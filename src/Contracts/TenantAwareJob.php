<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Contracts;

interface TenantAwareJob
{
    public function tenantId(): int|string;
}
