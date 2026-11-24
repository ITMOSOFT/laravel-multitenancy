<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Tests;

use Illuminate\Http\Request;
use ItmoSoft\Multitenancy\Context\TenantContext;
use ItmoSoft\Multitenancy\Http\Middleware\ResolveTenant;
use ItmoSoft\Multitenancy\Models\Tenant;
use ItmoSoft\Multitenancy\Resolvers\DomainTenantResolver;
use Symfony\Component\HttpFoundation\Response;

class MiddlewareTest extends TestCase
{
    public function test_middleware_sets_context_for_request_and_clears_afterward(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'domain' => 'acme.test',
            'status' => 'active',
        ]);

        $context = app(TenantContext::class);
        $middleware = new ResolveTenant(app(DomainTenantResolver::class), $context);
        $request = Request::create('https://acme.test/dashboard');

        $response = $middleware->handle($request, function () use ($context, $tenant): Response {
            $this->assertSame($tenant->id, $context->id());

            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
        $this->assertNull($context->current());
    }

    public function test_middleware_restores_context_when_downstream_throws(): void
    {
        $previous = Tenant::query()->create([
            'name' => 'Previous',
            'slug' => 'previous',
            'domain' => 'previous.test',
            'status' => 'active',
        ]);
        $current = Tenant::query()->create([
            'name' => 'Current',
            'slug' => 'current',
            'domain' => 'current.test',
            'status' => 'active',
        ]);

        $context = app(TenantContext::class);
        $context->set($previous);
        $middleware = new ResolveTenant(app(DomainTenantResolver::class), $context);

        try {
            $middleware->handle(
                Request::create('https://current.test/'),
                function () use ($context, $current): Response {
                    $this->assertSame($current->id, $context->id());
                    throw new \RuntimeException('downstream failure');
                }
            );
        } catch (\RuntimeException) {
            // Expected.
        }

        $this->assertSame($previous->id, $context->id());
    }

    public function test_excluded_host_runs_without_tenant_context(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Previous',
            'slug' => 'previous',
            'domain' => 'previous.test',
            'status' => 'active',
        ]);
        $context = app(TenantContext::class);
        $context->set($tenant);

        $middleware = new ResolveTenant(app(DomainTenantResolver::class), $context);
        $middleware->handle(
            Request::create('http://localhost/'),
            function () use ($context): Response {
                $this->assertNull($context->current());

                return new Response('central');
            }
        );

        $this->assertSame($tenant->id, $context->id());
    }

    public function test_allow_unresolved_runs_without_tenant_context(): void
    {
        config(['itmosoft-multitenancy.middleware.allow_unresolved' => true]);

        $tenant = Tenant::query()->create([
            'name' => 'Previous',
            'slug' => 'previous',
            'domain' => 'previous.test',
            'status' => 'active',
        ]);
        $context = app(TenantContext::class);
        $context->set($tenant);

        $middleware = new ResolveTenant(app(DomainTenantResolver::class), $context);
        $middleware->handle(
            Request::create('https://unknown.test/'),
            function () use ($context): Response {
                $this->assertNull($context->current());

                return new Response('central');
            }
        );

        $this->assertSame($tenant->id, $context->id());
    }

    public function test_middleware_returns_404_for_unresolved_tenant(): void
    {
        $middleware = new ResolveTenant(
            app(DomainTenantResolver::class),
            app(TenantContext::class)
        );

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle(
            Request::create('https://unknown.test/'),
            fn () => new Response('should not run')
        );
    }
}
