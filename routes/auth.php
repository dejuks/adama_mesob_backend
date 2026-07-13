<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FaydaController;


use App\Http\Controllers\Api\RefreshTokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/refresh', [RefreshTokenController::class, 'refresh']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/profile', [UserController::class, 'profile']);
        Route::post('/profile', [UserController::class, 'updateProfile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/profile/password', [UserController::class, 'changeOwnPassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
Route::post(
    '/auth/fayda/callback',
    [FaydaController::class, 'callback']
);
