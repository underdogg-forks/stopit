# Stopit API Quick Reference

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication
All requests require a Bearer token:
```
Authorization: Bearer YOUR_API_TOKEN_HERE
```

## Endpoints

### POST /exceptions
Report an exception to Stopit.

**Required:**
- `exception_class` (string) — Exception class name
- `message` (string) — Exception message

**Optional:**
- `file` (string) — File path
- `line` (integer) — Line number
- `stack_trace` (string) — Full stack trace
- `request_method` (string) — HTTP method
- `request_url` (string) — Request URL
- `headers` (string) — HTTP headers
- `user_agent` (string) — User agent
- `ip_address` (string) — Client IP
- `user_id` (string) — User identifier
- `context` (object) — Additional context data
- `severity` (string) — One of: `info`, `warning`, `error`, `critical`

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/v1/exceptions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "exception_class": "RuntimeException",
    "message": "Database connection failed",
    "file": "/app/Database.php",
    "line": 42,
    "severity": "critical",
    "context": {
      "database": "primary",
      "retry_count": 3
    }
  }'
```

**PHP Example:**
```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'http://localhost:8000',
    'headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
        'Content-Type' => 'application/json',
    ],
]);

$response = $client->post('/api/v1/exceptions', [
    'json' => [
        'exception_class' => 'RuntimeException',
        'message' => 'Database connection failed',
        'file' => '/app/Database.php',
        'line' => 42,
        'severity' => 'critical',
        'context' => [
            'database' => 'primary',
            'retry_count' => 3,
        ],
    ],
]);
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
// 401 - Invalid or missing token
{
  "message": "Invalid or missing API token"
}

// 422 - Validation error
{
  "message": "The exception class field is required.",
  "errors": {
    "exception_class": ["The exception class field is required."]
  }
}
```

## Severity Levels

| Level | Description |
|-------|-------------|
| `info` | Informational messages |
| `warning` | Warning messages |
| `error` | Error exceptions (default) |
| `critical` | Critical failures |

## Getting Your API Token

1. Log in to the Stopit admin panel at `/admin`
2. Navigate to Applications
3. Create a new application or regenerate token for existing one
4. Copy the token immediately (it won't be shown again)
5. Store the token securely in your environment variables

## Rate Limiting

Currently no rate limiting is enforced. This may change in future versions.

## Support

For questions or issues:
- Open an issue on GitHub
- Check the main README for detailed documentation
- Review the integration examples in the README
