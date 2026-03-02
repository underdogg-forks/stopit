<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Central Domain
    |--------------------------------------------------------------------------
    |
    | This is the main domain for your application (without subdomain).
    | Tenant subdomains will be appended to this domain.
    | Example: stopit.dev -> gitman.stopit.dev, spotivel.stopit.dev
    |
    */
    'central_domain' => env('TENANT_CENTRAL_DOMAIN', 'stopit.dev'),
    
    /*
    |--------------------------------------------------------------------------
    | Base Domain for URL Generation
    |--------------------------------------------------------------------------
    |
    | Used for generating tenant URLs in the application.
    |
    */
    'base_domain' => env('APP_BASE_DOMAIN', 'stopit.dev'),
    
    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Session domain should use a leading dot to share sessions across subdomains.
    | Example: .stopit.dev
    |
    */
    'session_domain' => env('SESSION_DOMAIN', '.stopit.dev'),
    
    /*
    |--------------------------------------------------------------------------
    | Tenant Identification
    |--------------------------------------------------------------------------
    |
    | Configure how tenants are identified in the application.
    |
    */
    'identification' => [
        'method' => 'subdomain', // subdomain, path, or header
        'model' => \Modules\Stopit\Models\Account::class,
        'column' => 'domain',
    ],
];
