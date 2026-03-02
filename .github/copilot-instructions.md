# GitHub Copilot Instructions

## Environment Constraints

This repository operates in a restricted network environment with firewall rules in place.

### ❌ Prohibited CLI Commands

**DO NOT execute the following types of commands:**

1. **Package Management**
   - `composer install`, `composer require`, `composer update`
   - `npm install`, `npm ci`, `yarn install`, `pnpm install`
   - Any command that downloads external dependencies

2. **Testing Frameworks**
   - `php artisan test`
   - `phpunit`
   - `pest`
   - `npm test` / `yarn test`

3. **Build Tools**
   - `npm run build` / `npm run dev`
   - `vite build`
   - Asset compilation commands

4. **Database Operations**
   - `php artisan migrate` (unless explicitly requested and environment is set up)
   - `php artisan db:seed`

5. **External Network Access**
   - `git push` / `git pull` (use provided report_progress tool instead)
   - `curl`, `wget` for external URLs
   - Any command making HTTP/HTTPS requests to external services

### ✅ Allowed Operations

You MAY perform these operations:

1. **File Operations**
   - Read files: `cat`, `view`, `less`, `head`, `tail`
   - Edit files: `edit`, `create` tools
   - Search files: `grep`, `find`, `glob`

2. **Code Analysis**
   - Syntax checking: `php -l <file>`
   - Static analysis that doesn't require external dependencies
   - Code structure review

3. **Git Inspection** (read-only)
   - `git status`
   - `git diff`
   - `git log`
   - `git show`

4. **File System**
   - `ls`, `pwd`, `mkdir`, `rm`, `mv`, `cp`
   - Directory navigation and inspection

### Workflow Guidelines

1. **Code Review**: Focus on static code analysis using file reading and inspection tools
2. **Dependency Assumptions**: Assume all necessary dependencies are already installed
3. **Testing**: Recommend test approaches without executing them
4. **Changes**: Use provided tools (`edit`, `create`, `report_progress`) instead of direct git commands
5. **Network Operations**: Use `web_fetch` tool if external content retrieval is absolutely necessary

### Error Handling

If you encounter "Firewall rules blocked me from connecting" errors:
- This is expected behavior in this environment
- Do NOT attempt to retry network operations
- Use alternative approaches from the "Allowed Operations" section above

### Summary

**Key Principle**: Work with the codebase using read/write file operations and static analysis only. Avoid any operations requiring external network access.
