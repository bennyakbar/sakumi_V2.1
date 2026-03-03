#!/usr/bin/env bash
#
# Usage:
#   ./scripts/switch-env.sh dummy   -> Switch to SQLite dummy database
#   ./scripts/switch-env.sh real    -> Switch to PostgreSQL real database
#   ./scripts/switch-env.sh         -> Show current mode
#

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$ROOT_DIR/.env"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Error: .env not found at $ENV_FILE" >&2
  exit 1
fi

get_env_value() {
  local key="$1"
  grep -E "^${key}=" "$ENV_FILE" | tail -n1 | cut -d'=' -f2- || true
}

set_env_value() {
  local key="$1"
  local value="$2"
  local tmp
  tmp="$(mktemp)"
  awk -v k="$key" -v v="$value" '
    BEGIN { done=0 }
    {
      if ($0 ~ ("^" k "=")) {
        print k "=" v
        done=1
      } else {
        print
      }
    }
    END {
      if (!done) {
        print k "=" v
      }
    }
  ' "$ENV_FILE" > "$tmp"
  mv "$tmp" "$ENV_FILE"
}

get_profile_value() {
  local profile_file="$1"
  local key="$2"
  if [[ -f "$profile_file" ]]; then
    grep -E "^${key}=" "$profile_file" | tail -n1 | cut -d'=' -f2- || true
  else
    true
  fi
}

set_from_profile_or_default() {
  local profile_file="$1"
  local key="$2"
  local default="$3"
  local profile_value
  profile_value="$(get_profile_value "$profile_file" "$key")"
  if [[ -n "$profile_value" ]]; then
    set_env_value "$key" "$profile_value"
  else
    set_env_value "$key" "$default"
  fi
}

show_current_mode() {
  local current
  current="$(get_env_value "DB_SAKUMI_MODE" | tr -d '"')"
  echo "Current mode: ${current:-unknown}"
}

provision_real_db() {
  local compose_file="$ROOT_DIR/portable-transfer-kit-docker/docker-compose.yml"
  local db_user db_host db_port

  db_user="$(get_env_value "DB_REAL_USERNAME")"
  db_host="$(get_env_value "DB_HOST")"
  db_port="$(get_env_value "DB_PORT")"

  echo ""
  echo "Preparing real database..."

  if command -v pg_isready >/dev/null 2>&1; then
    if pg_isready -h "${db_host:-127.0.0.1}" -p "${db_port:-5432}" -U "${db_user:-postgres}" >/dev/null 2>&1; then
      echo "PostgreSQL already reachable at ${db_host:-127.0.0.1}:${db_port:-5432}."
    else
      echo "PostgreSQL not reachable yet at ${db_host:-127.0.0.1}:${db_port:-5432}."
    fi
  fi

  if command -v docker >/dev/null 2>&1 && [[ -f "$compose_file" ]]; then
    if docker compose -f "$compose_file" up -d db >/dev/null 2>&1; then
      echo "Docker database container started."
      for _ in {1..30}; do
        if docker compose -f "$compose_file" exec -T db pg_isready -U "${db_user:-sakumi}" >/dev/null 2>&1; then
          echo "Docker PostgreSQL is ready."
          break
        fi
        sleep 1
      done
    else
      echo "Warning: unable to start Docker database container. Continuing with switched environment." >&2
    fi
  elif [[ ! -f "$compose_file" ]]; then
    echo "Warning: Docker compose file not found. Skipping auto-start." >&2
  else
    echo "Warning: docker command not found. Skipping auto-start." >&2
  fi

  if (cd "$ROOT_DIR" && php artisan migrate --force --no-interaction >/dev/null 2>&1); then
    echo "Real database migration completed."
  else
    echo "Warning: migration failed in real mode. Check DB availability and run: php artisan migrate --force" >&2
  fi
}

provision_dummy_db() {
  local dummy_db="$ROOT_DIR/database/sakumi_dummy.sqlite"

  echo ""
  echo "Preparing dummy database..."
  if [[ ! -f "$dummy_db" ]]; then
    touch "$dummy_db"
  fi

  (
    cd "$ROOT_DIR"
    php artisan migrate:fresh --force --no-interaction
    php artisan db:seed --class='Database\Seeders\Testing\DummyDatabaseSeeder' --force --no-interaction
  )
  echo "Dummy database migration + seed completed."
}

if [[ $# -eq 0 ]]; then
  show_current_mode
  exit 0
fi

TARGET="$1"
if [[ "$TARGET" != "dummy" && "$TARGET" != "real" ]]; then
  echo "Usage: $0 [dummy|real]" >&2
  exit 1
fi

CURRENT="$(get_env_value "DB_SAKUMI_MODE" | tr -d '"')"
if [[ "$CURRENT" == "$TARGET" ]]; then
  echo "Already in $TARGET mode. Refreshing caches and database setup..."
fi

PROFILE_FILE="$ROOT_DIR/.env.$TARGET"

if [[ "$TARGET" == "dummy" ]]; then
  set_from_profile_or_default "$PROFILE_FILE" "APP_NAME" "\"Sistem Keuangan MI [DUMMY]\""
  set_from_profile_or_default "$PROFILE_FILE" "APP_ENV" "testing"
  set_env_value "DB_SAKUMI_MODE" "dummy"
  set_env_value "DB_CONNECTION" "sakumi_dummy"
  set_from_profile_or_default "$PROFILE_FILE" "DB_HOST" ""
  set_from_profile_or_default "$PROFILE_FILE" "DB_PORT" ""
  set_from_profile_or_default "$PROFILE_FILE" "DB_REAL_DATABASE" "$(get_env_value "DB_REAL_DATABASE")"
  set_from_profile_or_default "$PROFILE_FILE" "DB_REAL_USERNAME" "$(get_env_value "DB_REAL_USERNAME")"
  set_from_profile_or_default "$PROFILE_FILE" "DB_REAL_PASSWORD" "$(get_env_value "DB_REAL_PASSWORD")"
else
  set_from_profile_or_default "$PROFILE_FILE" "APP_NAME" "\"Sistem Keuangan MI\""
  set_from_profile_or_default "$PROFILE_FILE" "APP_ENV" "local"
  set_env_value "DB_SAKUMI_MODE" "real"
  set_env_value "DB_CONNECTION" "sakumi_real"
  set_from_profile_or_default "$PROFILE_FILE" "DB_HOST" "127.0.0.1"
  set_from_profile_or_default "$PROFILE_FILE" "DB_PORT" "5433"
  set_from_profile_or_default "$PROFILE_FILE" "DB_REAL_DATABASE" "sakumi"
  set_from_profile_or_default "$PROFILE_FILE" "DB_REAL_USERNAME" "sakumi"
  set_from_profile_or_default "$PROFILE_FILE" "DB_REAL_PASSWORD" ""
fi

(
  cd "$ROOT_DIR"
  php artisan config:clear >/dev/null 2>&1 || true
  php artisan cache:clear >/dev/null 2>&1 || true
)

echo "Switched to $TARGET mode."

if [[ "$TARGET" == "real" ]]; then
  provision_real_db
else
  provision_dummy_db
fi

echo "Done."
