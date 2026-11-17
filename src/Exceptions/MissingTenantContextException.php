<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Exceptions;

use RuntimeException;

class MissingTenantContextException extends RuntimeException
{
    public static function forModel(string $model): self
    {
        return new self("A tenant context is required to query or create [{$model}].");
    }
}
