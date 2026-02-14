<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (NutriSport)
|--------------------------------------------------------------------------
*/

// Public
Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

// User (JWT 6h)
Route::middleware('auth:api')->group(function (): void {
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/user', [App\Http\Controllers\Api\AuthController::class, 'user']);
    Route::put('/user/profile', [App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    Route::put('/user/password', [App\Http\Controllers\Api\AuthController::class, 'updatePassword']);
    Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/{order}', [App\Http\Controllers\Api\OrderController::class, 'show']);
});

// Agent (JWT 8h)
Route::prefix('agent')->group(function (): void {
    Route::post('/login', [App\Http\Controllers\Api\AgentAuthController::class, 'login']);
    Route::post('/logout', [App\Http\Controllers\Api\AgentAuthController::class, 'logout'])
        ->middleware('auth:agent');
});
