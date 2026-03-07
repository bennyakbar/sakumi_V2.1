#!/bin/bash

cd /var/www/sakumi

echo "Pulling latest code..."
git pull origin main

echo "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader

echo "Running migrations..."
php artisan migrate --force

echo "Optimizing Laravel..."
php artisan optimize

echo "Deployment finished."
