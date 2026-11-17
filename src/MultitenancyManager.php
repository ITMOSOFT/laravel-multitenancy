<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy;

use Closure;
use ItmoSoft\Multitenancy\Context\TenantContext;
use ItmoSoft\Multitenancy\Models\Tenant;

class MultitenancyManager
{
    public function __construct(
        protected TenantContext $context,
    ) {
    }

    public function current(): ?Tenant
    {
        return $this->context->current();
    }

    public function id(): int|string|null
    {
        return $this->context->id();
    }

    public function run(Tenant $tenant, Closure $callback): mixed
    {
        return $this->context->run($tenant, $callback);
    }

    public function clear(): void
    {
        $this->context->clear();
    }
}
