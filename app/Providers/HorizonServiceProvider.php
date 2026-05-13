<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

final class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Gate access to the /horizon dashboard.
     *
     * Matches any authenticated user whose email appears in the
     * `horizon.admins` config (set via the HORIZON_ADMINS env var,
     * comma-separated). With no admins configured, access is denied —
     * fail closed.
     *
     * In APP_ENV=local Horizon's own routes bypass this gate, so dev
     * work still has dashboard access without configuration.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (?User $user): bool {
            if ($user === null) {
                return false;
            }

            $admins = array_filter(array_map(
                'trim',
                explode(',', (string) config('horizon.admins', '')),
            ));

            return in_array($user->email, $admins, strict: true);
        });
    }
}
