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

echo "[railway-start] Skipping blocking DB checks during boot"

case "${DATABASE_URL:-}" in
  sqlite:*)
    echo "[railway-start] SQLite mode: running migrations"
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod || true

    echo "[railway-start] SQLite mode: ensuring admin account"
    php bin/console doctrine:query:sql "INSERT INTO user (email, roles, password, is_active, is_verified, last_login, google_id) VALUES ('admin@midterm.local', '[\"ROLE_ADMIN\"]', '\$2y\$12\$XCegzkOZrLG68DcbW0MfU.rNJGVhB5I3waW.HyzJrW1N34KX6Ox0W', 1, 1, NULL, NULL) ON CONFLICT(email) DO UPDATE SET roles='[\"ROLE_ADMIN\"]', password='\$2y\$12\$XCegzkOZrLG68DcbW0MfU.rNJGVhB5I3waW.HyzJrW1N34KX6Ox0W', is_active=1, is_verified=1;" --env=prod || true
    ;;
esac

echo "[railway-start] Starting PHP server on port ${PORT:-8080}"
exec php -S 0.0.0.0:${PORT:-8080} -t public public/index.php