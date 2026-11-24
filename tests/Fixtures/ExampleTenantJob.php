<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Tests\Fixtures;

use ItmoSoft\Multitenancy\Contracts\TenantAwareJob;

class ExampleTenantJob implements TenantAwareJob
{
    public function __construct(
        protected int|string $tenantKey,
    ) {
    }

    public function tenantId(): int|string
    {
        return $this->tenantKey;
    }
}
