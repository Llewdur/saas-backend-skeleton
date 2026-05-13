<?php

declare(strict_types=1);

use App\Modules\Audit\Http\Controllers\ListActivityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant.ensure'])->group(function (): void {
    Route::get('audit', ListActivityController::class)->name('audit.index');
});
