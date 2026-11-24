<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Support;

use InvalidArgumentException;
use ItmoSoft\Multitenancy\Context\TenantContext;

class TenantCacheKey
{
    public function __construct(
        protected TenantContext $context,
    ) {
    }

    public function make(string $key): string
    {
        $tenantId = $this->context->id();

        if ($tenantId === null) {
            throw new InvalidArgumentException('Cannot create a tenant cache key without a tenant context.');
        }

        $key = ltrim($key, '.');

        if ($key === '') {
            throw new InvalidArgumentException('A tenant cache key cannot be empty.');
        }

        $prefix = trim((string) config('itmosoft-multitenancy.cache.prefix', 'tenant'), '.');

        return $prefix.'.'.$tenantId.'.'.$key;
    }
}
