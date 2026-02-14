#!/bin/bash
# Run after: docker compose up -d
# From project root: ./docker/init.sh (or run commands below manually)

set -e

echo "Installing Composer dependencies..."
docker compose exec app composer install --no-interaction

echo "Copying .env.example to .env (if .env missing)..."
docker compose exec app sh -c '[ -f .env ] || cp .env.example .env'

echo "Generating application key..."
docker compose exec app php artisan key:generate

echo "Running migrations and seeders..."
docker compose exec app php artisan migrate --seed --force

echo "JWT secret (run after Task 02 - when jwt-auth is installed):"
echo "  docker compose exec app php artisan jwt:secret"

echo "Done. API should be available at http://localhost:8000"
