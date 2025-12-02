#!/bin/bash
set -e

echo "Starting application entrypoint..."
echo "DB_HOST=${DB_HOST}, DB_PORT=${DB_PORT}, DB_DATABASE=${DB_DATABASE}, DB_USERNAME=${DB_USERNAME}"

echo "Ensuring Laravel storage and cache directories exist..."
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Wait for MySQL
max_attempts=30
attempt=0

while true; do
  attempt=$((attempt + 1))
  echo "Checking MySQL connection (attempt ${attempt}/${max_attempts})..."

  php -r "
    \$host = getenv('DB_HOST');
    \$port = getenv('DB_PORT') ?: 3306;
    \$db   = getenv('DB_DATABASE');
    \$user = getenv('DB_USERNAME');
    \$pass = getenv('DB_PASSWORD');
    try {
        new PDO(\"mysql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass);
        echo \"DB OK\n\";
    } catch (Throwable \$e) {
        fwrite(STDERR, 'DB ERROR: ' . \$e->getMessage() . \"\n\");
        exit(1);
    }
  " && break

  if [ "$attempt" -ge "$max_attempts" ]; then
    echo "Failed to connect to MySQL after ${max_attempts} attempts. Exiting."
    exit 1
  fi

  echo "MySQL is unavailable - sleeping 2s..."
  sleep 2
done

# Ensure .env exists: copy example if present, otherwise create a formal .env from provided template
echo "Generating env file..."
if [ ! -f .env ]; then
  if [ -f .env.example ]; then
    cp .env.example .env
    echo "Copied .env.example to .env"
  else
    echo "No .env.example found - creating formal .env from template..."
    cat > .env <<'EOF'
APP_NAME=SaktoSpace
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=saktospace
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

FIREBASE_CREDENTIALS=C:\Users\healp\PhpstormProjects\SaktoSpaceAPI\storage\app\saktospace-firebase-adminsdk-abc123.json
EOF
    echo "Created formal .env"
  fi
else
  echo ".env already exists - skipping creation."
fi

# Generate application key if not set
if ! grep -q '^APP_KEY=' .env || [ -z "$(grep '^APP_KEY=' .env | cut -d= -f2-)" ]; then
  echo "Generating application key..."
  php artisan key:generate --force
else
  echo "APP_KEY already set - skipping key:generate"
fi

echo "MySQL is up - running migrations..."
php artisan migrate --force

echo "Running database seeders..."
php artisan db:seed --force

echo "Starting Apache..."
exec apache2-foreground
