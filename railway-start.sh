#!/usr/bin/env bash
###############################################################################
# Aqdi Backend — Railway startup
# SAFETY: the database is wiped + reseeded ONLY when ALLOW_DB_RESET=true and the
# environment is not production. Otherwise it runs forward migrations only, so a
# real database can never be destroyed by a deploy or a crash-restart.
# To rebuild the TEST data on deploy, set ALLOW_DB_RESET=true in Railway.
###############################################################################
set -e

echo "[railway-start] clearing config cache"
php artisan config:clear || true
php artisan cache:clear || true

if [ "$APP_ENV" != "production" ] && [ "$ALLOW_DB_RESET" = "true" ]; then
  echo "[railway-start] RESET MODE: migrate:fresh + seed (test data)"
  php artisan migrate:fresh --force
  php artisan db:seed --force || echo "[railway-start] seed reported an error (continuing to serve)"
else
  echo "[railway-start] SAFE MODE: forward migrations only (no wipe)"
  php artisan migrate --force || echo "[railway-start] migrate reported an error (continuing to serve)"
fi

echo "[railway-start] linking storage"
php artisan storage:link || true

echo "[railway-start] starting server on 0.0.0.0:${PORT:-8080}"
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
