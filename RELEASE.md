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

## Publish repository

Create the GitHub repository manually, then run:

```bash
git init
git branch -M main
git add .
git commit -m "chore: initial Laravel multitenancy package"
git remote add origin https://github.com/ITMOSOFT/laravel-multitenancy.git
git push -u origin main
```

## Release

Only after local PHPUnit and all GitHub Actions jobs pass:

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

GitHub Release title:

`v1.0.0 - Initial Release`

Release description:

```markdown
## Initial release

- Domain and subdomain tenant resolution.
- Scoped tenant request context with safe restoration.
- Shared-database Eloquent tenant scope and automatic tenant assignment.
- Tenant-aware queue middleware contract.
- Tenant cache-key and storage-path namespace helpers.
- Configurable tenant table and migration.
- Laravel 12 and 13 compatibility.

### Important scope

This release provides shared-database tenancy primitives. Database-per-tenant connection switching and tenant database provisioning are not included.
```

## Packagist

Submit `https://github.com/ITMOSOFT/laravel-multitenancy` at https://packagist.org/packages/submit, then enable the GitHub webhook/update integration.

## Repository hygiene

Do not commit `vendor/`, `.phpunit.cache/`, `.phpunit.result.cache`, `.env`, or other local artifacts. The `.gitignore` in this package excludes them.
