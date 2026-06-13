#!/bin/bash
php artisan migrate --force
php artisan reverb:start --host 0.0.0.0 --port 8081 &
php artisan queue:work --tries=3 &
# FrankenPHP (default for Railpack) usually listens on 8080 or $PORT
# We'll use the default entrypoint behavior if possible, or start it explicitly.
# For Railpack, we should let the builder handle the web server if we can,
# but a combined command usually requires us to start the web server ourselves.
php artisan serve --host 0.0.0.0 --port $PORT
