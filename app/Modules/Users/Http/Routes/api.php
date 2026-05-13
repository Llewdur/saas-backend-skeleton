<?php

declare(strict_types=1);

use App\Modules\Users\Http\Controllers\ListMembersController;
use App\Modules\Users\Http\Controllers\UpdateMemberRoleController;
use App\Modules\Users\Http\Controllers\UpdateProfileController;
use Illuminate\Support\Facades\Route;

// Profile is user-scoped, not tenant-scoped.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::patch('users/me', UpdateProfileController::class)->name('users.update-profile');
});

// Members are tenant-scoped.
Route::middleware(['auth:sanctum', 'tenant.ensure'])->group(function (): void {
    Route::get('members', ListMembersController::class)->name('members.index');
    Route::patch('members/{membership}', UpdateMemberRoleController::class)->name('members.update-role');
});
