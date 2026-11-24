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
use ItmoSoft\Multitenancy\Tests\Fixtures\CustomTenant;
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

    public function test_domain_resolver_normalizes_host_case_and_trailing_dot(): void
    {
        $tenant = $this->tenant('acme', 'acme.test');

        $resolved = app(DomainTenantResolver::class)->resolve(
            Request::create('https://ACME.TEST./')
        );

        $this->assertSame($tenant->id, $resolved?->id);
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

    public function test_subdomain_resolver_rejects_reserved_subdomains_case_insensitively(): void
    {
        config([
            'itmosoft-multitenancy.subdomains.base_domain' => 'example.test',
            'itmosoft-multitenancy.subdomains.reserved' => ['WWW', 'API'],
        ]);

        $resolved = app(SubdomainTenantResolver::class)->resolve(
            Request::create('https://www.example.test/')
        );

        $this->assertNull($resolved);
    }

    public function test_subdomain_resolver_rejects_hosts_outside_base_domain(): void
    {
        config(['itmosoft-multitenancy.subdomains.base_domain' => 'example.test']);

        $resolved = app(SubdomainTenantResolver::class)->resolve(
            Request::create('https://acme.notexample.test/')
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

    public function test_run_without_tenant_clears_context_during_callback_and_restores_afterward(): void
    {
        $tenant = $this->tenant('first', 'first.test');
        $context = app(TenantContext::class);
        $context->set($tenant);

        $context->runWithoutTenant(function () use ($context): void {
            $this->assertNull($context->current());
        });

        $this->assertSame($tenant->id, $context->id());
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

    public function test_create_overwrites_caller_supplied_tenant_id(): void
    {
        $first = $this->tenant('first', 'first.test');
        $second = $this->tenant('second', 'second.test');
        app(TenantContext::class)->set($first);

        $project = Project::query()->create([
            'name' => 'Protected project',
            'tenant_id' => $second->id,
        ]);

        $this->assertSame($first->id, $project->tenant_id);
    }

    public function test_model_update_cannot_reassign_record_to_another_tenant(): void
    {
        $first = $this->tenant('first', 'first.test');
        $second = $this->tenant('second', 'second.test');
        app(TenantContext::class)->set($first);

        $project = Project::query()->create(['name' => 'Project']);

        $project->tenant_id = $second->id;
        $project->name = 'Updated project';
        $project->save();

        $this->assertSame($first->id, $project->fresh()->tenant_id);
    }

    public function test_tenant_scoped_query_fails_closed_without_context(): void
    {
        $this->expectException(MissingTenantContextException::class);

        Project::query()->count();
    }

    public function test_query_returns_no_rows_without_context_when_exception_is_disabled(): void
    {
        $tenant = $this->tenant('acme', 'acme.test');
        app(TenantContext::class)->set($tenant);
        Project::query()->create(['name' => 'Tenant project']);

        app(TenantContext::class)->clear();
        config(['itmosoft-multitenancy.tenant_scope.require_context' => false]);

        $this->assertSame(0, Project::query()->count());
    }

    public function test_tenant_relation_uses_configured_tenant_model(): void
    {
        config(['itmosoft-multitenancy.tenant.model' => CustomTenant::class]);

        $tenant = CustomTenant::query()->create([
            'name' => 'Custom',
            'slug' => 'custom',
            'domain' => 'custom.test',
            'status' => 'active',
        ]);
        app(TenantContext::class)->set($tenant);

        $project = Project::query()->create(['name' => 'Custom tenant project']);

        $this->assertInstanceOf(CustomTenant::class, $project->tenant);
    }

    public function test_tenant_cache_key_is_namespaced(): void
    {
        $tenant = $this->tenant('acme', 'acme.test');
        app(TenantContext::class)->set($tenant);

        $this->assertSame('tenant.'.$tenant->id.'.dashboard.stats', app(TenantCacheKey::class)->make('dashboard.stats'));
    }

    public function test_tenant_cache_key_rejects_empty_key(): void
    {
        $tenant = $this->tenant('acme', 'acme.test');
        app(TenantContext::class)->set($tenant);

        $this->expectException(\InvalidArgumentException::class);
        app(TenantCacheKey::class)->make('...');
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

    public function test_tenant_storage_path_rejects_control_characters(): void
    {
        $tenant = $this->tenant('acme', 'acme.test');
        app(TenantContext::class)->set($tenant);

        $this->expectException(\InvalidArgumentException::class);
        app(TenantStoragePath::class)->make("uploads/\0avatar.png");
    }
}
