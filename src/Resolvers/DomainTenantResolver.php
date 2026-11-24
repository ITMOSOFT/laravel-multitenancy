<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Resolvers;

use Illuminate\Http\Request;
use ItmoSoft\Multitenancy\Contracts\TenantResolver;
use ItmoSoft\Multitenancy\Models\Tenant;

class DomainTenantResolver implements TenantResolver
{
    public function resolve(Request $request): ?Tenant
    {
        $model = config('itmosoft-multitenancy.tenant.model', Tenant::class);
        $host = strtolower(rtrim($request->getHost(), '.'));

        /** @var Tenant|null $tenant */
        $tenant = $model::query()
            ->where('domain', $host)
            ->where('status', 'active')
            ->first();

        return $tenant;
    }
}
