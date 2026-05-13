<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Subscription;

/**
 * Returns the current user's subscription state.
 *
 * Note: in production this should move to per-Tenant billing (apply the
 * Billable trait to Tenant instead of User, migrate customer_columns to the
 * tenants table). Stub is per-user to keep the skeleton runnable without
 * Stripe credentials configured. See README "Billing" section.
 */
final class SubscriptionController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return new JsonResponse([
                'errors' => [['code' => 'unauthenticated', 'title' => 'Unauthenticated']],
            ], 401);
        }

        $subscription = $user->subscription();

        if (! $subscription instanceof Subscription) {
            return new JsonResponse([
                'data' => [
                    'status' => 'none',
                    'plan' => 'free',
                ],
            ]);
        }

        return new JsonResponse([
            'data' => [
                'status' => $subscription->stripe_status,
                'plan' => $subscription->type,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ],
        ]);
    }
}
