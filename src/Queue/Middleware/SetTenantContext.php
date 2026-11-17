<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Queue\Middleware;

use Closure;
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

        $tenant = Tenant::query()->findOrFail($job->tenantId());

        return app(TenantContext::class)->run($tenant, fn () => $next($job));
    }
}
