# Stopit — Exception Monitoring SaaS

A standalone multi-tenant SaaS exception monitoring platform built as a Laravel module. Stopit ingests exceptions from external applications via Bearer token API and displays them in a beautiful Filament 3 admin dashboard with Nord theme.

## Features

- **Multi-tenant Architecture** — Isolated accounts with many-to-many relationships for users and applications
- **Bearer Token Authentication** — Secure API access with SHA-256 hashed tokens
- **Exception Grouping** — Automatic fingerprinting by (application, class, message)
- **Occurrence Tracking** — Incremental counting of duplicate exceptions
- **Filament 3 Dashboard** — Beautiful admin UI with Nord color scheme
- **Real-time Widgets** — Stats, recent exceptions, and exception distribution charts
- **Severity Levels** — Info, Warning, Error, Critical
- **Cross-Application Isolation** — Complete data separation between apps and accounts

## Directory Structure

```
stopit/
├── Modules/
│   ├── Core/
│   │   ├── Contracts/
│   │   │   └── RepositoryContract.php
│   │   ├── Enums/
│   │   │   └── Severity.php
│   │   └── Traits/
│   │       └── BelongsToAccount.php
│   └── Stopit/
│       ├── DTOs/
│       │   ├── ApplicationData.php
│       │   └── ExceptionData.php
│       ├── Database/
│       │   ├── migrations/
│       │   │   ├── 2026_02_28_000001_create_accounts_table.php
│       │   │   ├── 2026_02_28_000002_create_users_table.php
│       │   │   ├── 2026_02_28_000003_create_applications_table.php
│       │   │   └── 2026_02_28_000004_create_exceptions_table.php
│       │   ├── factories/
│       │   │   ├── AccountFactory.php
│       │   │   ├── UserFactory.php
│       │   │   ├── ApplicationFactory.php
│       │   │   └── ExceptionRecordFactory.php
│       │   └── seeders/
│       │       └── StopitSeeder.php
│       ├── Models/
│       │   ├── Account.php
│       │   ├── User.php
│       │   ├── Application.php
│       │   └── ExceptionRecord.php
│       ├── Repositories/
│       │   ├── Contracts/
│       │   │   ├── ExceptionRepositoryContract.php
│       │   │   └── ApplicationRepositoryContract.php
│       │   ├── ExceptionRepository.php
│       │   └── ApplicationRepository.php
│       ├── Services/
│       │   ├── ApplicationService.php
│       │   ├── ExceptionCollectionService.php
│       │   └── DashboardService.php
│       ├── Transformers/
│       │   └── ExceptionTransformer.php
│       ├── Http/
│       │   ├── Controllers/
│       │   │   └── ExceptionController.php
│       │   ├── Middleware/
│       │   │   └── ApiTokenMiddleware.php
│       │   └── Requests/
│       │       └── StoreExceptionRequest.php
│       ├── Filament/
│       │   ├── StopitPanelProvider.php
│       │   ├── Pages/
│       │   │   └── Dashboard.php
│       │   ├── Resources/
│       │   │   ├── ApplicationResource.php
│       │   │   ├── ApplicationResource/
│       │   │   │   ├── Forms/
│       │   │   │   │   └── ApplicationForm.php
│       │   │   │   ├── Tables/
│       │   │   │   │   └── ApplicationTable.php
│       │   │   │   └── Pages/
│       │   │   │       ├── ListApplications.php
│       │   │   │       ├── CreateApplication.php
│       │   │   │       ├── EditApplication.php
│       │   │   │       └── ViewApplication.php
│       │   │   ├── ExceptionResource.php
│       │   │   └── ExceptionResource/
│       │   │       ├── Forms/
│       │   │       │   └── ExceptionForm.php
│       │   │       ├── Tables/
│       │   │       │   └── ExceptionTable.php
│       │   │       └── Pages/
│       │   │           ├── ListExceptions.php
│       │   │           └── ViewException.php
│       │   └── Widgets/
│       │       ├── ExceptionStatsWidget.php
│       │       ├── RecentExceptionsWidget.php
│       │       └── ExceptionsByClassWidget.php
│       ├── routes/
│       │   └── api.php
│       ├── Tests/
│       │   └── Feature/
│       │       ├── Api/
│       │       │   ├── ExceptionCollectionTest.php
│       │       │   └── CrossApplicationIsolationTest.php
│       │       └── Services/
│       │           ├── ApplicationServiceTest.php
│       │           ├── ExceptionCollectionServiceTest.php
│       │           └── DashboardServiceTest.php
│       └── StopitServiceProvider.php
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── routes/
├── tests/
└── composer.json
```

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- SQLite or MySQL

