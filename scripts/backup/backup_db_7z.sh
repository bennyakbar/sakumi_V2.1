#!/usr/bin/env bash
# ──────────────────────────────────────────────────────────────
# SAKUMI — PostgreSQL Database Backup (7z, production-safe)
#
# Dumps the PostgreSQL database via pg_dump, compresses with
# 7z at maximum compression, rotates to keep only the last
# N backups, and logs every step.
#
# Credentials: sourced from ~/.pgpass (mode 600) — never
# stored in this script or passed on the command line.
#
# Performance: pg_dump and 7z run under nice/ionice so they
# yield to production workloads.
# ──────────────────────────────────────────────────────────────
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Override backup root BEFORE sourcing common.sh
export BACKUP_ROOT="${BACKUP_ROOT:-/var/backups/sakumi}"
export DB_BACKUP_DIR="${DB_BACKUP_DIR:-$BACKUP_ROOT/db}"
export LOG_DIR="${LOG_DIR:-$BACKUP_ROOT/logs}"

# shellcheck source=./common.sh
source "$SCRIPT_DIR/common.sh"

# ── Dependencies ──────────────────────────────────────────────
require_cmd pg_dump
require_cmd 7z
require_cmd nice
require_cmd ionice
require_cmd find

# ── Configuration ─────────────────────────────────────────────
PGHOST="${PGHOST:-127.0.0.1}"
PGPORT="${PGPORT:-5432}"
PGDATABASE="${PGDATABASE:-sakumi_real}"
PGUSER="${PGUSER:-sakumi}"
PGPASSFILE="${PGPASSFILE:-$HOME/.pgpass}"
RETENTION_COUNT="${RETENTION_COUNT:-14}"

require_file_mode_600 "$PGPASSFILE"
export PGPASSFILE PGHOST PGPORT PGDATABASE PGUSER

# ── Timestamp & paths ────────────────────────────────────────
date_tag="$(date +%Y%m%d_%H%M)"
outfile="$DB_BACKUP_DIR/sakumi_db_${date_tag}.sql.7z"
tmp_sql="$(mktemp "$DB_BACKUP_DIR/.sakumi_db_${date_tag}.XXXXXX.sql")"

# ── Cleanup handler ──────────────────────────────────────────
cleanup() {
    rm -f "$tmp_sql"
}
trap cleanup EXIT

# ── Dump ─────────────────────────────────────────────────────
log "INFO" "database backup started (db=${PGDATABASE}, host=${PGHOST}, port=${PGPORT}, user=${PGUSER})"

if ! nice -n 19 ionice -c 3 pg_dump \
    --format=plain \
    --encoding=UTF8 \
    --no-owner \
    --no-privileges \
    --file="$tmp_sql"; then
    fail "pg_dump failed for database ${PGDATABASE}"
fi

# ── Compress with 7z (max compression, single-thread safe) ───
# -mx=9  : maximum compression
# -mmt=1 : single thread to minimise CPU contention
# -bso0 -bse0 -bsp0 : suppress stdout/stderr/progress noise
if ! nice -n 19 ionice -c 3 7z a -t7z -mx=9 -mmt=1 \
    -bso0 -bse0 -bsp0 \
    "$outfile" "$tmp_sql"; then
    fail "7z compression failed for ${tmp_sql}"
fi

chmod 600 "$outfile" || true
chown root:root "$outfile" 2>/dev/null || true

# ── Count-based retention (keep newest N) ────────────────────
mapfile -t backups < <(
    find "$DB_BACKUP_DIR" -maxdepth 1 -type f -name 'sakumi_db_*.sql.7z' \
        -printf '%T@ %p\n' | sort -rn | awk '{print $2}'
)

deleted=0
if (( ${#backups[@]} > RETENTION_COUNT )); then
    for (( i=RETENTION_COUNT; i<${#backups[@]}; i++ )); do
        rm -f "${backups[$i]}" && (( deleted++ )) || true
    done
fi

log "INFO" "retention cleanup completed (kept=${RETENTION_COUNT}, deleted=${deleted})"

# ── Done ─────────────────────────────────────────────────────
size="$(du -h "$outfile" | awk '{print $1}')"
log "INFO" "database backup completed (file=${outfile}, size=${size})"

exit 0
