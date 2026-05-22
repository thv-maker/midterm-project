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

echo "[railway-start] Starting PHP server on port ${PORT:-8080}"
exec php -S 0.0.0.0:${PORT:-8080} -t public public/index.php