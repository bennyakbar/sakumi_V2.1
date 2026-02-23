#!/usr/bin/env bash
#
# Usage:
#   ./scripts/switch-env.sh dummy   → Switch to SQLite dummy database
#   ./scripts/switch-env.sh real    → Switch to PostgreSQL real database
#   ./scripts/switch-env.sh        → Show current mode
#

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$ROOT_DIR/.env"

# ── Show current mode if no argument ──
if [[ $# -eq 0 ]]; then
  CURRENT="$(grep -E '^DB_SAKUMI_MODE=' "$ENV_FILE" | cut -d'=' -f2 | tr -d '"' || echo 'unknown')"
  echo "Current mode: $CURRENT"
  exit 0
fi

TARGET="$1"
if [[ "$TARGET" != "dummy" && "$TARGET" != "real" ]]; then
  echo "Usage: $0 [dummy|real]"
  exit 1
fi

# ── Check current mode ──
CURRENT="$(grep -E '^DB_SAKUMI_MODE=' "$ENV_FILE" | cut -d'=' -f2 | tr -d '"' || echo '')"
if [[ "$CURRENT" == "$TARGET" ]]; then
  echo "Already in $TARGET mode. Nothing to do."
  exit 0
fi

# ── Helper: update a single .env variable ──
set_env() {
  local KEY="$1" VAL="$2"
  if grep -qE "^${KEY}=" "$ENV_FILE"; then
    sed -i "s|^${KEY}=.*|${KEY}=${VAL}|" "$ENV_FILE"
  else
    echo "${KEY}=${VAL}" >> "$ENV_FILE"
  fi
}

# ── Switch variables ──
if [[ "$TARGET" == "dummy" ]]; then
  set_env APP_NAME '"Sistem Keuangan MI [DUMMY]"'
  set_env APP_ENV testing
  set_env DB_SAKUMI_MODE dummy
  set_env DB_CONNECTION sakumi_dummy
  set_env DB_HOST ""
  set_env DB_PORT ""
  set_env DB_REAL_DATABASE sakumi_real
  set_env DB_REAL_USERNAME sakumi
  set_env DB_REAL_PASSWORD ""
else
  set_env APP_NAME '"Sistem Keuangan MI"'
  set_env APP_ENV local
  set_env DB_SAKUMI_MODE real
  set_env DB_CONNECTION sakumi_real
  set_env DB_HOST 127.0.0.1
  set_env DB_PORT 5433
  set_env DB_REAL_DATABASE sakumi
  set_env DB_REAL_USERNAME sakumi
  set_env DB_REAL_PASSWORD sakumi
fi

# ── Clear Laravel caches ──
(
  cd "$ROOT_DIR"
  php artisan config:clear  >/dev/null 2>&1 || true
  php artisan cache:clear   >/dev/null 2>&1 || true
)

echo "✅ Switched to $TARGET mode!"

# ── Post-switch setup ──
if [[ "$TARGET" == "real" ]]; then
  echo ""
  echo "Starting PostgreSQL container..."

  COMPOSE_FILE="$ROOT_DIR/portable-transfer-kit-docker/docker-compose.yml"
  if [[ ! -f "$COMPOSE_FILE" ]]; then
    echo "⚠️  Docker Compose file not found: $COMPOSE_FILE"
    echo "   Please start your PostgreSQL manually."
    exit 0
  fi

  docker compose -f "$COMPOSE_FILE" up -d db

  echo "Waiting for database..."
  for i in {1..30}; do
    if docker compose -f "$COMPOSE_FILE" exec db pg_isready -U sakumi >/dev/null 2>&1; then
      echo "Database ready!"
      break
    fi
    echo -n "."
    sleep 1
  done

  (cd "$ROOT_DIR" && php artisan migrate --force --no-interaction)
  echo "✅ Real database ready!"
fi

if [[ "$TARGET" == "dummy" ]]; then
  echo ""
  DUMMY_DB="$ROOT_DIR/database/sakumi_dummy.sqlite"
  if [[ ! -f "$DUMMY_DB" ]]; then
    touch "$DUMMY_DB"
  fi

  (
    cd "$ROOT_DIR"
    php artisan migrate --force --no-interaction
    php artisan db:seed --class='Database\Seeders\Testing\DummyDatabaseSeeder' --force --no-interaction
  )
  echo "✅ Dummy database ready!"
fi
