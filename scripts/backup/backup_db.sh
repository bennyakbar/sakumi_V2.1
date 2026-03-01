#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=./common.sh
source "$SCRIPT_DIR/common.sh"

require_cmd pg_dump
require_cmd gzip
require_cmd find

PGHOST="${PGHOST:-127.0.0.1}"
PGPORT="${PGPORT:-5432}"
PGDATABASE="${PGDATABASE:-sakumi_real}"
PGUSER="${PGUSER:-sakumi}"
PGPASSFILE="${PGPASSFILE:-$HOME/.pgpass}"

require_file_mode_600 "$PGPASSFILE"
export PGPASSFILE PGHOST PGPORT PGDATABASE PGUSER

date_tag="$(date +%F)"
outfile="$DB_BACKUP_DIR/sakumi_db_${date_tag}.sql.gz"
tmp_sql="$(mktemp "$DB_BACKUP_DIR/.sakumi_db_${date_tag}.XXXXXX.sql")"

cleanup() {
    rm -f "$tmp_sql"
}
trap cleanup EXIT

log "INFO" "database backup started (db=${PGDATABASE}, host=${PGHOST}, port=${PGPORT}, user=${PGUSER})"

if ! pg_dump \
    --format=plain \
    --encoding=UTF8 \
    --no-owner \
    --no-privileges \
    --file="$tmp_sql"; then
    fail "pg_dump failed for database ${PGDATABASE}"
fi

if ! gzip -c9 "$tmp_sql" >"$outfile"; then
    fail "gzip compression failed for ${tmp_sql}"
fi

chmod 600 "$outfile" || true
chown root:root "$outfile" 2>/dev/null || true

deleted="$(find "$DB_BACKUP_DIR" -maxdepth 1 -type f -name 'sakumi_db_*.sql.gz' -mtime +30 -print -delete | wc -l || true)"
log "INFO" "retention cleanup completed (deleted=${deleted})"

size="$(du -h "$outfile" | awk '{print $1}')"
log "INFO" "database backup completed (file=${outfile}, size=${size})"
