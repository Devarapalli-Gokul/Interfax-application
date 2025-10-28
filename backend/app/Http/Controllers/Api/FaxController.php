<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FaxController extends BaseInterfaxController
{
    private $interfaxClient;

    public function __construct()
    {
        // Constructor simplified - client creation moved to base class
    }


    public function getInboundFaxes(Request $request)
    {
        $user = $request->user();
        if (!$this->isInterfaxConfigured($user)) {
            return $this->handleInterfaxNotConfigured();
        }

        try {
            // Get pagination parameters
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);
            $offset = ($page - 1) * $perPage;
            
            // Validate pagination parameters
            $perPage = max(1, min(50, $perPage)); // Limit per_page between 1 and 50
            $page = max(1, $page); // Page must be at least 1
            
            $interfaxClient = $this->getInterfaxClient($user);
            if (!$interfaxClient) {
                return $this->handleInterfaxNotConfigured();
            }
            
            // Fetch a reasonable number of faxes and do client-side pagination
            // This avoids rate limiting while still providing consistent results
            $faxes = $interfaxClient->getInboundFaxes(50, 0); // Get 50 most recent faxes
            
            // Transform the data to match frontend expectations
            $transformedFaxes = collect($faxes)->map(function ($fax) {
                // Extract meaningful sender information from available fields
                $senderInfo = $this->extractSenderInfo($fax);
                
                return [
                    'id' => $fax['id'], // Use InterFAX ID directly
                    'from_number' => $fax['faxNumber'],
                    'status' => $fax['status'],
                    'pages' => $fax['pages'],
                    'received_at' => $fax['completionTime'],
                    'duration' => $fax['duration'],
                    'csid' => $fax['csid'],
                    'sender_name' => $senderInfo['name'],
                    'sender_email' => $senderInfo['email'],
                    'sender_details' => $senderInfo['details'],
                    'metadata' => $fax, // Include full metadata for debugging
                    'type' => 'inbound',
                    'created_at' => $fax['completionTime'],
                    'updated_at' => $fax['completionTime'],
                ];
            })->sortByDesc('received_at'); // Sort by received date for consistent ordering

            // Apply client-side pagination
            $total = $transformedFaxes->count();
            $paginatedFaxes = $transformedFaxes->slice($offset, $perPage)->values();

            return response()->json([
                'data' => $paginatedFaxes,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_next_page' => $page < ceil($total / $perPage),
                    'has_previous_page' => $page > 1,
                    'next_page' => $page < ceil($total / $perPage) ? $page + 1 : null,
                    'previous_page' => $page > 1 ? $page - 1 : null,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch inbound faxes from InterFAX: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch inbound faxes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getOutboundFaxes(Request $request)
    {
        $user = $request->user();
        if (!$this->isInterfaxConfigured($user)) {
            return $this->handleInterfaxNotConfigured();
        }

        try {
            // Get pagination parameters
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);
            $offset = ($page - 1) * $perPage;
            
            // Validate pagination parameters
            $perPage = max(1, min(50, $perPage)); // Limit per_page between 1 and 50
            $page = max(1, $page); // Page must be at least 1
            
            $interfaxClient = $this->getInterfaxClient($user);
            if (!$interfaxClient) {
                return $this->handleInterfaxNotConfigured();
            }
            
            // Fetch a reasonable number of faxes and do client-side pagination
            // This avoids rate limiting while still providing consistent results
            $faxes = $interfaxClient->getOutboundFaxes(50, 0); // Get 50 most recent faxes
            
            // Debug: Log the raw faxes data structure
            Log::info('Raw faxes from InterFAX client in getOutboundFaxes:', [
                'count' => count($faxes),
                'first_fax_keys' => !empty($faxes) ? array_keys($faxes[0]) : [],
                'first_fax_data' => !empty($faxes) ? $faxes[0] : null
            ]);
            
            // Transform the data to match frontend expectations
            $transformedFaxes = collect($faxes)->map(function ($fax) {
                // Extract meaningful recipient information from available fields
                $recipientInfo = $this->extractRecipientInfo($fax);
                
                // Sanity patch: ensure replyEmail exists and metadata is object
                $meta = $fax['metadata'] ?? [];
                if (is_string($meta)) { 
                    $meta = json_decode($meta, true) ?: []; 
                }
                // Get replyEmail from the correct field (InterFAX client returns it as 'replyEmail')
                $replyEmail = $fax['replyEmail'] ?? $meta['replyEmail'] ?? null;
                
                return [
                    'id' => $fax['id'], // Use InterFAX ID directly
                    'fax_number' => $fax['faxNumber'],
                    'status' => $fax['status'],
                    'pages' => $fax['pages'],
                    'sent_at' => $fax['submitTime'],
                    'completion_time' => $fax['completionTime'],
                    'duration' => $fax['duration'],
                    'cost' => $fax['cost'],
                    'subject' => $fax['subject'],
                    'replyEmail' => $replyEmail, // <-- forced to exist
                    'csid' => $fax['csid'],
                    'recipient_name' => $recipientInfo['name'],
                    'recipient_email' => $recipientInfo['email'],
                    'recipient_details' => $recipientInfo['details'],
                    'metadata' => $meta, // <-- ensure object in JSON
                    'type' => 'outbound',
                    'created_at' => $fax['submitTime'],
                    'updated_at' => $fax['completionTime'] ?? $fax['submitTime'],
                ];
            })->sortByDesc('sent_at'); // Sort by sent date for consistent ordering

            // Apply client-side pagination
            $total = $transformedFaxes->count();
            $paginatedFaxes = $transformedFaxes->slice($offset, $perPage)->values();

            return response()->json([
                'data' => $paginatedFaxes,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_next_page' => $page < ceil($total / $perPage),
                    'has_previous_page' => $page > 1,
                    'next_page' => $page < ceil($total / $perPage) ? $page + 1 : null,
                    'previous_page' => $page > 1 ? $page - 1 : null,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch outbound faxes from InterFAX: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch outbound faxes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Note: Individual fax retrieval methods removed - use InterFAX API directly
    // Individual faxes can be retrieved via InterFAX API using the fax ID

    public function getFaxContent(Request $request, $id, $type = 'outbound')
    {
        $user = $request->user();
        if (!$this->isInterfaxConfigured($user)) {
            return $this->handleInterfaxNotConfigured();
        }

        try {
            Log::info("Getting fax content from InterFAX", [
                'fax_id' => $id,
                'type' => $type
            ]);
            
            $interfaxClient = $this->getInterfaxClient($user);
            if (!$interfaxClient) {
                return $this->handleInterfaxNotConfigured();
            }
            $content = $interfaxClient->getFaxContent($id, $type);
            
            Log::info("Content retrieved from InterFAX", [
                'content_length' => strlen($content),
                'content_start' => substr($content, 0, 20)
            ]);
            
            // Determine MIME type and handle format conversion
            $mimeType = 'application/pdf'; // Default
            $finalContent = $content;
            
            if (strpos($content, '%PDF-') === 0) {
                // Already PDF - use as is
                $mimeType = 'application/pdf';
            } elseif (strpos($content, 'II*') === 0 || strpos($content, 'MM*') === 0 || strpos($content, 'TIFF') === 0) {
                // TIFF content - attempt conversion to PDF for inline preview
                Log::info("Processing TIFF content for outbound fax", ['fax_id' => $id]);
                
                try {
                    // Create temporary files for conversion
                    $tempTiffFile = tempnam(sys_get_temp_dir(), 'tiff_');
                    $tempPdfFile = tempnam(sys_get_temp_dir(), 'pdf_');
                    
                    Log::info("Converting TIFF to PDF using tiff2pdf", ['fax_id' => $id]);
                        
                    try {
                        // Write TIFF content to temporary file
                        file_put_contents($tempTiffFile, $content);
                        
                        // Use tiff2pdf for conversion (best for fax TIFFs)
                        $TIFF2PDF = '/usr/local/bin/tiff2pdf';
                        if (is_file($TIFF2PDF)) {
                            $cmd = escapeshellcmd($TIFF2PDF) . ' -o ' . escapeshellarg($tempPdfFile) . ' ' . escapeshellarg($tempTiffFile) . ' 2>&1';
                            $output = shell_exec($cmd);
                            
                            if (file_exists($tempPdfFile) && filesize($tempPdfFile) > 0) {
                                $convertedContent = file_get_contents($tempPdfFile);
                                
                                if (strpos($convertedContent, '%PDF-') === 0) {
                                    $finalContent = $convertedContent;
                                    $mimeType = 'application/pdf';
                                    Log::info("Successfully converted TIFF to PDF using tiff2pdf", ['fax_id' => $id]);
                                } else {
                                    Log::warning("tiff2pdf produced invalid PDF content", ['fax_id' => $id, 'output' => $output]);
                                    $finalContent = $content; // Use original TIFF content
                                    $mimeType = 'image/tiff';
                                }
                            } else {
                                Log::warning("tiff2pdf failed to create PDF file", ['fax_id' => $id, 'output' => $output]);
                                $finalContent = $content; // Use original TIFF content
                                $mimeType = 'image/tiff';
                            }
                        } else {
                            Log::warning("tiff2pdf binary not found at /usr/local/bin/tiff2pdf", ['fax_id' => $id]);
                            $finalContent = $content; // Use original TIFF content
                            $mimeType = 'image/tiff';
                        }
                        
                        Log::info("Successfully converted TIFF to PDF", [
                            'original_size' => strlen($content),
                            'converted_size' => strlen($finalContent)
                        ]);
                        
                    } finally {
                        // Clean up temporary files
                        if (file_exists($tempTiffFile)) unlink($tempTiffFile);
                        if (file_exists($tempPdfFile)) unlink($tempPdfFile);
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to convert TIFF to PDF: " . $e->getMessage());
                    // Fallback to serving TIFF as download
                    $mimeType = 'image/tiff';
                    $finalContent = $content; // Use original TIFF content
                }
            } elseif (strpos($content, 'GIF') === 0 || strpos($content, 'PNG') === 0) {
                $mimeType = 'image/png';
            }
            
            // Set appropriate disposition based on content type
            $disposition = 'inline';
            if ($mimeType === 'image/tiff') {
                // Force download for TIFF files since browsers don't display them well
                $disposition = 'attachment; filename="fax_' . $id . '.tiff"';
            } elseif ($mimeType === 'application/pdf') {
                // For PDF files, always use inline for preview
                $disposition = 'inline; filename="fax_' . $id . '.pdf"';
            } elseif ($request->get('inline')) {
                $disposition = 'inline';
            } else {
                $disposition = 'attachment';
            }
            
            return response($finalContent, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => $disposition,
                'Cache-Control' => 'no-cache, must-revalidate',
                'Content-Length' => strlen($finalContent),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get fax content: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to retrieve fax content',
                'message' => $e->getMessage(),
                'fax_id' => $id,
                'type' => $type
            ], 500);
        }
    }

    /**
     * Get inbound fax content directly from InterFAX API
     */
    public function getInterfaxInboundFaxContent(Request $request, $id)
    {
        return $this->getInterfaxFaxContent($request, 'inbound', $id);
    }

    /**
     * Get outbound fax content directly from InterFAX API
     */
    public function getInterfaxOutboundFaxContent(Request $request, $id)
    {
        return $this->getInterfaxFaxContent($request, 'outbound', $id);
    }

    /**
     * Get fax content directly from InterFAX API for both inbound and outbound faxes
     */
    public function getInterfaxFaxContent(Request $request, $type, $id)
    {
        // Use the same logic as getFaxContent method to ensure TIFF to PDF conversion works
        return $this->getFaxContent($request, $id, $type);
    }

    public function sendFax(Request $request)
    {
        // Get user from request (must be authenticated)
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'error' => 'Authentication required'
            ], 401);
        }

        if (!$this->isInterfaxConfigured($user)) {
            return $this->handleInterfaxNotConfigured();
        }

        $request->validate([
            'fax_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
            'file' => 'required_without:file_url|file|mimes:pdf,tiff,tif,doc,docx|max:10240',
            'file_url' => 'required_without:file|url',
        ]);

        try {
            $interfaxClient = $this->getInterfaxClient($user);
            if (!$interfaxClient) {
                return $this->handleInterfaxNotConfigured();
            }
            $filePath = null;
            $fileUrl = $request->file_url;

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('temp', $fileName, 'local');
            }

            // Prepare additional parameters (map to InterFAX names)
            $additionalParams = [];
            if ($request->filled('subject')) {
                $additionalParams['reference'] = (string) $request->input('subject');
            }
            if ($request->filled('replyEmail')) {
                $additionalParams['replyAddress'] = (string) $request->input('replyEmail');
            }
            if ($request->filled('recipient_name')) {
                $additionalParams['contact'] = (string) $request->input('recipient_name');
            }

            // Send to InterFAX
            $response = $interfaxClient->sendFax(
                $request->fax_number,
                $filePath ? storage_path('app/' . $filePath) : null,
                $fileUrl,
                $additionalParams
            );

            // Log successful fax submission
            Log::info("Fax submitted successfully", [
                'fax_id' => $response['id'] ?? null,
                'fax_number' => $request->fax_number,
                'status' => $response['status'] ?? 'pending',
                'pages' => $response['pages'] ?? null,
                'user_id' => $user->id
            ]);

            // Clean up temp file if it was uploaded
            if ($filePath) {
                // Remove temp file after successful submission
                if (file_exists($filePath)) {
                    unlink($filePath);
                    Log::info("Temp file cleaned up: {$filePath}");
                }
            } elseif ($fileUrl) {
                Log::info("Fax sent via URL: {$fileUrl}");
            }

            // Return InterFAX response with additional metadata
            $faxResponse = array_merge($response, [
                'fax_number' => $request->fax_number,
                'subject' => $request->subject ?? null,
                'replyEmail' => $request->replyEmail ?? null,
                'recipient_name' => $request->recipient_name ?? null,
                'sent_at' => now()->toISOString(),
            ]);

            return response()->json($faxResponse, 201);
        } catch (\Exception $e) {
            Log::error("Failed to send fax: " . $e->getMessage());
            
            // Provide specific error messages for common InterFAX issues
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'designated fax number')) {
                $errorMessage = 'Developer account restriction: You can only send faxes to designated numbers. Please contact InterFAX support or upgrade your account.';
            } elseif (str_contains($errorMessage, 'Invalid recipient')) {
                $errorMessage = 'Invalid fax number format. Please use international format (e.g., +1555123456).';
            } elseif (str_contains($errorMessage, 'balance')) {
                $errorMessage = 'Insufficient account balance. Please add credits to your InterFAX account.';
            }
            
            return response()->json([
                'error' => 'Failed to send fax: ' . $errorMessage,
                'details' => 'This is likely due to InterFAX developer account restrictions. You may need to upgrade your account or contact InterFAX support for designated fax numbers.'
            ], 500);
        }
    }

    public function cancelFax(Request $request, $id)
    {
        $user = $request->user();
        if (!$this->isInterfaxConfigured($user)) {
            return $this->handleInterfaxNotConfigured();
        }

        try {
            $interfaxClient = $this->getInterfaxClient($user);
            if (!$interfaxClient) {
                return $this->handleInterfaxNotConfigured();
            }
            
            // Cancel fax directly via InterFAX API
            $response = $interfaxClient->cancelFax($id);
            
            Log::info("Fax cancelled successfully", [
                'fax_id' => $id,
                'user_id' => $user->id,
                'response' => $response
            ]);
            
            return response()->json([
                'id' => $id,
                'status' => 'cancelled',
                'cancelled_at' => now()->toISOString(),
                'message' => 'Fax cancelled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to cancel fax: " . $e->getMessage());
            return response()->json(['error' => 'Failed to cancel fax: ' . $e->getMessage()], 500);
        }
    }

    // Webhook method removed - not needed for real-time InterFAX APIs

    /**
     * Get account balance
     */
    public function getBalance(Request $request)
    {
        $user = $request->user();
        if (!$this->isInterfaxConfigured($user)) {
            return $this->handleInterfaxNotConfigured();
        }

        try {
            $interfaxClient = $this->getInterfaxClient($user);
            if (!$interfaxClient) {
                return $this->handleInterfaxNotConfigured();
            }
            $balance = $interfaxClient->getBalance();
            return response()->json($balance);
        } catch (\Exception $e) {
            Log::error("Failed to get balance: " . $e->getMessage());
            return response()->json(['error' => 'Failed to get account balance'], 500);
        }
    }

    /**
     * Get fax status from Interfax
     */
    public function getFaxStatus(Request $request, $id, $type = 'outbound')
    {
        $user = $request->user();
        if (!$this->isInterfaxConfigured($user)) {
            return $this->handleInterfaxNotConfigured();
        }

        $fax = Fax::where('type', $type)
            ->findOrFail($id);

        try {
            $interfaxClient = $this->getInterfaxClient($user);
            if (!$interfaxClient) {
                return $this->handleInterfaxNotConfigured();
            }
            
            $status = $interfaxClient->getFaxStatus($fax->interfax_id ?? $id, $type);
            
            // Update local status if different
            if (isset($status['status']) && $status['status'] !== $fax->status) {
                $fax->update([
                    'status' => $status['status'],
                    'metadata' => array_merge($fax->metadata ?? [], $status),
                    'completed_at' => in_array($status['status'], ['completed', 'failed', 'cancelled']) ? now() : null,
                ]);
            }
            
            return response()->json($status);
        } catch (\Exception $e) {
            Log::error("Failed to get fax status: " . $e->getMessage());
            return response()->json(['error' => 'Failed to get fax status'], 500);
        }
    }

    /**
     * Get inbound faxes directly from InterFAX API (not stored in local database)
     */
    public function getInboundFaxesFromInterfax(Request $request)
    {
        $user = $request->user();
        if (!$this->isInterfaxConfigured($user)) {
            return $this->handleInterfaxNotConfigured();
        }

        try {
            // Get pagination parameters
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);
            $offset = ($page - 1) * $perPage;
            
            // Validate pagination parameters
            $perPage = max(1, min(50, $perPage)); // Limit per_page between 1 and 50
            $page = max(1, $page); // Page must be at least 1
            
            $interfaxClient = $this->getInterfaxClient($user);
            if (!$interfaxClient) {
                return $this->handleInterfaxNotConfigured();
            }
            
            // Fetch a reasonable number of faxes and do client-side pagination
            // This avoids rate limiting while still providing consistent results
            $faxes = $interfaxClient->getInboundFaxes(50, 0); // Get 50 most recent faxes
            
            // Transform the data to match frontend expectations
            $transformedFaxes = collect($faxes)->map(function ($fax) {
                // Extract meaningful sender information from available fields
                $senderInfo = $this->extractSenderInfo($fax);
                
                return [
                    'id' => $fax['id'],
                    'from_number' => $fax['faxNumber'],
                    'status' => $fax['status'],
                    'pages' => $fax['pages'],
                    'received_at' => $fax['completionTime'],
                    'duration' => $fax['duration'],
                    'csid' => $fax['csid'],
                    'sender_name' => $senderInfo['name'],
                    'sender_email' => $senderInfo['email'],
                    'sender_details' => $senderInfo['details'],
                    'metadata' => $fax,
                ];
            })->sortByDesc('received_at'); // Sort by received date for consistent ordering
            
            // Apply client-side pagination
            $totalFaxes = $transformedFaxes->count();
            $paginatedFaxes = $transformedFaxes->slice($offset, $perPage)->values();
            
            // Calculate pagination metadata
            $totalPages = ceil($totalFaxes / $perPage);
            $hasNextPage = $page < $totalPages;
            $hasPreviousPage = $page > 1;
            
            return response()->json([
                'data' => $paginatedFaxes->values(),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalFaxes,
                    'total_pages' => $totalPages,
                    'has_next_page' => $hasNextPage,
                    'has_previous_page' => $hasPreviousPage,
                    'next_page' => $hasNextPage ? $page + 1 : null,
                    'previous_page' => $hasPreviousPage ? $page - 1 : null,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $totalFaxes)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch inbound faxes from InterFAX: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch inbound faxes from InterFAX',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get outbound faxes directly from InterFAX API (not stored in local database)
     */
    public function getOutboundFaxesFromInterfax(Request $request)
    {
        $user = $request->user();
        if (!$this->isInterfaxConfigured($user)) {
            return $this->handleInterfaxNotConfigured();
        }

        try {
            // Get pagination parameters
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);
            $offset = ($page - 1) * $perPage;
            
            // Validate pagination parameters
            $perPage = max(1, min(50, $perPage)); // Limit per_page between 1 and 50
            $page = max(1, $page); // Page must be at least 1
            
            $interfaxClient = $this->getInterfaxClient($user);
            if (!$interfaxClient) {
                return $this->handleInterfaxNotConfigured();
            }
            
            // Fetch a reasonable number of faxes and do client-side pagination
            // This avoids rate limiting while still providing consistent results
            $faxes = $interfaxClient->getOutboundFaxes(50, 0); // Get 50 most recent faxes
            
            // Transform the data to match frontend expectations
            $transformedFaxes = collect($faxes)->map(function ($fax) {
                // Extract meaningful recipient information from available fields
                $recipientInfo = $this->extractRecipientInfo($fax);
                
                return [
                    'id' => $fax['id'], // Use InterFAX ID directly
                    'fax_number' => $fax['faxNumber'],
                    'status' => $fax['status'],
                    'pages' => $fax['pages'],
                    'sent_at' => $fax['submitTime'],
                    'completion_time' => $fax['completionTime'],
                    'duration' => $fax['duration'],
                    'cost' => $fax['cost'],
                    'subject' => $fax['subject'],
                    'csid' => $fax['csid'],
                    'recipient_name' => $recipientInfo['name'],
                    'recipient_email' => $recipientInfo['email'],
                    'recipient_details' => $recipientInfo['details'],
                    'metadata' => $fax,
                ];
            })->sortByDesc('sent_at'); // Sort by sent date for consistent ordering
            
            // Apply client-side pagination
            $totalFaxes = $transformedFaxes->count();
            $paginatedFaxes = $transformedFaxes->slice($offset, $perPage)->values();
            
            // Calculate pagination metadata
            $totalPages = ceil($totalFaxes / $perPage);
            $hasNextPage = $page < $totalPages;
            $hasPreviousPage = $page > 1;
            
            return response()->json([
                'data' => $paginatedFaxes->values(),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalFaxes,
                    'total_pages' => $totalPages,
                    'has_next_page' => $hasNextPage,
                    'has_previous_page' => $hasPreviousPage,
                    'next_page' => $hasNextPage ? $page + 1 : null,
                    'previous_page' => $hasPreviousPage ? $page - 1 : null,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $totalFaxes)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch outbound faxes from InterFAX: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch outbound faxes from InterFAX',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Note: createMockInboundFax method removed - no local database storage

    /**
     * Extracts meaningful sender information from available InterFAX fields.
     * InterFAX API doesn't provide sender names, so we extract what we can.
     */
    private function extractSenderInfo($faxData)
    {
        $senderName = 'Unknown Sender';
        $senderEmail = null;
        $senderDetails = [];
        
        // Try to extract meaningful information from available fields
        if (!empty($faxData['csid']) && $faxData['csid'] !== 'INTERFAX') {
            $senderName = $faxData['csid'];
            $senderDetails[] = 'CSID: ' . $faxData['csid'];
        }
        
        if (!empty($faxData['replyEmail'])) {
            $senderEmail = $faxData['replyEmail'];
            $senderDetails[] = 'Reply Email: ' . $faxData['replyEmail'];
        }
        
        if (!empty($faxData['subject'])) {
            $senderDetails[] = 'Subject: ' . $faxData['subject'];
        }
        
        if (!empty($faxData['faxNumber'])) {
            $senderDetails[] = 'From: ' . $faxData['faxNumber'];
        }
        
        // If we have some details, use the first meaningful one as the name
        if (!empty($senderDetails) && $senderName === 'Unknown Sender') {
            $senderName = $faxData['faxNumber'] ?? 'Unknown Sender';
        }
        
        return [
            'name' => $senderName,
            'email' => $senderEmail,
            'details' => implode(' | ', $senderDetails),
        ];
    }
    

    /**
     * Extracts meaningful recipient information from available InterFAX fields.
     * InterFAX API doesn't provide recipient names, so we extract what we can.
     */
    private function extractRecipientInfo($faxData)
    {
        $recipientName = 'Unknown Recipient';
        $recipientEmail = null;
        $recipientDetails = [];
        
        // Try to extract meaningful information from available fields
        if (!empty($faxData['replyEmail'])) {
            $recipientEmail = $faxData['replyEmail'];
            $recipientDetails[] = 'Reply Email: ' . $faxData['replyEmail'];
        }
        
        if (!empty($faxData['subject'])) {
            $recipientDetails[] = 'Subject: ' . $faxData['subject'];
        }
        
        if (!empty($faxData['faxNumber'])) {
            $recipientName = $faxData['faxNumber'];
            $recipientDetails[] = 'To: ' . $faxData['faxNumber'];
        }
        
        if (!empty($faxData['csid'])) {
            $recipientDetails[] = 'CSID: ' . $faxData['csid'];
        }
        
        return [
            'name' => $recipientName,
            'email' => $recipientEmail,
            'details' => implode(' | ', $recipientDetails),
        ];
    }
}
