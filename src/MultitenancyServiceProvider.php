<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy;

use Illuminate\Support\ServiceProvider;
use ItmoSoft\Multitenancy\Context\TenantContext;
use ItmoSoft\Multitenancy\Contracts\TenantResolver;
use ItmoSoft\Multitenancy\Http\Middleware\ResolveTenant;
use ItmoSoft\Multitenancy\Resolvers\DomainTenantResolver;

class MultitenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/itmosoft-multitenancy.php',
            'itmosoft-multitenancy'
        );

        $this->app->scoped(TenantContext::class, fn () => new TenantContext());

        $this->app->bind(TenantResolver::class, function ($app) {
            $resolver = config(
                'itmosoft-multitenancy.resolver',
                DomainTenantResolver::class
            );

            return $app->make($resolver);
        });

        $this->app->scoped(MultitenancyManager::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/itmosoft-multitenancy.php' => config_path('itmosoft-multitenancy.php'),
        ], 'itmosoft-multitenancy-config');

        if ($this->app->bound('router')) {
            $this->app['router']->aliasMiddleware('tenant', ResolveTenant::class);
        }
    }
}
