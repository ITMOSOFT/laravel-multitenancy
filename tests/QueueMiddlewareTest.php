<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Tests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use ItmoSoft\Multitenancy\Context\TenantContext;
use ItmoSoft\Multitenancy\Models\Tenant;
use ItmoSoft\Multitenancy\Queue\Middleware\SetTenantContext;
use ItmoSoft\Multitenancy\Tests\Fixtures\CustomTenant;
use ItmoSoft\Multitenancy\Tests\Fixtures\ExampleTenantJob;

class QueueMiddlewareTest extends TestCase
{
    private function tenant(string $slug, string $status = 'active'): Tenant
    {
        return Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => $status,
        ]);
    }

    public function test_queue_middleware_sets_tenant_context_for_job_and_restores_it(): void
    {
        $previous = $this->tenant('previous');
        $tenant = $this->tenant('worker');
        $context = app(TenantContext::class);
        $context->set($previous);

        $job = new ExampleTenantJob($tenant->id);
        $result = (new SetTenantContext())->handle($job, function () use ($context, $tenant): string {
            $this->assertSame($tenant->id, $context->id());

            return 'processed';
        });

        $this->assertSame('processed', $result);
        $this->assertSame($previous->id, $context->id());
    }

    public function test_queue_middleware_restores_context_when_job_throws(): void
    {
        $previous = $this->tenant('previous');
        $tenant = $this->tenant('worker');
        $context = app(TenantContext::class);
        $context->set($previous);

        try {
            (new SetTenantContext())->handle(
                new ExampleTenantJob($tenant->id),
                function (): never {
                    throw new \RuntimeException('job failed');
                }
            );
        } catch (\RuntimeException) {
            // Expected.
        }

        $this->assertSame($previous->id, $context->id());
    }

    public function test_queue_middleware_uses_configured_custom_tenant_model(): void
    {
        config(['itmosoft-multitenancy.tenant.model' => CustomTenant::class]);

        $tenant = CustomTenant::query()->create([
            'name' => 'Custom',
            'slug' => 'custom',
            'domain' => 'custom.test',
            'status' => 'active',
        ]);

        (new SetTenantContext())->handle(
            new ExampleTenantJob($tenant->id),
            function () use ($tenant): void {
                $this->assertInstanceOf(CustomTenant::class, app(TenantContext::class)->current());
                $this->assertSame($tenant->id, app(TenantContext::class)->id());
            }
        );
    }

    public function test_queue_middleware_rejects_inactive_tenants(): void
    {
        $tenant = $this->tenant('suspended', 'suspended');

        $this->expectException(ModelNotFoundException::class);

        (new SetTenantContext())->handle(
            new ExampleTenantJob($tenant->id),
            fn () => null
        );
    }

    public function test_non_tenant_aware_job_runs_without_changing_context(): void
    {
        $tenant = $this->tenant('current');
        $context = app(TenantContext::class);
        $context->set($tenant);
        $job = new \stdClass();

        $result = (new SetTenantContext())->handle($job, fn () => 'ok');

        $this->assertSame('ok', $result);
        $this->assertSame($tenant->id, $context->id());
    }
}
