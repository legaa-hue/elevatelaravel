<?php

use App\Http\Controllers\Api\JWTAuthController;
use App\Http\Controllers\PushNotificationController;
use Illuminate\Support\Facades\Route;

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
Route::post('/auth/register', [JWTAuthController::class, 'register']);
Route::post('/auth/login', [JWTAuthController::class, 'login']);
Route::get('/push/public-key', [PushNotificationController::class, 'getPublicKey']);

// Protected routes (require JWT token)
Route::middleware('auth:api')->group(function () {
    Route::get('/auth/me', [JWTAuthController::class, 'me']);
    Route::post('/auth/logout', [JWTAuthController::class, 'logout']);
    Route::post('/auth/refresh', [JWTAuthController::class, 'refresh']);
    
    // Push notification routes
    Route::post('/push/subscribe', [PushNotificationController::class, 'subscribe']);
    Route::post('/push/unsubscribe', [PushNotificationController::class, 'unsubscribe']);
    Route::post('/push/test', [PushNotificationController::class, 'sendTest']);
});
