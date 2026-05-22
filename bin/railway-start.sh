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

if [ -n "${DATABASE_URL}" ]; then
  echo "[railway-start] Waiting for database..."
  i=0
  until php bin/console doctrine:query:sql "SELECT 1" --no-interaction >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "$i" -ge 30 ]; then
      echo "[railway-start] Database not ready after 30 attempts."
      exit 1
    fi
    sleep 2
  done

  echo "[railway-start] Running migrations..."
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

echo "[railway-start] Starting PHP server on port ${PORT:-8080}"
exec php -S 0.0.0.0:${PORT:-8080} -t public public/index.php