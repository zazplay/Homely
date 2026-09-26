#!/bin/sh
# Container start: prepare Laravel, update the schema, seed demo data once, then serve.
set -e

php artisan config:cache
php artisan route:cache
php artisan data:cache-structures

# Schema changes are applied on every deploy (idempotent: only new migrations run).
php artisan migrate --force

# Demo content (agents, listings, photos) — only into an empty database.
if [ "${SEED_DEMO:-false}" = "true" ]; then
  php artisan demo:seed-if-empty
fi

exec frankenphp php-server --listen "0.0.0.0:${PORT:-10000}" --root public/
