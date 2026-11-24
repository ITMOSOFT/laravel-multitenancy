<?php

declare(strict_types=1);

use ItmoSoft\Multitenancy\Resolvers\DomainTenantResolver;

return [
    'table_prefix' => 'itmosoft_',

    'tenant' => [
        'table' => 'tenants',
        'model' => ItmoSoft\Multitenancy\Models\Tenant::class,
        'not_found_status' => 404,
    ],

    'resolver' => DomainTenantResolver::class,

    'subdomains' => [
        'base_domain' => env('TENANCY_BASE_DOMAIN'),
        'reserved' => ['www', 'app', 'api', 'admin', 'mail', 'static'],
    ],

    'middleware' => [
        'excluded_hosts' => ['localhost', '127.0.0.1'],
        'allow_unresolved' => false,
    ],

    'tenant_scope' => [
        'column' => 'tenant_id',
        'require_context' => true,
    ],

    'cache' => [
        'prefix' => 'tenant',
    ],

    'storage' => [
        'prefix' => 'tenants',
    ],
];
