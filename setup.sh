#!/bin/bash

# Stopit Setup Script

set -e

echo "🚀 Setting up Stopit Exception Monitoring Platform..."
echo ""

# Guard destructive operations to safe environments only.
# Read APP_ENV from the .env file (if present) so the guard reflects the
# actual configured environment rather than just the shell's exported variable.
if [ -f .env ]; then
    FILE_ENV=$(grep -E '^APP_ENV=' .env | head -1 | cut -d '=' -f2 | tr -d "\"' ")
    if [ -n "$FILE_ENV" ]; then
        APP_ENV="$FILE_ENV"
    fi
fi
APP_ENV="${APP_ENV:-local}"
SAFE_ENVS="local testing ci development"

is_safe_env() {
    for env in $SAFE_ENVS; do
        if [ "$APP_ENV" = "$env" ]; then
            return 0
        fi
    done
    return 1
}

if ! is_safe_env; then
    echo "❌ Refusing to run setup in APP_ENV=${APP_ENV}."
    echo "   Only allowed in: ${SAFE_ENVS}"
    exit 1
fi

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer not found. Please install Composer first."
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP not found. Please install PHP 8.2+ first."
    exit 1
fi

echo "✓ PHP version: $(php -v | head -n 1)"
echo "✓ Composer version: $(composer --version)"
echo ""

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-interaction

# Create .env file
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
fi

# Generate app key only if not already set
if ! grep -q "^APP_KEY=.\+" .env 2>/dev/null; then
    echo "🔑 Generating application key..."
    php artisan key:generate --ansi
else
    echo "✓ APP_KEY already set, skipping generation..."
fi

# Create SQLite Database
if [ ! -f database/database.sqlite ]; then
    echo "💾 Creating SQLite database..."
    touch database/database.sqlite
fi

# Create required storage directories
echo "📁 Creating storage directories..."
mkdir -p storage/framework/{sessions,views,cache,testing}
mkdir -p storage/app/public
mkdir -p storage/logs

# Run Migrations
echo "🗄️  Running migrations..."
php artisan migrate --force

# Refresh autoloader to ensure all classes are discoverable
echo "🔄 Refreshing autoloader..."
composer dump-autoload

# Seed Database
echo "🌱 Seeding database..."
php artisan db:seed --class='Modules\Stopit\Database\Seeders\StopitSeeder'

echo ""
echo "✅ Setup complete!"
echo ""
echo "To start the development server, run:"
echo "  php artisan serve"
echo ""
echo "Then visit http://localhost:8000/admin"
echo ""
echo "Login credentials:"
echo "  Email: admin@acme.test"
echo "  Password: password"
echo ""