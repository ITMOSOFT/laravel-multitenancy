<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Queue\Middleware;

use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use ItmoSoft\Multitenancy\Context\TenantContext;
use ItmoSoft\Multitenancy\Contracts\TenantAwareJob;
use ItmoSoft\Multitenancy\Models\Tenant;

class SetTenantContext
{
    public function handle(object $job, Closure $next): mixed
    {
        if (! $job instanceof TenantAwareJob) {
            return $next($job);
        }

        $model = config('itmosoft-multitenancy.tenant.model', Tenant::class);

        if (! is_string($model) || ! is_a($model, Tenant::class, true)) {
            throw new InvalidArgumentException(
                'The configured tenant model must extend '.Tenant::class.'.'
            );
        }

        /** @var Tenant $tenant */
        $tenant = $model::query()
            ->where('status', 'active')
            ->findOrFail($job->tenantId());

        return app(TenantContext::class)->run($tenant, fn () => $next($job));
    }
}
