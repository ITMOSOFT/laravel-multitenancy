# ITMOSOFT Laravel Multitenancy

Tenant resolution, request context, and shared-database isolation primitives for Laravel 12 and 13.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require itmosoft/laravel-multitenancy
php artisan vendor:publish --tag=itmosoft-multitenancy-config
php artisan migrate
```

The service provider loads the package migration automatically. The default tenant table is `itmosoft_tenants`.

## Create tenants

```php
use ItmoSoft\Multitenancy\Models\Tenant;

$tenant = Tenant::query()->create([
    'name' => 'Acme',
    'slug' => 'acme',
    'domain' => 'acme.example.com',
    'status' => 'active',
    'settings' => ['timezone' => 'UTC'],
]);
```

Tenant statuses are application-managed strings. Only `active` tenants are returned by the built-in request resolvers and queue middleware.

## Resolve tenants

The default resolver maps the request host to `tenants.domain`.

```php
// bootstrap/app.php (Laravel 11+ application middleware configuration)
->withMiddleware(function ($middleware): void {
    $middleware->alias([
        'tenant' => \ItmoSoft\Multitenancy\Http\Middleware\ResolveTenant::class,
    ]);
})
```

Apply `tenant` only to routes that must resolve a tenant. The package also registers the `tenant` middleware alias through its service provider.

For subdomains such as `acme.example.com`, configure:

```php
// config/itmosoft-multitenancy.php
'resolver' => \ItmoSoft\Multitenancy\Resolvers\SubdomainTenantResolver::class,
'subdomains' => [
    'base_domain' => env('TENANCY_BASE_DOMAIN', 'example.com'),
    'reserved' => ['www', 'app', 'api', 'admin'],
],
```

The subdomain resolver expects one tenant label immediately before the configured base domain and looks up that label against `tenants.slug`.

Unresolved hosts return HTTP 404 by default. Add central domains to `middleware.excluded_hosts`, or set `allow_unresolved` only when the surrounding application deliberately handles central domains itself. Excluded and allowed-unresolved requests execute with no active tenant context.

## Access the current tenant

```php
use ItmoSoft\Multitenancy\MultitenancyManager;

$tenancy = app(MultitenancyManager::class);

$tenant = $tenancy->current();
$tenantId = $tenancy->id();
```

You can also run a callback in a tenant context. The previous context is restored even if the callback throws:

```php
$tenancy->run($tenant, function () {
    // Tenant context is active here.
});
```

## Scope Eloquent models

Add `BelongsToTenant` to each tenant-owned model and add the configured tenant key column (default `tenant_id`) to its table:

```php
use Illuminate\Database\Eloquent\Model;
use ItmoSoft\Multitenancy\Database\Concerns\BelongsToTenant;

class Project extends Model
{
    use BelongsToTenant;

    protected $fillable = ['name'];
}
```

The trait:
- Automatically assigns `tenant_id` on create, overriding any caller-supplied tenant ID.
- Applies a global scope to reads, updates and deletes.
- Fails closed when a tenant context is missing by default.
- If `tenant_scope.require_context` is false, missing context returns zero rows rather than an unscoped result.
- Prevents ordinary Eloquent model updates from changing the tenant foreign key.
- Provides `tenant()` and `withoutTenantScope()`.

For carefully controlled cross-tenant maintenance work, use `withoutTenantScope()` inside an explicitly authorized system operation. Never expose unscoped queries to tenant-facing requests. Avoid mass-updating the tenant foreign key; Eloquent mass updates do not fire per-model events.

## Custom tenant model

Set `tenant.model` to a class extending the package `Tenant` model. Domain/subdomain resolution, tenant relationships, and queue context loading use this configured model.

## Cache and storage namespacing

```php
$cacheKey = app(\ItmoSoft\Multitenancy\Support\TenantCacheKey::class)
    ->make('dashboard.metrics');

$path = app(\ItmoSoft\Multitenancy\Support\TenantStoragePath::class)
    ->make('uploads/logo.png');
```

These helpers generate tenant-specific names/paths. Storage paths reject parent traversal and control characters. They do not automatically change Laravel's cache store, filesystem disk, or authorization policies.

## Queue jobs

Implement `TenantAwareJob` and attach `SetTenantContext` to the job's middleware:

```php
use ItmoSoft\Multitenancy\Contracts\TenantAwareJob;
use ItmoSoft\Multitenancy\Queue\Middleware\SetTenantContext;

class RebuildTenantReport implements TenantAwareJob
{
    public function __construct(public int|string $tenantKey) {}

    public function tenantId(): int|string
    {
        return $this->tenantKey;
    }

    public function middleware(): array
    {
        return [new SetTenantContext()];
    }

    public function handle(): void
    {
        // Tenant context is active for this job.
    }
}
```

The middleware reloads the tenant by ID using the configured tenant model, requires the tenant to be active, and restores the prior context after execution.

## Configuration

- `table_prefix`: prefix for the tenant table.
- `tenant.table`: tenant table suffix.
- `tenant.model`: optional tenant model class extending the package `Tenant` model.
- `resolver`: resolver class implementing `TenantResolver`.
- `subdomains.base_domain`: base domain for subdomain resolution.
- `middleware.excluded_hosts`: hosts that bypass resolution without inheriting an active tenant context.
- `middleware.allow_unresolved`: allow unresolved requests to continue without tenant context (default false).
- `tenant_scope.column`: tenant foreign-key column (default `tenant_id`).
- `tenant_scope.require_context`: throw when a scoped model is queried without context (default true); false returns no rows.
- `cache.prefix` and `storage.prefix`: helper namespaces.

## Security and architecture boundaries

This package implements shared-database, row-scoped tenancy primitives. It does not implement database-per-tenant connection switching, tenant database provisioning, tenant migrations, central-domain routing, tenant signup/billing, admin UI, KYC, or authorization policies.

- Tenant isolation is only applied to models that explicitly use `BelongsToTenant`.
- Add the tenant key and appropriate indexes/constraints to every tenant-owned table.
- Do not trust a tenant ID supplied by the client. Resolve tenant identity from a verified host or trusted application context.
- Review host/proxy configuration to prevent Host-header spoofing. Configure trusted proxies correctly.
- Use HTTPS, secure cookies, and tenant-aware authorization policies.
- Cache/storage helpers namespace identifiers; the host application remains responsible for access control.
- The queue middleware must be attached to every tenant-aware job that needs tenant context.
- Test isolation on the production database engine. SQLite tests do not prove production row-locking or database behavior.
- Cross-tenant administrative operations must be explicit, authorized, logged and reviewed.
- Do not use unscoped operations or mass tenant-key updates in tenant-facing code.

## Tests

```bash
composer validate --strict
composer install
composer test
```

## License

MIT.
