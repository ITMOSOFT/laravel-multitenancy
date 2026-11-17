<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ItmoSoft\Multitenancy\MultitenancyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'testing',
            '--path' => realpath(__DIR__.'/../database/migrations'),
            '--realpath' => true,
            '--force' => true,
        ]);

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app): array
    {
        return [MultitenancyServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('itmosoft-multitenancy.table_prefix', 'itmosoft_');
        $app['config']->set('itmosoft-multitenancy.tenant.table', 'tenants');
    }

}

