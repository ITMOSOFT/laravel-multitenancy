<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Context;

use Closure;
use ItmoSoft\Multitenancy\Models\Tenant;

class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    public function current(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): int|string|null
    {
        return $this->tenant?->getKey();
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Run a callback in a tenant context and always restore the previous context.
     */
    public function run(Tenant $tenant, Closure $callback): mixed
    {
        $previous = $this->tenant;
        $this->tenant = $tenant;

        try {
            return $callback();
        } finally {
            $this->tenant = $previous;
        }
    }

    /**
     * Run a callback without tenant context and always restore the previous context.
     */
    public function runWithoutTenant(Closure $callback): mixed
    {
        $previous = $this->tenant;
        $this->tenant = null;

        try {
            return $callback();
        } finally {
            $this->tenant = $previous;
        }
    }
}
