#!/bin/bash
set -e

echo "🚀 Starting Gym App Backend setup..."

# ── Copy .env ─────────────────────────────────────────────────
if [ ! -f /var/www/gym-app-backend/.env ]; then
    echo "📄 Creating .env from .env.example..."
    cp /var/www/gym-app-backend/.env.example /var/www/gym-app-backend/.env
    echo "✅ .env created"
else
    echo "✅ .env already exists"
fi

# ── Delete composer.lock ───────────────────────────────────────
if [ -f /var/www/gym-app-backend/composer.lock ]; then
    echo "🗑️  Deleting composer.lock..."
    rm -f /var/www/gym-app-backend/composer.lock
    echo "✅ composer.lock deleted"
fi

# ── Composer install ───────────────────────────────────────────
if [ ! -d /var/www/gym-app-backend/vendor ]; then
    echo "📦 Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    echo "✅ Composer dependencies installed"
else
    echo "✅ Vendor directory already exists"
fi

# ── Generate app key ───────────────────────────────────────────
APP_KEY_VALUE=$(grep "^APP_KEY=" /var/www/gym-app-backend/.env | cut -d '=' -f2)
if [ -z "$APP_KEY_VALUE" ] || [ "$APP_KEY_VALUE" = "base64:GENERATE_THIS_WITH_php_artisan_key_generate" ]; then
    echo "🔑 Generating app key..."
    php artisan key:generate --force
    echo "✅ App key generated"
else
    echo "✅ App key already set"
fi

# ── Wait for database ──────────────────────────────────────────
echo "⏳ Waiting for database..."
until php -r "
    \$conn = pg_connect('host=' . getenv('DB_HOST') . ' port=' . getenv('DB_PORT') . ' dbname=' . getenv('DB_DATABASE') . ' user=' . getenv('DB_USERNAME') . ' password=' . getenv('DB_PASSWORD'));
    if (!\$conn) exit(1);
    exit(0);
" 2>/dev/null; do
    echo "   Database not ready — retrying in 3s..."
    sleep 3
done
echo "✅ Database connected"

# ── Migrate and seed ───────────────────────────────────────────
MIGRATED=$(php artisan migrate:status 2>/dev/null | grep -c "Ran" || true)

if [ "$MIGRATED" -eq "0" ]; then
    echo "🗄️  Fresh install — running migrations..."
    php artisan migrate --force
    echo "✅ Migrations completed"

    echo "🌱 Running seeders..."
    php artisan db:seed --force
    echo "✅ Seeders completed"
else
    echo "✅ Already migrated — running new migrations only..."
    php artisan migrate --force
    echo "✅ Migrations up to date"
fi

# ── Install Sanctum ────────────────────────────────────────────
if ! php artisan route:list 2>/dev/null | grep -q "sanctum"; then
    echo "🔐 Installing Sanctum..."
    php artisan install:api --no-interaction
    echo "✅ Sanctum installed"
else
    echo "✅ Sanctum already installed"
fi

# ── Optimize ───────────────────────────────────────────────────
echo "⚡ Optimizing..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan permission:cache-reset
echo "✅ Optimized"

# ── Fix permissions ────────────────────────────────────────────
echo "🔧 Fixing permissions..."
chmod -R 777 /var/www/gym-app-backend/storage
chmod -R 777 /var/www/gym-app-backend/bootstrap/cache
echo "✅ Permissions fixed"

echo ""
echo "✅ =================================="
echo "✅  Gym App Backend is ready!"
echo "✅  URL: http://localhost:8000"
echo "✅  Telescope: http://localhost:8000/telescope"
echo "✅ =================================="
echo ""
echo "  Default credentials:"
echo "  superadmin@gym.com   / password123"
echo "  admin@gym.com        / password123"
echo "  receptionist@gym.com / password123"
echo "  trainer@gym.com      / password123"
echo "  member@gym.com       / password123"
echo ""

# ── Start php-fpm ──────────────────────────────────────────────
exec php-fpm
