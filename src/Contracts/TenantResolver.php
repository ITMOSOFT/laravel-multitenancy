<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Contracts;

use Illuminate\Http\Request;
use ItmoSoft\Multitenancy\Models\Tenant;

interface TenantResolver
{
    public function resolve(Request $request): ?Tenant;
}
