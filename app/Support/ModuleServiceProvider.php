<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Event => Listener[] subscriptions.
     *
     * Public so each module can declare its own subscriptions concisely.
     * Bindings/singletons use Laravel's native `public $bindings` / `public $singletons`
     * properties on the subclass.
     *
     * @var array<class-string, array<int, class-string>>
     */
    public array $listen = [];

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('Database/migrations'));
        $this->loadRoutesIfPresent();
        $this->registerListeners();
    }

    protected function modulePath(string $relative = ''): string
    {
        $reflector = new ReflectionClass($this);
        $base = dirname((string) $reflector->getFileName());

        return $relative === '' ? $base : $base.DIRECTORY_SEPARATOR.$relative;
    }

    private function loadRoutesIfPresent(): void
    {
        $apiRoutes = $this->modulePath('Http/Routes/api.php');
        if (! file_exists($apiRoutes)) {
            return;
        }

        Route::middleware('api')
            ->prefix('api/v1')
            ->group($apiRoutes);
    }

    private function registerListeners(): void
    {
        if ($this->listen === []) {
            return;
        }

        $dispatcher = $this->app->make(Dispatcher::class);
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $dispatcher->listen($event, $listener);
            }
        }
    }
}
