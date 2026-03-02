# Multi-Tenancy Implementation Guide

## Overview

Stopit implements a complete subdomain-based multi-tenancy system where each workspace (account) gets its own subdomain. This allows organizations to have isolated workspaces with their own branding and access control.

## Architecture

### Subdomain Routing

- **Main Domain**: `stopit.dev` - Central dashboard, tenant selection
- **Tenant Subdomains**: `{workspace}.stopit.dev` - Individual workspace instances
- **Example**: `gitman.stopit.dev`, `spotivel.stopit.dev`

### Key Components

1. **IdentifyTenant Middleware** (`Modules/Stopit/Http/Middleware/IdentifyTenant.php`)
   - Extracts subdomain from request
   - Loads tenant (Account) from database
   - Sets tenant context in session
   - Shares tenant data with views

2. **EnforceTenantAccess Middleware** (`Modules/Stopit/Http/Middleware/EnforceTenantAccess.php`)
   - Validates user has access to current tenant
   - Prevents unauthorized cross-tenant access
   - Logs out users attempting to access unauthorized tenants

3. **TenantSwitcherController** (`Modules/Stopit/Http/Middleware/TenantSwitcherController.php`)
   - Displays available workspaces for user
   - Handles switching between tenants
   - Generates tenant-specific URLs

4. **Database Schema**
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

### Session Configuration

Sessions are shared across subdomains using a leading dot in `SESSION_DOMAIN`. This allows users to switch between tenants without re-authenticating.

## User Workflow

### 1. Navigate to Tenant Subdomain

```
https://gitman.stopit.dev
```

- Middleware identifies tenant from subdomain
- Sets tenant context in session
- Redirects to login if not authenticated

### 2. Login

- User authenticates via Filament login page
- Nord theme with orange accents applied
- EnforceTenantAccess validates user has access

### 3. Work Within Tenant

- All data (applications, exceptions) scoped to tenant
- User menu shows "Switch Workspace" option
- Tenant context persists across requests

### 4. Switch Workspace

- Click "Switch Workspace" in user menu
- See list of available workspaces
- Click workspace to redirect to its subdomain

### 5. Main Domain Access

```
https://stopit.dev
```

- Clears tenant context
- Shows generic dashboard
- Displays all workspaces user has access to

## Testing

### Running Multi-Tenancy Tests

```bash
php artisan test --filter=Tenancy
```

### Test Suites

1. **SubdomainTenancyTest** (6 tests)
   - Subdomain identification
   - Access control
   - Session management
   - Invalid tenant handling

2. **TenantSwitcherTest** (8 tests)
   - Workspace switcher UI
   - Tenant switching
   - Authorization
   - Current tenant indication

3. **CompleteMultiTenancyWorkflowTest** (8 tests)
   - End-to-end workflows
   - Cross-tenant isolation
   - Session scoping
   - Authentication flow

## Security Considerations

### Cross-Tenant Isolation

- **Resource Scoping**: All Filament resources scope queries to current tenant
- **Middleware Enforcement**: EnforceTenantAccess on all authenticated routes
- **Session Validation**: Tenant ID validated on every request
- **Policy Checks**: User-tenant relationship verified

### Attack Prevention

1. **Subdomain Guessing**: Invalid subdomains return 404
2. **Session Hijacking**: Tenant ID in session, validated server-side
3. **Direct URL Access**: Resources check tenant access in getEloquentQuery()
4. **Account Enumeration**: Only show workspaces user has access to

## Deployment

### DNS Configuration

Configure wildcard DNS for subdomains:

```
*.stopit.dev  A  your-server-ip
```

### Web Server Configuration

#### Nginx

```nginx
server {
    listen 80;
    server_name *.stopit.dev stopit.dev;
    
    root /var/www/stopit/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
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

### SSL Certificates

Use Let's Encrypt with wildcard certificates:

```bash
certbot certonly --dns-cloudflare \
  -d stopit.dev \
  -d *.stopit.dev
```

## Troubleshooting

### Session Not Persisting Across Subdomains

Check `SESSION_DOMAIN` in `.env`:
```env
SESSION_DOMAIN=.stopit.dev  # Note the leading dot
```

### Tenant Not Being Identified

1. Verify DNS wildcard is configured
2. Check web server catches all subdomains
3. Ensure `domain` column exists on `accounts` table
4. Run migration: `php artisan migrate`

### User Can't Access Tenant

1. Verify user-account relationship in `workspaces` pivot table
2. Check account `is_active` is `true`
3. Ensure middleware is registered in Filament panel

## Future Enhancements

- [ ] Custom domains (bring your own domain)
- [ ] Tenant-specific branding/themes
- [ ] Usage analytics per tenant
- [ ] Tenant provisioning API
- [ ] Subdomain availability check

## API Documentation

For API endpoints in tenant context, see [API_REFERENCE.md](API_REFERENCE.md).
