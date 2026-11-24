<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Database\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ItmoSoft\Multitenancy\Context\TenantContext;
use ItmoSoft\Multitenancy\Database\Scopes\TenantScope;
use ItmoSoft\Multitenancy\Exceptions\MissingTenantContextException;
use ItmoSoft\Multitenancy\Models\Tenant;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model): void {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null) {
                throw MissingTenantContextException::forModel($model::class);
            }

            $column = config('itmosoft-multitenancy.tenant_scope.column', 'tenant_id');

            // Never trust a caller-supplied tenant_id on tenant-owned records.
            $model->setAttribute($column, $tenantId);
        });

        static::updating(function ($model): void {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null) {
                throw MissingTenantContextException::forModel($model::class);
            }

            $column = config('itmosoft-multitenancy.tenant_scope.column', 'tenant_id');

            // Prevent normal Eloquent model updates from transferring a row
            // into another tenant by changing its tenant foreign key.
            $model->setAttribute($column, $tenantId);
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            config('itmosoft-multitenancy.tenant.model', Tenant::class),
            config('itmosoft-multitenancy.tenant_scope.column', 'tenant_id')
        );
    }

    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
