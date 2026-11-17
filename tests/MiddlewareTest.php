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
