<?php

declare(strict_types=1);

use App\Modules\Billing\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('billing/subscription', SubscriptionController::class)->name('billing.subscription');
});
