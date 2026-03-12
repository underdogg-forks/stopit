# Multi-Tenancy Implementation Guide

## Overview

Stopit implements subdomain-based multi-tenancy using the `tenancy/tenancy` package. Each workspace (account) gets its own subdomain, providing isolated environments with proper tenant identification and data scoping.

## Architecture

### Subdomain Routing

- **Main Domain**: `stopit.dev` - Central dashboard
- **Tenant Subdomains**: `{workspace}.stopit.dev` - Individual workspace instances
- **Example**: `gitman.stopit.dev`, `spotivel.stopit.dev`

### Key Components

1. **tenancy/tenancy Package**
   - Handles tenant identification via subdomain
   - Provides tenant resolution and context management
   - Manages tenant-aware database queries

2. **Account Model** (`Modules/Stopit/src/Models/Account.php`)
   - Implements `Tenancy\Identification\Contracts\Tenant` interface
   - Provides `getTenantIdentifier()` and `getTenantKey()` methods
   - Stores tenant domain in `domain` column

3. **Database Schema**
   ```sql
   accounts table:
   - id
   - name
   - slug
   - domain (unique, subdomain identifier)
   - is_active (boolean)
   - timestamps
   ```

## Configuration

### Environment Variables (.env)

```env
APP_BASE_DOMAIN=stopit.dev
SESSION_DRIVER=database
SESSION_DOMAIN=.stopit.dev
TENANT_CENTRAL_DOMAIN=stopit.dev
```

### Tenancy Configuration (config/tenancy.php)

```php
return [
    'tenant_model' => \Modules\Stopit\Models\Account::class,
    'identification_driver' => 'subdomain',
    'central_domains' => [
        env('TENANT_CENTRAL_DOMAIN', 'stopit.dev'),
    ],
    'tenant_column' => 'domain',
];
```

## Installation

### 1. Install the Package

```bash
composer require tenancy/tenancy
```

### 2. Register Service Provider

The `TenancyServiceProvider` is automatically registered in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TenancyServiceProvider::class,
];
```

### 3. Database Migration

Run the migration to add multi-tenancy columns to accounts:

```bash
php artisan migrate
```

## Usage

### Accessing Tenant Context

```php
use Tenancy\Facades\Tenancy;

// Get current tenant
$tenant = Tenancy::getTenant();

// Check if a tenant is active
if (Tenancy::isActive()) {
    // Tenant-specific logic
}

// Get tenant property
$domain = $tenant->getTenantIdentifier();
```

### Tenant-Aware Queries

The package automatically scopes queries based on the current tenant context. Your models should use the tenant-aware traits provided by the package.

### Testing

When testing multi-tenant features:

```php
use Tenancy\Facades\Tenancy;

public function test_tenant_specific_feature()
{
    $tenant = Account::factory()->create([
        'domain' => 'test-tenant',
    ]);
    
    Tenancy::setTenant($tenant);
    
    // Your test logic here
    
    Tenancy::clearTenant();
}
```

## Deployment

### DNS Configuration

Configure wildcard DNS for your domain:

```
A    @           -> Your-Server-IP
A    *.stopit.dev -> Your-Server-IP
```

### Web Server Configuration

#### Nginx

```nginx
server {
    listen 80;
    server_name stopit.dev *.stopit.dev;
    
    root /var/www/stopit/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Apache

```apache
<VirtualHost *:80>
    ServerName stopit.dev
    ServerAlias *.stopit.dev
    
    DocumentRoot /var/www/stopit/public
    
    <Directory /var/www/stopit/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### SSL/TLS

For wildcard SSL certificates:

```bash
# Using Let's Encrypt with Certbot
certbot --nginx -d stopit.dev -d *.stopit.dev
```

## Troubleshooting

### Tenant Not Identified

**Issue**: Subdomain not properly identifying tenant

**Solution**:
1. Check DNS configuration
2. Verify `TENANT_CENTRAL_DOMAIN` in `.env`
3. Ensure `domain` column is populated in accounts table
4. Check web server configuration for wildcard support

### Session Issues

**Issue**: Session not persisting across subdomains

**Solution**:
1. Set `SESSION_DRIVER=database` in `.env`
2. Set `SESSION_DOMAIN=.stopit.dev` (note the leading dot)
3. Run `php artisan session:table` and `php artisan migrate`

### Cross-Tenant Data Leaks

**Issue**: Data from one tenant visible in another

**Solution**:
1. Ensure all models use tenant-aware traits
2. Check that tenant context is set before queries
3. Review Filament resource queries for proper scoping

## API Integration

When making API requests to tenant-specific endpoints:

```bash
# Include subdomain in the URL
curl https://gitman.stopit.dev/api/v1/exceptions \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message": "Error message"}'
```

## Security Considerations

1. **Tenant Isolation**: The tenancy package ensures data isolation at the query level
2. **Access Control**: Users must have explicit access to each tenant
3. **API Tokens**: Tokens are scoped to applications which belong to specific accounts
4. **Session Security**: Cross-subdomain sessions use secure, httpOnly cookies

## Additional Resources

- [tenancy/tenancy Documentation](https://tenancy.dev/)
- [Laravel Multi-Tenancy Guide](https://laravel.com/docs/multi-tenancy)
- [Subdomain Routing in Laravel](https://laravel.com/docs/routing#route-group-subdomain-routing)
