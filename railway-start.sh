#!/usr/bin/env bash
###############################################################################
# Aqdi Backend — Railway TEST server startup
# Runs migrations, seeds the test data (idempotent), then serves HTTP on $PORT.
# This file is only used by the Railway test environment (Dockerfile.railway).
###############################################################################
set -e

echo "[railway-start] clearing config cache"
php artisan config:clear || true
php artisan cache:clear || true

echo "[railway-start] running migrations"
php artisan migrate --force

echo "[railway-start] seeding test data (idempotent)"
php artisan db:seed --force || true

echo "[railway-start] linking storage"
php artisan storage:link || true

echo "[railway-start] starting server on 0.0.0.0:${PORT:-8080}"
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
