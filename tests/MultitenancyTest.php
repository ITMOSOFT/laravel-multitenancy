<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use ItmoSoft\Multitenancy\Context\TenantContext;
use ItmoSoft\Multitenancy\Exceptions\MissingTenantContextException;
use ItmoSoft\Multitenancy\Models\Tenant;
use ItmoSoft\Multitenancy\Resolvers\DomainTenantResolver;
use ItmoSoft\Multitenancy\Resolvers\SubdomainTenantResolver;
use ItmoSoft\Multitenancy\Support\TenantCacheKey;
use ItmoSoft\Multitenancy\Support\TenantStoragePath;
use ItmoSoft\Multitenancy\Tests\Fixtures\Project;

class MultitenancyTest extends TestCase
{

    private function tenant(string $slug, string $domain): Tenant
    {
        return Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'domain' => $domain,
            'status' => 'active',
        ]);
    }

    public function test_domain_resolver_finds_active_tenant_by_host(): void
    {
        $tenant = $this->tenant('acme', 'acme.test');

        $resolved = app(DomainTenantResolver::class)->resolve(
            Request::create('https://acme.test/dashboard')
        );

        $this->assertSame($tenant->id, $resolved?->id);
    }

    public function test_domain_resolver_ignores_inactive_tenants(): void
    {
        Tenant::query()->create([
            'name' => 'Inactive',
            'slug' => 'inactive',
            'domain' => 'inactive.test',
            'status' => 'suspended',
        ]);

        $resolved = app(DomainTenantResolver::class)->resolve(
            Request::create('https://inactive.test/')
        );

        $this->assertNull($resolved);
    }

    public function test_subdomain_resolver_resolves_tenant_slug(): void
    {
        $tenant = $this->tenant('acme', 'custom.test');
        config(['itmosoft-multitenancy.subdomains.base_domain' => 'example.test']);

        $resolved = app(SubdomainTenantResolver::class)->resolve(
            Request::create('https://acme.example.test/')
        );

        $this->assertSame($tenant->id, $resolved?->id);
    }

    public function test_subdomain_resolver_rejects_reserved_subdomains(): void
    {
        config(['itmosoft-multitenancy.subdomains.base_domain' => 'example.test']);

        $resolved = app(SubdomainTenantResolver::class)->resolve(
            Request::create('https://www.example.test/')
        );

        $this->assertNull($resolved);
    }

    public function test_context_run_restores_previous_tenant(): void
    {
        $first = $this->tenant('first', 'first.test');
        $second = $this->tenant('second', 'second.test');
        $context = app(TenantContext::class);
        $context->set($first);

        $context->run($second, function () use ($context, $second): void {
            $this->assertSame($second->id, $context->id());
        });

        $this->assertSame($first->id, $context->id());
    }

    public function test_context_is_restored_when_callback_throws(): void
    {
        $first = $this->tenant('first', 'first.test');
        $second = $this->tenant('second', 'second.test');
        $context = app(TenantContext::class);
        $context->set($first);

        try {
            $context->run($second, function (): void {
                throw new \RuntimeException('failure');
            });
        } catch (\RuntimeException) {
            // Expected.
        }

        $this->assertSame($first->id, $context->id());
    }

    public function test_belongs_to_tenant_scopes_queries_and_assigns_tenant_id(): void
    {
        $first = $this->tenant('first', 'first.test');
        $second = $this->tenant('second', 'second.test');
        $context = app(TenantContext::class);

        $context->set($first);
        $project = Project::query()->create(['name' => 'First project']);

        $context->set($second);
        Project::query()->create(['name' => 'Second project']);

        $this->assertSame(['Second project'], Project::query()->pluck('name')->all());

        $context->set($first);
        $this->assertSame(['First project'], Project::query()->pluck('name')->all());
        $this->assertSame($first->id, $project->tenant_id);
    }

    public function test_tenant_scoped_query_fails_closed_without_context(): void
    {
        $this->expectException(MissingTenantContextException::class);

        Project::query()->count();
    }

    public function test_tenant_cache_key_is_namespaced(): void
    {
        $tenant = $this->tenant('acme', 'acme.test');
        app(TenantContext::class)->set($tenant);

        $this->assertSame('tenant.'.$tenant->id.'.dashboard.stats', app(TenantCacheKey::class)->make('dashboard.stats'));
    }

    public function test_tenant_storage_path_is_namespaced_and_rejects_traversal(): void
    {
        $tenant = $this->tenant('acme', 'acme.test');
        app(TenantContext::class)->set($tenant);
        $paths = app(TenantStoragePath::class);

        $this->assertSame('tenants/'.$tenant->id.'/uploads/avatar.png', $paths->make('/uploads/avatar.png'));

        $this->expectException(\InvalidArgumentException::class);
        $paths->make('../outside.txt');
    }
}
