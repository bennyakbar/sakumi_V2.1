#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=./common.sh
source "$SCRIPT_DIR/common.sh"

require_cmd rclone

RCLONE_REMOTE="${RCLONE_REMOTE:-sakumi-remote:sakumi-prod}"
RCLONE_OPTS=(
    --transfers=4
    --checkers=8
    --retries=3
    --low-level-retries=10
    --contimeout=30s
    --timeout=5m
)

log "INFO" "offsite upload started (remote=${RCLONE_REMOTE})"

if ! rclone copy "$DB_BACKUP_DIR" "${RCLONE_REMOTE}/db" "${RCLONE_OPTS[@]}"; then
    fail "offsite upload failed for ${DB_BACKUP_DIR}; local backups kept untouched"
fi

if ! rclone copy "$FILE_BACKUP_DIR" "${RCLONE_REMOTE}/files" "${RCLONE_OPTS[@]}"; then
    fail "offsite upload failed for ${FILE_BACKUP_DIR}; local backups kept untouched"
fi

log "INFO" "offsite upload completed successfully"
