<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InterfaxClient;
use Illuminate\Support\Facades\Log;

abstract class BaseInterfaxController extends Controller
{
    /**
     * Get InterFAX client with user credentials
     *
     * @param mixed $user
     * @return InterfaxClient|null
     */
    protected function getInterfaxClient($user = null)
    {
        if (!$user) {
            return null;
        }

        // Use user's InterFAX credentials
        if ($user->interfax_username && $user->interfax_password) {
            try {
                return new InterfaxClient($user->interfax_username, $user->interfax_password);
            } catch (\Exception $e) {
                Log::error('Failed to create InterFAX client with user credentials', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }

        return null;
    }

    /**
     * Check if InterFAX is properly configured for a user
     *
     * @param mixed $user
     * @return bool
     */
    protected function isInterfaxConfigured($user = null): bool
    {
        if (!$user) {
            return false;
        }

        return $user->interfax_username && $user->interfax_password;
    }

    /**
     * Handle InterFAX not configured error
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleInterfaxNotConfigured()
    {
        return response()->json([
            'error' => 'interfax credentials not configured'
        ], 503);
    }
}
