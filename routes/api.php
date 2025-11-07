<?php

use App\Http\Controllers\Api\JWTAuthController;
use App\Http\Controllers\FileUploadController;
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

// Protected routes (require JWT token)
Route::middleware(['auth:api'])->group(function () {
    Route::get('/auth/me', [JWTAuthController::class, 'me']);
    Route::post('/auth/logout', [JWTAuthController::class, 'logout']);
    Route::post('/auth/refresh', [JWTAuthController::class, 'refresh']);

    // File upload routes
    Route::get('/files/config', [FileUploadController::class, 'config']);
    Route::get('/files', [FileUploadController::class, 'index']);
    Route::post('/files/upload', [FileUploadController::class, 'upload']);
    Route::post('/files/upload-multiple', [FileUploadController::class, 'uploadMultiple']);
    Route::get('/files/{file}', [FileUploadController::class, 'show']);
    Route::delete('/files/{file}', [FileUploadController::class, 'destroy']);
});
