#!/usr/bin/env bash
# ──────────────────────────────────────────────────────────────
# SAKUMI — Weekly Restore-Test (PostgreSQL)
#
# Validates the most recent 7z backup by:
#   1. Extracting the SQL dump from the newest .sql.7z
#   2. Restoring into a disposable test database
#   3. Running basic sanity checks (table count, row sample)
#   4. Dropping the test database unconditionally
#
# Designed to run weekly via cron.  Uses nice/ionice to avoid
# contention with production.
#
# Exit codes:
#   0 — restore test passed
#   1 — restore test failed (check log for details)
# ──────────────────────────────────────────────────────────────
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

export BACKUP_ROOT="${BACKUP_ROOT:-/var/backups/sakumi}"
export DB_BACKUP_DIR="${DB_BACKUP_DIR:-$BACKUP_ROOT/db}"
export LOG_DIR="${LOG_DIR:-$BACKUP_ROOT/logs}"

# shellcheck source=./common.sh
source "$SCRIPT_DIR/common.sh"

require_cmd 7z
require_cmd psql
require_cmd nice
require_cmd ionice

# ── Configuration ─────────────────────────────────────────────
PGHOST="${PGHOST:-127.0.0.1}"
PGPORT="${PGPORT:-5432}"
PGUSER="${PGUSER:-sakumi}"
PGPASSFILE="${PGPASSFILE:-$HOME/.pgpass}"
TEST_DB="${RESTORE_TEST_DB:-sakumi_restore_test}"

require_file_mode_600 "$PGPASSFILE"
export PGPASSFILE PGHOST PGPORT PGUSER

# ── Locate newest backup ────────────────────────────────────
newest_backup="$(
    find "$DB_BACKUP_DIR" -maxdepth 1 -type f -name 'sakumi_db_*.sql.7z' \
        -printf '%T@ %p\n' | sort -rn | head -1 | awk '{print $2}'
)"

[[ -n "$newest_backup" ]] || fail "no .sql.7z backup found in ${DB_BACKUP_DIR}"

log "INFO" "restore test started (backup=${newest_backup})"

# ── Temporary workspace ──────────────────────────────────────
work_dir="$(mktemp -d "$BACKUP_ROOT/.restore_test.XXXXXX")"

cleanup() {
    # Always attempt to drop the test database
    PGDATABASE=postgres psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" \
        -c "DROP DATABASE IF EXISTS \"${TEST_DB}\";" 2>/dev/null || true
    rm -rf "$work_dir"
}
trap cleanup EXIT

# ── Extract ──────────────────────────────────────────────────
if ! nice -n 19 ionice -c 3 7z x -o"$work_dir" -bso0 -bse0 -bsp0 "$newest_backup"; then
    fail "failed to extract ${newest_backup}"
fi

sql_file="$(find "$work_dir" -maxdepth 1 -type f -name '*.sql' | head -1)"
[[ -n "$sql_file" ]] || fail "no .sql file found inside archive ${newest_backup}"

# ── Create test database ────────────────────────────────────
log "INFO" "creating test database ${TEST_DB}"

# Drop any leftover from a previous failed run
PGDATABASE=postgres psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" \
    -c "DROP DATABASE IF EXISTS \"${TEST_DB}\";" 2>/dev/null || true

if ! PGDATABASE=postgres psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" \
    -c "CREATE DATABASE \"${TEST_DB}\" ENCODING 'UTF8';"; then
    fail "failed to create test database ${TEST_DB}"
fi

# ── Restore ──────────────────────────────────────────────────
log "INFO" "restoring dump into ${TEST_DB}"

if ! nice -n 19 ionice -c 3 \
    PGDATABASE="$TEST_DB" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" \
    --single-transaction \
    --set ON_ERROR_STOP=1 \
    -f "$sql_file" >/dev/null 2>&1; then
    fail "restore into ${TEST_DB} failed — backup may be corrupt"
fi

# ── Sanity checks ───────────────────────────────────────────
log "INFO" "running sanity checks on ${TEST_DB}"

table_count="$(
    PGDATABASE="$TEST_DB" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" \
        -t -A -c "
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_type = 'BASE TABLE';
    "
)"

if [[ -z "$table_count" ]] || (( table_count < 1 )); then
    fail "sanity check failed: no public tables found in ${TEST_DB} (count=${table_count})"
fi

# Check that key application tables exist
missing_tables=()
for tbl in users students academic_years; do
    exists="$(
        PGDATABASE="$TEST_DB" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" \
            -t -A -c "
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = 'public'
              AND table_name = '${tbl}';
        "
    )"
    if [[ "$exists" != "1" ]]; then
        missing_tables+=("$tbl")
    fi
done

if (( ${#missing_tables[@]} > 0 )); then
    fail "sanity check failed: missing key tables: ${missing_tables[*]}"
fi

# Quick row count on users table
user_rows="$(
    PGDATABASE="$TEST_DB" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" \
        -t -A -c "SELECT COUNT(*) FROM users;"
)"

log "INFO" "sanity checks passed (tables=${table_count}, users_rows=${user_rows})"

# ── Cleanup happens in the trap ──────────────────────────────
log "INFO" "restore test PASSED (backup=${newest_backup})"

exit 0
