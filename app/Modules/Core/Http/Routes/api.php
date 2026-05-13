<?php

declare(strict_types=1);

use App\Modules\Core\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant.ensure'])->group(function (): void {
    Route::apiResource('projects', ProjectController::class);
});
