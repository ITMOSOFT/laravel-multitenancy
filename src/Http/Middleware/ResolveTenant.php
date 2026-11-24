<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ItmoSoft\Multitenancy\Context\TenantContext;
use ItmoSoft\Multitenancy\Contracts\TenantResolver;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        protected TenantResolver $resolver,
        protected TenantContext $context,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower(rtrim($request->getHost(), '.'));
        $excludedHosts = array_map(
            static fn ($value) => strtolower(rtrim((string) $value, '.')),
            config('itmosoft-multitenancy.middleware.excluded_hosts', [])
        );

        if (in_array($host, $excludedHosts, true)) {
            return $this->context->runWithoutTenant(fn () => $next($request));
        }

        $tenant = $this->resolver->resolve($request);

        if (! $tenant) {
            if (config('itmosoft-multitenancy.middleware.allow_unresolved', false)) {
                return $this->context->runWithoutTenant(fn () => $next($request));
            }

            abort((int) config('itmosoft-multitenancy.tenant.not_found_status', 404));
        }

        return $this->context->run($tenant, fn () => $next($request));
    }
}
