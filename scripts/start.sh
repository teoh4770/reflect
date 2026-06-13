#!/bin/bash

echo "Debug: RAILWAY_RUN_COMMAND is '$RAILWAY_RUN_COMMAND'"

if [ "$RAILWAY_RUN_COMMAND" = "reverb" ]; then
    echo "Starting Reverb..."
    php artisan reverb:start --host 0.0.0.0 --port $PORT
    exit 0
fi

if [ "$RAILWAY_RUN_COMMAND" = "worker" ]; then
    echo "Starting Worker..."
    php artisan queue:work --tries=3
    exit 0
fi

if [ "$RAILWAY_RUN_COMMAND" = "scheduler" ]; then
    echo "Starting Scheduler..."
    php artisan schedule:work
    exit 0
fi

php artisan migrate --force --seed
php artisan queue:work --tries=3 &

echo "Starting Web Server..."
php artisan serve --host 0.0.0.0 --port $PORT
