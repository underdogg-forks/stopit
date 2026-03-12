<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration uses the tenancy/tenancy package for Laravel.
    | Tenants are identified by subdomain and stored in the Account model.
    |
    */

    'tenant_model' => \Modules\Stopit\Models\Account::class,
    
    /*
    |--------------------------------------------------------------------------
    | Identification Driver
    |--------------------------------------------------------------------------
    |
    | The driver used to identify tenants. For subdomain-based tenancy,
    | use 'subdomain'.
    |
    */
    'identification_driver' => 'subdomain',
    
    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    |
    | Domains that should be treated as the central application domain,
    | not tenant-specific domains.
    |
    */
    'central_domains' => [
        env('TENANT_CENTRAL_DOMAIN', 'stopit.dev'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Tenant Column
    |--------------------------------------------------------------------------
    |
    | The column name in the tenant model that stores the identifier.
    |
    */
    'tenant_column' => 'domain',
];
