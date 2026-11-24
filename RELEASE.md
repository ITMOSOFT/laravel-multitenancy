# Release instructions

## Repository

- Repository: `ITMOSOFT/laravel-multitenancy`
- Composer package: `itmosoft/laravel-multitenancy`
- Description: `Tenant resolution, request context, and shared-database isolation primitives for Laravel applications.`

## Local validation

```bash
composer validate --strict
composer install
composer test
```

Run the full GitHub Actions matrix before releasing:
- PHP 8.3 / Laravel 12
- PHP 8.4 / Laravel 12
- PHP 8.3 / Laravel 13
- PHP 8.4 / Laravel 13

## Recommended patch release

The reviewed repository baseline is v1.0.0. If this patch is merged and all tests pass, use v1.0.1.

Suggested local Git commands (run these yourself after reviewing the diff):

```bash
git status
git diff
git add .
git commit -m "fix: harden tenant context and isolation"
git push origin main

git tag -a v1.0.1 -m "Release v1.0.1"
git push origin v1.0.1
```

GitHub Release title:

`v1.0.1 — Tenant Context and Isolation Hardening`

Release description:

```markdown
## Fixes
- Resolve queue tenant context through the configured tenant model.
- Require active tenants for tenant-aware queue jobs.
- Keep tenant queries fail-closed when context is absent.
- Prevent ordinary Eloquent model updates from changing tenant ownership.
- Clear tenant context during excluded and unresolved host requests.
- Normalize host/subdomain comparisons.
- Reject control characters in tenant storage paths and empty cache keys.

## Tests
- Expanded coverage for queue middleware, custom tenant models, tenant reassignment, request context restoration, and path validation.
- CI matrix: PHP 8.3/8.4 with Laravel 12/13.
```

## Packagist

After publishing the GitHub tag, confirm Packagist detects v1.0.1. Do not publish until local PHPUnit and all four GitHub Actions jobs pass.

## Repository hygiene

Do not commit `vendor/`, `.phpunit.cache/`, `.phpunit.result.cache`, `.env`, or other local artifacts.
