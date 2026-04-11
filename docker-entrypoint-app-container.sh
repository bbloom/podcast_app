#!/bin/sh
set -e

echo ">> ENTRYPOINT IS RUNNING <<"

# If VS Code is trying to run a shell, allow it
if [ "$1" = "bash" ] || [ "$1" = "sh" ] || [ "$1" = "sleep" ]; then
    exec "$@"
fi

# Step 1: Install composer dependencies if needed
if [ ! -d "/app/vendor" ]; then
    composer install --no-interaction --prefer-dist --working-dir=/app
fi

# Step 2: Wait for database
until pg_isready -h "$DB_HOST" -U "$DB_USERNAME"; do
  echo "Waiting for database..."
  sleep 2
done

# Step 3: If this is the queue worker, skip web server setup and start the worker.
# The queue container passes "php /app/artisan queue:work ..." as its command,
# so we detect "php" as the first argument and hand off immediately — no wipe,
# no migrate, no FrankenPHP.
if [ "$1" = "php" ]; then
    exec "$@"
fi

# Step 4: Start Octane with FrankenPHP in worker mode
exec php artisan octane:start \
    --server=frankenphp \
    --host=0.0.0.0 \
    --port=443 \
    --admin-port=2019 \
    --caddyfile=/etc/caddy/Caddyfile