<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Support;

use InvalidArgumentException;
use ItmoSoft\Multitenancy\Context\TenantContext;

class TenantStoragePath
{
    public function __construct(
        protected TenantContext $context,
    ) {
    }

    public function make(string $path = ''): string
    {
        $tenantId = $this->context->id();

        if ($tenantId === null) {
            throw new InvalidArgumentException('Cannot create a tenant storage path without a tenant context.');
        }

        $clean = str_replace('\\', '/', trim($path));
        $segments = array_filter(explode('/', $clean), static fn ($segment) => $segment !== '' && $segment !== '.');

        if (in_array('..', $segments, true)) {
            throw new InvalidArgumentException('Tenant storage paths cannot traverse parent directories.');
        }

        $prefix = trim((string) config('itmosoft-multitenancy.storage.prefix', 'tenants'), '/');

        return implode('/', array_merge([$prefix, (string) $tenantId], $segments));
    }
}