### Setup Steps

1. **Clone the repository**

```bash
git clone https://github.com/underdogg-forks/stopit.git
cd stopit
```

2. **Install dependencies**

```bash
composer install
```

3. **Configure environment**

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` to configure your database:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Or for MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stopit
DB_USERNAME=root
DB_PASSWORD=
```

4. **Create database file** (SQLite only)

```bash
touch Database/Database.sqlite
```

5. **Run migrations**

```bash
php artisan migrate
```

6. **Seed sample data**

```bash
php artisan db:seed --class=Modules\\Stopit\\Database\\Seeders\\StopitSeeder
```

This will create:
- Account: "Acme Corp"
- User: admin@acme.test / password
- Applications: GitMan, Jobify, Spotivel, TrollBeGone (with API tokens printed to console)

7. **Start the development server**

```bash
php artisan serve
```

8. **Access the admin panel**

Visit `http://localhost:8000/admin` and log in with:
- Email: `admin@acme.test`
- Password: `password`

## API Reference

### Authentication

All API requests require a Bearer token in the `Authorization` header:

```
Authorization: Bearer YOUR_API_TOKEN_HERE
```

Tokens are generated when you create an application in the Filament admin panel. They are displayed **only once** and must be saved immediately.

### POST /api/v1/exceptions

Report an exception to Stopit.

**Required Fields:**
- `exception_class` (string, max 500) — The exception class name
- `message` (string) — The exception message

**Optional Fields:**
- `file` (string, max 1000) — File where exception occurred
- `line` (integer) — Line number where exception occurred
- `stack_trace` (string) — Full stack trace
- `request_method` (string, max 10) — HTTP method (GET, POST, etc.)
- `request_url` (string, max 2048) — Request URL
- `headers` (string) — HTTP headers as string
- `user_agent` (string, max 1000) — User agent string
- `ip_address` (string, max 45) — Client IP address
- `user_id` (string, max 255) — User ID if authenticated
- `context` (array) — Additional context data (stored as JSON)
- `severity` (string) — One of: `info`, `warning`, `error`, `critical` (default: `error`)

**Request Example:**

```bash
curl -X POST http://localhost:8000/api/v1/exceptions \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "exception_class": "RuntimeException",
    "message": "Database connection failed",
    "file": "/app/Database/Connection.php",
    "line": 142,
    "stack_trace": "#0 /app/Database/Connection.php(142): connect()\n#1 /app/index.php(23): query()",
    "request_method": "POST",
    "request_url": "https://example.com/api/users",
    "user_agent": "Mozilla/5.0",
    "ip_address": "192.168.1.100",
    "user_id": "12345",
    "context": {
      "Database": "primary",
      "retry_count": 3
    },
    "severity": "critical"
  }'
```

**Success Response (201):**

```json
{
  "id": 1,
  "exception_class": "RuntimeException",
  "message": "Database connection failed",
  "created_at": "2026-02-28T10:30:00+00:00"
}
```

**Error Responses:**

```json
// 401 Unauthorized (missing or invalid token)
{
  "message": "Invalid or missing API token"
}

// 422 Validation Error
{
  "message": "The exception class field is required.",
  "errors": {
    "exception_class": [
      "The exception class field is required."
    ]
  }
}
```

## Integration Examples

### Guzzle HTTP Client

```php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class StopitLogger
{
    private Client $client;
    private string $apiToken;

    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
        $this->client = new Client([
            'base_uri' => 'https://your-stopit-instance.com',
            'timeout' => 5.0,
        ]);
    }

    public function logException(\Throwable $exception, array $context = []): void
    {
        try {
            $this->client->postAsync('/api/v1/exceptions', [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'exception_class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'stack_trace' => $exception->getTraceAsString(),
                    'context' => $context,
                    'severity' => 'error',
                ],
            ])->wait();
        } catch (GuzzleException $e) {
            // Silently fail to avoid cascading errors
            error_log("Failed to log exception to Stopit: " . $e->getMessage());
        }
    }
}
```

