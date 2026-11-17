<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function tableName(): string
    {
        return config('itmosoft-multitenancy.table_prefix', 'itmosoft_')
            . config('itmosoft-multitenancy.tenant.table', 'tenants');
    }

    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('status', 24)->default('active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName());
    }
};
