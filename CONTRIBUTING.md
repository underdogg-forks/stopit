# Contributing to Stopit

Thank you for your interest in contributing to Stopit!

## Development Setup

1. Fork and clone the repository
2. Run `./setup.sh` to install dependencies and setup the database
3. Create a new branch for your feature: `git checkout -b feature/your-feature-name`
4. Make your changes
5. Run tests: `vendor/bin/phpunit`
6. Commit your changes following the commit guidelines below
7. Push to your fork and create a Pull Request

## Code Style

### PHP
- Follow PSR-12 coding standards
- Use explicit type hints for all parameters and return types
- **Do not use** `declare(strict_types=1)`
- **Do not use** `readonly` keyword or `final` classes
- Use early returns and guard clauses
- Keep methods focused and single-purpose

### Architecture Patterns
- **Models**: Pure Eloquent models with relationships only
- **DTOs**: Private properties with fluent getters/setters
- **Services**: Business logic with guard clauses
- **Repositories**: Data access with explicit methods (no `updateOrCreate()`)
- **Transformers**: Convert requests to DTOs
- **Filament**: Delegate forms/tables to separate classes

### Database
- Snake_case for column names
- Explicit foreign key constraints
- Composite indexes where needed
- TEXT columns with Laravel casts instead of JSON columns
- String-backed enums instead of MySQL ENUM

### Testing
- Use PHPUnit with `#[Test]` attribute
- Method names: `it_does_something` format
- Structure: Arrange / Act / Assert with comments
- Group tests with `#[Group('name')]`
- Test both happy path and edge cases

## Commit Messages

Format: `type: description`

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `test`: Test additions or changes
- `refactor`: Code refactoring
- `style`: Code style changes (formatting, etc.)
- `chore`: Build process or auxiliary tool changes

Example:
```
feat: add exception filtering by date range
fix: resolve token validation issue for long tokens
docs: update API reference with new endpoints
test: add tests for exception grouping logic
```

## Pull Request Process

1. Update the README.md with details of changes if applicable
2. Ensure all tests pass
3. Update or add tests for your changes
4. Make sure your code follows the style guidelines
5. Request review from maintainers

## Questions?

Feel free to open an issue for any questions or concerns.
