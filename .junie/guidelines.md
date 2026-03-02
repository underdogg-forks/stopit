# Junie Guidelines

## Code Analysis and Review

### CLI Command Restrictions

**IMPORTANT**: Do NOT execute any CLI commands that require network access or external dependencies. This includes but is not limited to:

- ❌ `composer install` / `composer require` / `composer update`
- ❌ `npm install` / `npm run` / `yarn install`
- ❌ `php artisan test` / `php artisan migrate` / `phpunit`
- ❌ `git push` / `git pull` (use provided tools instead)
- ❌ Package manager commands that download dependencies
- ❌ Commands that make external API calls

### Allowed Operations

You MAY perform the following operations:

- ✅ Read files using `cat`, `view`, `grep`, or similar tools
- ✅ Check file syntax using `php -l <file>`
- ✅ Use `git status`, `git diff`, `git log` for inspection
- ✅ Static code analysis that doesn't require dependencies
- ✅ File system operations (create, edit, delete files)

### Rationale

The development environment has firewall rules that block external network connections. Commands that attempt to download packages or access external resources will fail with connection errors.

### Alternative Approaches

Instead of running CLI commands:
1. Use static code analysis and file inspection
2. Review code structure and syntax manually
3. Use the `view` and `grep` tools to analyze code
4. Assume dependencies are already installed in the environment
5. Focus on code review and structural improvements