### Laravel Exception Handler Integration

```php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function report(Throwable $exception): void
    {
        // Report to Stopit
        if ($this->shouldReportToStopit($exception)) {
            $this->reportToStopit($exception);
        }

        parent::report($exception);
    }

    private function shouldReportToStopit(Throwable $exception): bool
    {
        // Don't report to Stopit in local environment
        if (app()->environment('local')) {
            return false;
        }

        // Don't report validation exceptions
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return false;
        }

        return true;
    }

    private function reportToStopit(Throwable $exception): void
    {
        try {
            $client = new \GuzzleHttp\Client();
            
            $client->postAsync(config('stopit.url') . '/api/v1/exceptions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('stopit.token'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'exception_class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'stack_trace' => $exception->getTraceAsString(),
                    'request_method' => request()->method(),
                    'request_url' => request()->fullUrl(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'user_id' => auth()->id() ? (string) auth()->id() : null,
                    'context' => [
                        'environment' => app()->environment(),
                        'version' => config('app.version'),
                    ],
                    'severity' => 'error',
                ],
            ]);
        } catch (\Exception $e) {
            // Silently fail
            \Log::error('Failed to report to Stopit: ' . $e->getMessage());
        }
    }
}
```

Add to `config/stopit.php`:

```php
<?php

return [
    'url' => env('STOPIT_URL', 'https://your-stopit-instance.com'),
    'token' => env('STOPIT_TOKEN'),
];
```

Add to `.env`:

```env
STOPIT_URL=https://your-stopit-instance.com
STOPIT_TOKEN=your_application_token_here
```

## Token Security

### How Tokens Work

1. **Generation**: Tokens are generated using `Str::random(64)`, providing ~384 bits of entropy
2. **Storage**: Only the SHA-256 hash of the token is stored in the database
3. **Display**: The plain token is shown **exactly once** when created or regenerated
4. **Validation**: Incoming tokens are hashed and compared against stored hashes
5. **Rotation**: Regenerating a token immediately invalidates the old token

### Token Best Practices

- **Never commit tokens to version control**
- **Store tokens in environment variables**
- **Use different tokens for each application**
- **Rotate tokens if compromised**
- **Use HTTPS in production to prevent token interception**

## Severity Levels

| Severity | Description | Color |
|----------|-------------|-------|
| `info` | Informational messages | Blue |
| `warning` | Warning messages that should be reviewed | Yellow |
| `error` | Error exceptions (default) | Red |
| `critical` | Critical failures requiring immediate attention | Red |

## Testing

### Run All Tests

```bash
vendor/bin/phpunit
```

### Run Specific Test Groups

```bash
# API tests
vendor/bin/phpunit --group=api

# Exception collection tests
vendor/bin/phpunit --group=exception-collection

# Isolation tests
vendor/bin/phpunit --group=isolation

# Service tests
vendor/bin/phpunit --group=services

# Application service tests
vendor/bin/phpunit --group=application-service

# Exception collection service tests
vendor/bin/phpunit --group=exception-collection-service

# Dashboard service tests
vendor/bin/phpunit --group=dashboard-service
```

### Test Coverage

The test suite includes:

- **5 API endpoint tests** — Authentication, validation, and basic flow
- **6 Cross-application isolation tests** — Multi-tenant data separation
- **6 Application service tests** — Token generation, validation, and rotation
- **6 Exception collection service tests** — Exception creation and grouping
- **5 Dashboard service tests** — Statistics and data aggregation

Total: **28 comprehensive tests** covering all critical paths

## Architecture

### Design Principles

1. **SOLID Principles** — Single responsibility, clear separations
2. **Explicit Types** — All parameters and return types declared
3. **Service Layer Pattern** — Business logic in services, not controllers
4. **Repository Pattern** — Data access abstracted behind contracts
5. **DTO Pattern** — Data transfer objects with fluent getters/setters
6. **Transformer Pattern** — Request data transformed before service layer

### Key Patterns

- **Models** — Pure Eloquent models with relationships
- **DTOs** — Private properties with public fluent getters/setters
- **Services** — Business logic with early returns and guard clauses
- **Repositories** — Explicit methods (insert, incrementOccurrence, updateToken)
- **Transformers** — Convert HTTP requests to DTOs
- **Middleware** — Token validation and application resolution
- **Resources** — Filament resources with delegated forms/tables
- **Widgets** — Dashboard widgets with application filtering

