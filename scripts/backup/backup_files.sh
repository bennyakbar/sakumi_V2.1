#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
# shellcheck source=./common.sh
source "$SCRIPT_DIR/common.sh"

require_cmd tar
require_cmd find
require_cmd openssl

ENV_ENCRYPTION_KEY_FILE="${ENV_ENCRYPTION_KEY_FILE:-/root/.sakumi_env_backup.key}"
ENV_FILE="${ENV_FILE:-$APP_ROOT/.env}"

date_tag="$(date +%F)"
archive="$FILE_BACKUP_DIR/sakumi_files_${date_tag}.tar.gz"
env_encrypted="$FILE_BACKUP_DIR/sakumi_env_${date_tag}.enc"
tmp_env="$(mktemp "$FILE_BACKUP_DIR/.sakumi_env_${date_tag}.XXXXXX")"

cleanup() {
    if command -v shred >/dev/null 2>&1; then
        shred -u "$tmp_env" 2>/dev/null || rm -f "$tmp_env"
    else
        rm -f "$tmp_env"
    fi
}
trap cleanup EXIT

log "INFO" "file backup started (app_root=${APP_ROOT})"

paths=()
[[ -d "$APP_ROOT/storage" ]] && paths+=("storage")
[[ -d "$APP_ROOT/public/uploads" ]] && paths+=("public/uploads")

if [[ "${#paths[@]}" -eq 0 ]]; then
    fail "no backup path found: expected at least one of storage/ or public/uploads/"
fi

if ! tar -czpf "$archive" -C "$APP_ROOT" "${paths[@]}"; then
    fail "failed creating files archive ${archive}"
fi
chmod 600 "$archive" || true
chown root:root "$archive" 2>/dev/null || true

[[ -f "$ENV_FILE" ]] || fail ".env file not found at ${ENV_FILE}"
require_file_mode_600 "$ENV_ENCRYPTION_KEY_FILE"

cp "$ENV_FILE" "$tmp_env"
chmod 600 "$tmp_env" || true

if ! openssl enc -aes-256-cbc -pbkdf2 -salt \
    -in "$tmp_env" \
    -out "$env_encrypted" \
    -pass "file:${ENV_ENCRYPTION_KEY_FILE}"; then
    fail "failed encrypting .env to ${env_encrypted}"
fi

chmod 600 "$env_encrypted" || true
chown root:root "$env_encrypted" 2>/dev/null || true

deleted_files="$(find "$FILE_BACKUP_DIR" -maxdepth 1 -type f -name 'sakumi_files_*.tar.gz' -mtime +30 -print -delete | wc -l || true)"
deleted_env="$(find "$FILE_BACKUP_DIR" -maxdepth 1 -type f -name 'sakumi_env_*.enc' -mtime +30 -print -delete | wc -l || true)"
log "INFO" "retention cleanup completed (files_deleted=${deleted_files}, env_deleted=${deleted_env})"

archive_size="$(du -h "$archive" | awk '{print $1}')"
env_size="$(du -h "$env_encrypted" | awk '{print $1}')"
log "INFO" "file backup completed (archive=${archive}, archive_size=${archive_size}, env=${env_encrypted}, env_size=${env_size})"
