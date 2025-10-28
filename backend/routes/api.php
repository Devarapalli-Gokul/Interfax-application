<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FaxController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Webhook routes removed - not needed for real-time InterFAX APIs

// Auth routes (require authentication to use user's InterFAX credentials)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Account routes (require authentication to use user's InterFAX credentials)
    Route::get('/account/balance', [FaxController::class, 'getBalance']);
    
    // Fax routes (InterFAX API only - no local database storage)
    Route::prefix('faxes')->group(function () {
        // Inbound faxes (direct from InterFAX API)
        Route::get('/inbound', [FaxController::class, 'getInboundFaxes']);
        Route::get('/inbound/{id}/content', function($id) {
            return app(FaxController::class)->getFaxContent(request(), $id, 'inbound');
        })->where('id', '[0-9]+');
        Route::get('/inbound/{id}/status', function($id) {
            return app(FaxController::class)->getFaxStatus(request(), $id, 'inbound');
        })->where('id', '[0-9]+');
        
        // Outbound faxes (direct from InterFAX API)
        Route::get('/outbound', [FaxController::class, 'getOutboundFaxes']);
        Route::get('/outbound/{id}/content', function($id) {
            return app(FaxController::class)->getFaxContent(request(), $id, 'outbound');
        })->where('id', '[0-9]+');
        Route::get('/outbound/{id}/status', function($id) {
            return app(FaxController::class)->getFaxStatus(request(), $id, 'outbound');
        })->where('id', '[0-9]+');
        Route::post('/outbound', [FaxController::class, 'sendFax']);
        Route::post('/outbound/{id}/cancel', [FaxController::class, 'cancelFax'])->where('id', '[0-9]+');

        // Direct InterFAX API endpoints (no local database storage)
        Route::get('/interfax/inbound', [FaxController::class, 'getInboundFaxesFromInterfax']);
        Route::get('/interfax/outbound', [FaxController::class, 'getOutboundFaxesFromInterfax']);
        Route::get('/interfax/inbound/{id}/content', [FaxController::class, 'getInterfaxInboundFaxContent'])->where('id', '[0-9]+');
        Route::get('/interfax/outbound/{id}/content', [FaxController::class, 'getInterfaxOutboundFaxContent'])->where('id', '[0-9]+');
        Route::get('/interfax/{type}/{id}/content', [FaxController::class, 'getInterfaxFaxContent'])->where('type', 'inbound|outbound')->where('id', '[0-9]+');
    });
});

// Admin routes removed - not needed for real-time InterFAX APIs
