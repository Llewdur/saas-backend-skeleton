<?php

declare(strict_types=1);

use App\Modules\Auth\Http\Controllers\LoginController;
use App\Modules\Auth\Http\Controllers\LogoutController;
use App\Modules\Auth\Http\Controllers\MeController;
use App\Modules\Auth\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

// Aggressive throttles on the unauthenticated surface — these are the routes
// an attacker would hit to enumerate emails or brute-force credentials.
Route::middleware('throttle:auth')->group(function (): void {
    Route::post('auth/register', RegisterController::class)->name('auth.register');
    Route::post('auth/login', LoginController::class)->name('auth.login');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('auth/logout', LogoutController::class)->name('auth.logout');
    Route::get('auth/me', MeController::class)->name('auth.me');
});
