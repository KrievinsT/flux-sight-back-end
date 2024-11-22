<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WebController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\TwoFactorAuthController;

Route::middleware(['web'])->group(function () {
    Route::post('/pre-register', [AuthController::class, 'preRegister']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/pre-login', [AuthController::class, 'preLogin']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/2fa/generate', [TwoFactorAuthController::class, 'generate2FACode']);
    Route::post('/2fa/verify', [TwoFactorAuthController::class, 'verify2FACode']);
    Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
});

// Social authentication routes
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Web routes
Route::post('/web/store', [WebController::class, 'store']);
Route::get('/web', [WebController::class, 'index']);

// Storage routes
Route::post('/storage/store', [StorageController::class, 'store']);
Route::get('/storage', [StorageController::class, 'index']);
