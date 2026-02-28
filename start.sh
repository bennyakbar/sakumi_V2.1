#!/bin/bash

# Usage:
#   ./start.sh          - Start server with current DB mode
#   ./start.sh dummy    - Switch to dummy DB, then start server
#   ./start.sh real     - Switch to real DB, then start server

# Ensure we are in the correct directory
if [ ! -f "artisan" ]; then
    echo "Error: artisan not found. Please run this script from the sakumi root directory."
    exit 1
fi

PORT=8002
HOST=127.0.0.1

# Switch DB mode if argument given
if [ -n "${1:-}" ]; then
    echo "Switching to $1 mode..."
    bash scripts/switch-env.sh "$1"
    echo ""
fi

# Show current mode
CURRENT_MODE=$(grep -E '^DB_SAKUMI_MODE=' .env | cut -d'=' -f2 | tr -d '"')
echo "==================================="
echo "  Sakumi Server"
echo "  Database: $CURRENT_MODE"
echo "  URL: http://$HOST:$PORT"
echo "==================================="

# Check if port is in use
if lsof -i :$PORT > /dev/null 2>&1; then
    echo "Port $PORT in use - killing old process..."
    PID=$(lsof -t -i:$PORT)
    kill -9 $PID 2>/dev/null || true
fi

# Start npm run dev in background if not running
if ! pgrep -f "npm run dev" > /dev/null; then
    echo "Starting Vite..."
    npm run dev > /dev/null 2>&1 &
fi

# Start artisan serve
php artisan serve --host=$HOST --port=$PORT
