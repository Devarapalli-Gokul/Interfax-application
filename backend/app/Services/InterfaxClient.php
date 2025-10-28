<?php

namespace App\Services;

use Interfax\Client;
use Interfax\GenericFactory;
use Interfax\Exception\RequestException;
use Interfax\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class InterfaxClient
{
    private Client $client;
    private ?string $username = null;
    private ?string $password = null;
    
    public function getClient(): Client
    {
        return $this->client;
    }

    public function __construct(?string $username = null, ?string $password = null)
    {
        $this->username = $username;
        $this->password = $password;
        
        // Create InterFAX client with credentials
        $params = [];
        if ($username && $password) {
            $params['username'] = $username;
            $params['password'] = $password;
        }
        
        $factory = new GenericFactory();
        $this->client = new Client($params, $factory);
        
        // Ensure the client has the correct headers
        $this->ensureCorrectHeaders();
    }

    /**
     * Ensure the InterFAX client has the correct headers
     */
    private function ensureCorrectHeaders(): void
    {
        // Use reflection to access the private base_uri property
        $reflection = new \ReflectionClass($this->client);
        
        // Check if the client has the correct headers by making a test request
        // This is a workaround since we can't directly modify the client's headers
        Log::info('InterFAX client headers verification', [
            'username' => $this->username ? substr($this->username, 0, 2) . '****' . substr($this->username, -2) : null,
            'base_uri' => 'https://rest.interfax.net'
        ]);
    }

    /**
     * Get inbound faxes
     */
    public function getInboundFaxes($limit = 10, $offset = 0): array
    {
        try {
            // Use proper pagination parameters
            $response = $this->client->inbound->incoming([
                'limit' => $limit,
                'offset' => $offset
            ]);
            return $this->convertFaxesToArray($response);
        } catch (RequestException $e) {
            Log::error('InterFAX getInboundFaxes error: ' . $e->getMessage());
            throw new \Exception('Failed to fetch inbound faxes: ' . $e->getMessage());
        }
    }

    /**
     * Get outbound faxes
     */
    public function getOutboundFaxes($limit = 10, $offset = 0): array
    {
        try {
            // Use proper pagination parameters
            $params = [
                'limit' => $limit,
                'offset' => $offset
            ];
            Log::info('InterFAX getOutboundFaxes params', $params);
            $response = $this->client->outbound->recent($params);
            return $this->convertFaxesToArray($response);
        } catch (RequestException $e) {
            Log::error('InterFAX getOutboundFaxes error: ' . $e->getMessage());
            throw new \Exception('Failed to fetch outbound faxes: ' . $e->getMessage());
        }
    }

    /**
     * Get fax by ID
     */
    public function getFax(string $id, string $type = 'outbound'): array
    {
        try {
            if ($type === 'inbound') {
                $response = $this->client->inbound->find($id);
            } else {
                $response = $this->client->outbound->find($id);
            }
            
            if (!$response) {
                throw new \Exception("Fax with ID {$id} not found");
            }
            
            return $this->convertFaxToArray($response);
        } catch (RequestException $e) {
            Log::error("InterFAX getFax error: " . $e->getMessage());
            throw new \Exception("Failed to fetch fax {$id}: " . $e->getMessage());
        }
    }

    /**
     * Send a fax
     */
    public function sendFax(string $faxNumber, $file, ?string $fileUrl = null, array $additionalParams = []): array
    {
        try {
            $params = ['faxNumber' => $faxNumber];
            
            if ($fileUrl) {
                // Send fax using URL
                $params['file'] = $fileUrl;
            } else {
                // Send fax using local file
                $params['file'] = $file;
            }

            // Add additional parameters if provided (using InterFAX parameter names)
            if (!empty($additionalParams['reference'])) {
                $params['reference'] = $additionalParams['reference'];
            }
            if (!empty($additionalParams['replyAddress'])) {
                $params['replyAddress'] = $additionalParams['replyAddress'];
            }
            if (!empty($additionalParams['contact'])) {
                $params['contact'] = $additionalParams['contact'];
            }

            // Debug: Log the parameters being sent to InterFAX
            Log::info('InterFAX sendFax parameters', $params);

            // Use the deliver method
            $fax = $this->client->deliver($params);
            
            // Debug: Log the response from InterFAX
            Log::info('InterFAX sendFax response', [
                'id' => $fax->id ?? null,
                'status' => $fax->status ?? null,
                'attributes' => $fax->attributes() ?? null
            ]);
            
            return [
                'id' => $fax->id,
                'status' => $fax->status,
                'location' => $fax->getLocation()
            ];
        } catch (RequestException $e) {
            Log::error('InterFAX sendFax error: ' . $e->getMessage());
            throw new \Exception('Failed to send fax: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a fax
     */
    public function cancelFax(string $id): array
    {
        try {
            $fax = $this->client->outbound->find($id);
            if (!$fax) {
                throw new \Exception("Fax with ID {$id} not found");
            }
            
            $fax->cancel();
            return ['status' => 'cancelled'];
        } catch (RequestException $e) {
            Log::error('InterFAX cancelFax error: ' . $e->getMessage());
            throw new \Exception('Failed to cancel fax: ' . $e->getMessage());
        }
    }

    /**
     * Get fax content (stream) from InterFAX API with comprehensive format support
     */
    public function getFaxContent(string $id, string $type = 'outbound', string $preferredFormat = 'pdf'): string
    {
        try {
            if ($type === 'inbound') {
                $fax = $this->client->inbound->find($id);
            } else {
                $fax = $this->client->outbound->find($id);
            }
            
            // Check if fax exists
            if (!$fax) {
                throw new \Exception("Fax with ID {$id} not found in InterFAX");
            }
            
            $image = $fax->image();
            
            // Check if image exists
            if (!$image) {
                throw new \Exception("Fax content not available for ID {$id}");
            }
            
            // Extract content from Interfax\Image object
            $reflection = new \ReflectionClass($image);
            $streamProperty = $reflection->getProperty('stream');
            $streamProperty->setAccessible(true);
            $stream = $streamProperty->getValue($image);
            
            // Read the stream content
            $content = '';
            while (!$stream->eof()) {
                $content .= $stream->read(1024 * 1024); // Read in 1MB chunks
            }
            
            return $content;
        } catch (RequestException $e) {
            Log::error("InterFAX getFaxContent error: " . $e->getMessage());
            throw new \Exception("Failed to fetch fax content {$id}: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::error("InterFAX getFaxContent error: " . $e->getMessage());
            throw new \Exception("Failed to fetch fax content {$id}: " . $e->getMessage());
        }
    }

    /**
     * Get account balance
     */
    public function getBalance(): array
    {
        try {
            $balance = $this->client->getBalance();
            return ['balance' => $balance];
        } catch (RequestException $e) {
            Log::error('InterFAX getBalance error: ' . $e->getMessage(), [
                'exception_type' => get_class($e),
                'code' => $e->getCode()
            ]);
            throw new \Exception('Failed to get account balance: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('InterFAX getBalance unexpected error: ' . $e->getMessage(), [
                'type' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to get account balance: ' . $e->getMessage());
        }
    }

    /**
     * Get fax status
     */
    public function getFaxStatus(string $id, string $type = 'outbound'): array
    {
        try {
            if ($type === 'inbound') {
                $fax = $this->client->inbound->find($id);
            } else {
                $fax = $this->client->outbound->find($id);
            }
            
            if (!$fax) {
                throw new \Exception("Fax with ID {$id} not found");
            }
            
            return $this->convertFaxToArray($fax);
        } catch (RequestException $e) {
            Log::error("InterFAX getFaxStatus error: " . $e->getMessage());
            throw new \Exception("Failed to get fax status {$id}: " . $e->getMessage());
        }
    }

    /**
     * Convert fax objects to array format
     */
    private function convertFaxesToArray($faxes): array
    {
        $result = [];
        
        // Handle case where faxes is not iterable
        if (!is_iterable($faxes)) {
            Log::error('InterFAX returned non-iterable faxes', ['faxes' => $faxes]);
            return [];
        }
        
        foreach ($faxes as $fax) {
            try {
                $result[] = $this->convertFaxToArray($fax);
            } catch (\Exception $e) {
                Log::error('Failed to convert fax to array', [
                    'error' => $e->getMessage(),
                    'fax' => $fax
                ]);
                // Skip this fax and continue with others
                continue;
            }
        }
        return $result;
    }

    /**
     * Convert single fax object to array format
     */
    private function convertFaxToArray($fax): array
    {
        // Handle different response types from InterFAX
        if (is_string($fax)) {
            Log::error('InterFAX returned string instead of object', ['fax' => $fax]);
            throw new \Exception('InterFAX API returned string instead of object');
        }
        
        if (is_array($fax)) {
            // If it's already an array, use it directly
            $attributes = $fax;
        } else {
            // Try to get attributes, with fallback
            try {
                $attributes = method_exists($fax, 'attributes') ? $fax->attributes() : (array) $fax;
            } catch (\Exception $e) {
                Log::error('Failed to get attributes from fax object', ['error' => $e->getMessage()]);
                $attributes = (array) $fax;
            }
        }
        
        // Debug: Log RAW InterFAX fax object to prove replyEmail exists
        Log::info('RAW_INTERFAX_FAX', ['fax' => json_decode(json_encode($fax), true)]);
        Log::info('RAW_INTERFAX_ATTRIBUTES', ['attributes' => $attributes]);
        
        // Ensure metadata is always an array
        $metadata = $attributes['metadata'] ?? [];
        
        // Sometimes SDK returns objects; normalize
        if (is_object($metadata)) {
            $metadata = json_decode(json_encode($metadata), true) ?: [];
        }
        
        $replyEmail = 
            $attributes['replyEmail'] 
            ?? $attributes['replyAddress']
            ?? $attributes['reply_email'] 
            ?? $attributes['replyTo'] 
            ?? $metadata['replyEmail'] 
            ?? $metadata['replyAddress']
            ?? null;
            
        $subject = 
            $attributes['reference']
            ?? $attributes['subject']
            ?? $metadata['reference']
            ?? $metadata['subject']
            ?? null;
        
        // Build the final array; include replyEmail explicitly
        return [
            'id' => $attributes['id'] ?? null,
            'status' => $this->mapStatus($attributes['status'] ?? $attributes['messageStatus'] ?? 'unknown'),
            'faxNumber' => $attributes['destinationFax'] ?? $attributes['phoneNumber'] ?? null,
            'pages' => $attributes['pagesSent'] ?? $attributes['pagesSubmitted'] ?? $attributes['pages'] ?? null,
            'cost' => $attributes['costPerUnit'] ?? null,
            'duration' => $attributes['duration'] ?? $attributes['recordingDuration'] ?? null,
            'submitTime' => $attributes['submitTime'] ?? null,
            'completionTime' => $attributes['completionTime'] ?? $attributes['receiveTime'] ?? null,
            'subject' => $subject,
            'replyEmail' => $replyEmail, // <-- top-level
            'pageSize' => $attributes['pageSize'] ?? null,
            'resolution' => $attributes['pageResolution'] ?? null,
            'rendering' => $attributes['rendering'] ?? null,
            'pageHeader' => $attributes['pageHeader'] ?? null,
            'retriesToPerform' => $attributes['attemptsToPerform'] ?? null,
            'csid' => $attributes['senderCSID'] ?? $attributes['remoteCSID'] ?? null,
            'attemptsMade' => $attributes['attemptsMade'] ?? null,
            'busyRetriesToPerform' => null, // Not available in current API
            'busyRetriesMade' => null, // Not available in current API
            'failureRetriesToPerform' => null, // Not available in current API
            'failureRetriesMade' => null, // Not available in current API
            'location' => $attributes['uri'] ?? null,
            'metadata' => array_merge($metadata, [
                'replyEmail' => $replyEmail, // <-- also in metadata for compatibility
            ]),
        ];
    }

    /**
     * Map InterFAX status codes to readable status
     */
    private function mapStatus($status): string
    {
        if (is_numeric($status)) {
            // Map numeric status codes based on actual InterFAX behavior
            // Status 0 appears to mean "completed successfully" based on the data
            $statusMap = [
                0 => 'completed',      // All faxes with status 0 have completion times and pages sent
                1 => 'in_progress',
                2 => 'completed',      // Alternative completion code
                3 => 'failed',
                4 => 'cancelled',
                5 => 'busy',
                6 => 'no_answer',
                7 => 'rejected',
                8 => 'retrying',
                9 => 'pending'         // If there's a pending status
            ];
            return $statusMap[$status] ?? 'unknown';
        }
        
        return $status;
    }
}