### Multi-Tenancy

All data is isolated using many-to-many relationships:

1. **User** belongs to many Accounts via `workspaces` pivot table (via `BelongsToManyAccounts` trait)
2. **Application** belongs to many Accounts via `account_application` pivot table (via `BelongsToManyAccounts` trait)
3. **ExceptionRecord** belongs to Application (which belongs to many Accounts)
4. **Queries** are automatically scoped using account relationships
5. **Widgets** re-assert account ownership even with specific application filter
6. **Resources** enforce tenant scoping on all record lookups (list, view, edit) via `getEloquentQuery()` and policy methods

## Database Schema

### Accounts Table

Stores tenant accounts.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | Account name |
| slug | varchar(255) unique | URL-friendly identifier |
| created_at | timestamp | |
| updated_at | timestamp | |

### Users Table

Admin users belonging to accounts via many-to-many relationship.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | User's full name |
| email | varchar(255) unique | Login email |
| password | varchar(255) | Hashed password |
| remember_token | varchar(100) nullable | Session token |
| created_at | timestamp | |
| updated_at | timestamp | |

### Workspaces Table (Pivot)

Links users to accounts (many-to-many).

| Column | Type | Notes |
|--------|------|-------|
| user_id | FK → users | Cascade delete |
| account_id | FK → accounts | Cascade delete |
| created_at | timestamp | |
| updated_at | timestamp | |

**Indexes**: Unique on (user_id, account_id)

### Applications Table

Applications that report exceptions.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | Application name |
| slug | varchar(255) unique | URL-friendly identifier |
| api_token | varchar(64) unique | SHA-256 hash, hidden in model |
| created_at | timestamp | |
| updated_at | timestamp | |

### Account_Application Table (Pivot)

Links applications to accounts (many-to-many).

| Column | Type | Notes |
|--------|------|-------|
| account_id | FK → accounts | Cascade delete |
| application_id | FK → applications | Cascade delete |
| created_at | timestamp | |
| updated_at | timestamp | |

**Indexes**: Unique on (account_id, application_id)

### Exceptions Table

Exception records with grouping by fingerprint.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| application_id | FK → applications | Cascade delete |
| exception_class | varchar(500) | Exception class name |
| message | text | Exception message |
| file | varchar(1000) nullable | File path |
| line | unsigned int nullable | Line number |
| stack_trace | longtext nullable | Full stack trace |
| request_method | varchar(10) nullable | HTTP method |
| request_url | varchar(2048) nullable | Request URL |
| headers | text nullable | HTTP headers |
| user_agent | varchar(1000) nullable | User agent string |
| ip_address | varchar(45) nullable | Client IP |
| user_id | varchar(255) nullable | User identifier |
| context | text nullable | JSON data (TEXT with Laravel cast) |
| severity | varchar(20) default 'error' | Severity level (enum cast) |
| occurrence_count | bigint unsigned default 1 | Number of occurrences |
| is_resolved | boolean default false | Resolution status |
| first_occurred_at | timestamp nullable | First occurrence time |
| last_occurred_at | timestamp nullable | Most recent occurrence |
| created_at | timestamp | |
| updated_at | timestamp | |

**Indexes:**
- `idx_app_class` — (application_id, exception_class)
- `idx_app_resolved_last` — (application_id, is_resolved, last_occurred_at)
- `idx_app_severity` — (application_id, severity)

## Future Enhancements (Phase 2)

The following features are planned but not yet implemented:

- **Alert Rules** — Email/Slack notifications on specific conditions
- **SSL Certificate Monitoring** — Track SSL expiration
- **Uptime Monitoring** — HTTP endpoint health checks
- **Performance Metrics** — Response time tracking
- **User Roles** — Granular permissions within accounts
- **API Rate Limiting** — Prevent abuse
- **Exception Resolution Workflow** — Comments, assignments
- **Dark Mode** — Theme switcher

## License

MIT License

## Credits

Built with:
- [Laravel 11](https://laravel.com)
- [Filament 3](https://filamentphp.com)
- [Nord Color Palette](https://www.nordtheme.com)

---

**Stopit** — Stop worrying about exceptions. Start monitoring them.
