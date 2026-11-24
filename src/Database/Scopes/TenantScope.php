<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Database\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use ItmoSoft\Multitenancy\Context\TenantContext;
use ItmoSoft\Multitenancy\Exceptions\MissingTenantContextException;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);
        $tenantId = $context->id();

        if ($tenantId === null) {
            if (config('itmosoft-multitenancy.tenant_scope.require_context', true)) {
                throw MissingTenantContextException::forModel($model::class);
            }

            // Disabling the exception must never turn a tenant-owned query into
            // an unscoped query. No context means no visible tenant rows.
            $builder->whereRaw('1 = 0');

            return;
        }

        $column = config('itmosoft-multitenancy.tenant_scope.column', 'tenant_id');
        $builder->where($model->qualifyColumn($column), $tenantId);
    }
}
