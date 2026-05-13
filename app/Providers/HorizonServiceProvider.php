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
     * Gate access to the /horizon dashboard in non-local environments.
     *
     * Skeleton policy: any authenticated user. In a real product, restrict
     * to ops / admin users — typically a list of emails from config, or a
     * role check against the user's membership in a designated "admin"
     * tenant. The standards' "no defensive code for states the type system
     * prevents" rule (§0.5) still applies — don't add elaborate role
     * machinery here until there's a second consumer that wants the same
     * gate.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (?User $user): bool => $user !== null);
    }
}
