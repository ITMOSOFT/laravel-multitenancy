<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function getTable(): string
    {
        return config('itmosoft-multitenancy.table_prefix', 'itmosoft_')
            . config('itmosoft-multitenancy.tenant.table', 'tenants');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
