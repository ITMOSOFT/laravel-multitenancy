# Changelog

All notable changes to this project are documented here.

## [Unreleased]

### Fixed
- Queue tenant context now resolves the configured custom tenant model and only loads active tenants.
- Missing tenant context with `require_context=false` now returns no rows instead of silently removing tenant filtering.
- Excluded and unresolved central-host requests execute without inheriting an existing tenant context.
- Normal Eloquent model updates cannot reassign a tenant-owned record to another tenant.
- Host and subdomain matching normalizes case and trailing dots; reserved subdomains are compared case-insensitively.
- Tenant storage paths reject control characters and cache keys reject empty identifiers.

### Tests
- Added queue middleware, custom tenant model, tenant reassignment, middleware restoration, fail-closed scope, host normalization, and path validation coverage.

## [1.0.0] - 2026-11-17

### Added
- Tenant model and configurable tenant table migration.
- Domain and subdomain tenant resolvers.
- Scoped request tenant context with restoration.
- Middleware alias for tenant resolution.
- Explicit Eloquent tenant scope and automatic tenant assignment trait.
- Tenant-aware queue middleware contract.
- Tenant cache-key and storage-path namespace helpers.
- PHPUnit tests and Laravel 12/13 CI workflow.
