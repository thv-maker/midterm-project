#!/bin/sh
set -e

cd "$(dirname "$0")/.."

export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"

echo "[railway-start] APP_ENV=${APP_ENV}"

if [ ! -f ".env" ]; then
  echo "[railway-start] Creating minimal .env for Symfony runtime"
  {
    echo "APP_ENV=${APP_ENV}"
    echo "APP_DEBUG=${APP_DEBUG}"
    echo "APP_SECRET=${APP_SECRET:-change-me-in-production}"
  } > .env
fi

echo "[railway-start] Generating JWT keypair (keys are gitignored, must be created at runtime)..."
mkdir -p config/jwt
php bin/console lexik:jwt:generate-keypair --overwrite --no-interaction --env=prod \
  || echo "[railway-start] WARNING: JWT keypair generation failed"

echo "[railway-start] Running database setup..."
case "${DATABASE_URL:-}" in
  sqlite:*)
    echo "[railway-start] SQLite mode: updating schema"
    php bin/console doctrine:schema:update --force --complete --no-interaction --env=prod || true

    echo "[railway-start] SQLite mode: ensuring admin account"
    php bin/console doctrine:query:sql "INSERT INTO user (email, roles, password, is_active, is_verified, last_login, google_id) VALUES ('admin@midterm.local', '[\"ROLE_ADMIN\"]', '\$2y\$12\$XCegzkOZrLG68DcbW0MfU.rNJGVhB5I3waW.HyzJrW1N34KX6Ox0W', 1, 1, NULL, NULL) ON CONFLICT(email) DO UPDATE SET roles='[\"ROLE_ADMIN\"]', password='\$2y\$12\$XCegzkOZrLG68DcbW0MfU.rNJGVhB5I3waW.HyzJrW1N34KX6Ox0W', is_active=1, is_verified=1;" --env=prod || true

    echo "[railway-start] SQLite mode: ensuring test customer account (email: customer@test.com / password: test1234)"
    TEST_HASH=$(php -r "echo password_hash('test1234', PASSWORD_BCRYPT, ['cost' => 12]);")
    php bin/console doctrine:query:sql "INSERT INTO user (email, roles, password, is_active, is_verified, last_login, google_id) VALUES ('customer@test.com', '[\"ROLE_USER\"]', '${TEST_HASH}', 1, 1, NULL, NULL) ON CONFLICT(email) DO UPDATE SET password='${TEST_HASH}', is_active=1, is_verified=1;" --env=prod || true
    php bin/console doctrine:query:sql "INSERT OR IGNORE INTO customer (name, email, phone, address, fcm_token) VALUES ('Test Customer', 'customer@test.com', '09123456789', '123 Test Street', NULL);" --env=prod || true
    ;;
  mysql:*|postgresql:*|postgres:*)
    echo "[railway-start] MySQL/Postgres mode: running migrations"
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod \
      || echo "[railway-start] WARNING: Migration failed (check DATABASE_URL)"
    ;;
  *)
    echo "[railway-start] WARNING: DATABASE_URL is not set or unrecognized"
    ;;
esac

echo "[railway-start] Clearing and warming Symfony cache..."
php bin/console cache:clear --env=prod --no-warmup --no-interaction \
  || echo "[railway-start] WARNING: cache:clear failed"
php bin/console cache:warmup --env=prod --no-interaction \
  || echo "[railway-start] WARNING: cache:warmup failed"

PUBLIC_PORT="${PORT:-8080}"
PHP_INTERNAL_PORT="${PHP_INTERNAL_PORT:-9080}"
export WEBSOCKET_BROADCAST_URL="${WEBSOCKET_BROADCAST_URL:-http://127.0.0.1:${PUBLIC_PORT}/broadcast}"

echo "[railway-start] Starting PHP backend on 127.0.0.1:${PHP_INTERNAL_PORT}..."
php -S 127.0.0.1:${PHP_INTERNAL_PORT} -t public public/index.php &

echo "[railway-start] Starting WebSocket + HTTP proxy on port ${PUBLIC_PORT}..."
if [ -f "websocket/server.js" ]; then
  (cd websocket && npm install --omit=dev && \
    PORT="${PUBLIC_PORT}" \
    PHP_BACKEND_URL="http://127.0.0.1:${PHP_INTERNAL_PORT}" \
    WEBSOCKET_SECRET="${WEBSOCKET_SECRET:-dev-websocket-secret}" \
    node server.js)
else
  echo "[railway-start] ERROR: websocket/server.js not found"
  exit 1
fi
