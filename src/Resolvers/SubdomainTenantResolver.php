<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Resolvers;

use Illuminate\Http\Request;
use ItmoSoft\Multitenancy\Contracts\TenantResolver;
use ItmoSoft\Multitenancy\Models\Tenant;

class SubdomainTenantResolver implements TenantResolver
{
    public function resolve(Request $request): ?Tenant
    {
        $baseDomain = strtolower(rtrim((string) config('itmosoft-multitenancy.subdomains.base_domain'), '.'));

        if ($baseDomain === '') {
            return null;
        }

        $host = strtolower(rtrim($request->getHost(), '.'));
        $suffix = '.'.$baseDomain;

        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $subdomain = substr($host, 0, -strlen($suffix));

        // Only a single tenant label is supported by this resolver.
        if ($subdomain === '' || str_contains($subdomain, '.')) {
            return null;
        }

        $reserved = array_map(
            static fn ($value) => strtolower((string) $value),
            config('itmosoft-multitenancy.subdomains.reserved', [])
        );

        if (in_array($subdomain, $reserved, true)) {
            return null;
        }

        $model = config('itmosoft-multitenancy.tenant.model', Tenant::class);

        /** @var Tenant|null $tenant */
        $tenant = $model::query()
            ->where('slug', $subdomain)
            ->where('status', 'active')
            ->first();

        return $tenant;
    }
}
