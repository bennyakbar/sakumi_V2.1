#!/usr/bin/env bash
set -Eeuo pipefail

umask 077

BACKUP_ROOT="${BACKUP_ROOT:-/backup}"
DB_BACKUP_DIR="${DB_BACKUP_DIR:-$BACKUP_ROOT/db}"
FILE_BACKUP_DIR="${FILE_BACKUP_DIR:-$BACKUP_ROOT/files}"
LOG_DIR="${LOG_DIR:-$BACKUP_ROOT/logs}"
LOG_FILE="${LOG_FILE:-$LOG_DIR/backup.log}"
ALERT_EMAIL="${ALERT_EMAIL:-}"

SCRIPT_NAME="$(basename "${BASH_SOURCE[1]:-${0}}")"
HOSTNAME_FQDN="$(hostname -f 2>/dev/null || hostname)"

mkdir -p "$DB_BACKUP_DIR" "$FILE_BACKUP_DIR" "$LOG_DIR"
chmod 700 "$BACKUP_ROOT" "$DB_BACKUP_DIR" "$FILE_BACKUP_DIR" "$LOG_DIR" || true

log() {
    local level="$1"
    shift
    printf '%s [%s] [%s] %s\n' \
        "$(date '+%Y-%m-%d %H:%M:%S')" \
        "$level" \
        "$SCRIPT_NAME" \
        "$*" >>"$LOG_FILE"
}

send_alert() {
    local subject="$1"
    local body="$2"

    if [[ -z "${ALERT_EMAIL}" ]]; then
        return 0
    fi

    if ! command -v mail >/dev/null 2>&1; then
        log "WARN" "mail command not found; cannot send alert to ${ALERT_EMAIL}"
        return 0
    fi

    printf '%s\n' "$body" | mail -s "$subject" "$ALERT_EMAIL" || \
        log "WARN" "failed sending alert email to ${ALERT_EMAIL}"
}

fail() {
    local msg="$1"
    log "ERROR" "$msg"
    send_alert "[SAKUMI BACKUP FAILED] ${SCRIPT_NAME} on ${HOSTNAME_FQDN}" "$msg"
    exit 1
}

require_cmd() {
    local cmd="$1"
    command -v "$cmd" >/dev/null 2>&1 || fail "required command not found: $cmd"
}

require_file_mode_600() {
    local f="$1"
    [[ -f "$f" ]] || fail "required file not found: $f"
    local mode
    mode="$(stat -c '%a' "$f")"
    [[ "$mode" == "600" ]] || fail "insecure permission on $f (expected 600, got $mode)"
}

on_error() {
    local exit_code=$?
    local line_no=$1
    fail "unexpected failure at line ${line_no}, exit code ${exit_code}"
}

trap 'on_error $LINENO' ERR
