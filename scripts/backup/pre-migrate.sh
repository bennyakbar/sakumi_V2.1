#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# Pre-migration backup: creates a pg_dump snapshot before running migrations.
#
# Usage:
#   ./scripts/backup/pre-migrate.sh              # backup only
#   ./scripts/backup/pre-migrate.sh --migrate    # backup then migrate --force
#
# Requires:
#   - DB_SAKUMI_MODE=real in .env
#   - pg_dump available
#   - .pgpass configured (see pgpass.example)
#
# The backup is stored in $DB_BACKUP_DIR with a timestamped filename so you
# can correlate it with the migration that followed.
# ─────────────────────────────────────────────────────────────────────────────
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

# shellcheck source=./common.sh
source "$SCRIPT_DIR/common.sh"

require_cmd pg_dump
require_cmd gzip
require_cmd php

# ── Parse arguments ──────────────────────────────────────────────────────────
RUN_MIGRATE=0
for arg in "$@"; do
  case "$arg" in
    --migrate) RUN_MIGRATE=1 ;;
    *)
      echo "Usage: $0 [--migrate]"
      echo "  --migrate  Run 'php artisan migrate --force' after backup"
      exit 1
      ;;
  esac
done

# ── Safety: only run in real mode ────────────────────────────────────────────
cd "$ROOT_DIR"

ENV_MODE="$(grep -E '^DB_SAKUMI_MODE=' .env 2>/dev/null | cut -d'=' -f2- || true)"
if [[ "$ENV_MODE" != "real" ]]; then
  echo "SKIP: DB_SAKUMI_MODE is '${ENV_MODE:-<unset>}', not 'real'. No backup needed."
  if [[ $RUN_MIGRATE -eq 1 ]]; then
    php artisan migrate
  fi
  exit 0
fi

# ── Check for pending migrations ─────────────────────────────────────────────
MIGRATE_STATUS="$(php artisan migrate:status --no-ansi 2>&1)"
if ! echo "$MIGRATE_STATUS" | grep -q "Pending"; then
  log "INFO" "no pending migrations — skipping pre-migration backup"
  echo "No pending migrations. Nothing to do."
  exit 0
fi

# ── Show migration preview ───────────────────────────────────────────────────
echo "=== Pending migrations ==="
echo "$MIGRATE_STATUS" | grep "Pending" || true
echo
echo "=== SQL preview (migrate --pretend) ==="
php artisan migrate --pretend --no-ansi 2>&1
echo "=== End of SQL preview ==="
echo

# ── Database connection settings ─────────────────────────────────────────────
PGHOST="${PGHOST:-$(grep -E '^DB_REAL_HOST=' .env 2>/dev/null | cut -d'=' -f2- || echo '127.0.0.1')}"
PGPORT="${PGPORT:-$(grep -E '^DB_REAL_PORT=' .env 2>/dev/null | cut -d'=' -f2- || echo '5432')}"
PGDATABASE="${PGDATABASE:-$(grep -E '^DB_REAL_DATABASE=' .env 2>/dev/null | cut -d'=' -f2- || echo 'sakumi_real')}"
PGUSER="${PGUSER:-$(grep -E '^DB_REAL_USERNAME=' .env 2>/dev/null | cut -d'=' -f2- || echo 'sakumi')}"
PGPASSFILE="${PGPASSFILE:-$HOME/.pgpass}"

require_file_mode_600 "$PGPASSFILE"
export PGPASSFILE PGHOST PGPORT PGDATABASE PGUSER

# ── Create backup ────────────────────────────────────────────────────────────
timestamp="$(date +%F_%H%M%S)"
outfile="$DB_BACKUP_DIR/sakumi_pre_migrate_${timestamp}.sql.gz"
tmp_sql="$(mktemp "$DB_BACKUP_DIR/.sakumi_pre_migrate_${timestamp}.XXXXXX.sql")"

cleanup() {
  rm -f "$tmp_sql"
}
trap cleanup EXIT

log "INFO" "pre-migration backup started (db=${PGDATABASE}, host=${PGHOST})"
echo "Creating pre-migration backup..."

if ! pg_dump \
    --format=plain \
    --encoding=UTF8 \
    --no-owner \
    --no-privileges \
    --file="$tmp_sql"; then
  fail "pg_dump failed — aborting migration"
fi

if ! gzip -c9 "$tmp_sql" >"$outfile"; then
  fail "gzip compression failed — aborting migration"
fi

chmod 600 "$outfile" || true

size="$(du -h "$outfile" | awk '{print $1}')"
log "INFO" "pre-migration backup completed (file=${outfile}, size=${size})"
echo "Backup saved: ${outfile} (${size})"
echo

# ── Optionally run migration ─────────────────────────────────────────────────
if [[ $RUN_MIGRATE -eq 1 ]]; then
  echo "Running: php artisan migrate --force"
  log "INFO" "running migration after backup"

  if php artisan migrate --force; then
    log "INFO" "migration completed successfully"
    echo "Migration completed successfully."
  else
    log "ERROR" "migration failed — restore from: ${outfile}"
    echo
    echo "MIGRATION FAILED!"
    echo "Restore from backup: ${outfile}"
    echo "  gunzip -c ${outfile} | psql -h ${PGHOST} -p ${PGPORT} -U ${PGUSER} ${PGDATABASE}"
    exit 1
  fi
else
  echo "Backup complete. To run migration:"
  echo "  php artisan migrate --force"
  echo
  echo "To restore if something goes wrong:"
  echo "  gunzip -c ${outfile} | psql -h ${PGHOST} -p ${PGPORT} -U ${PGUSER} ${PGDATABASE}"
fi
