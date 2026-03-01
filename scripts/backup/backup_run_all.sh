#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

"$SCRIPT_DIR/backup_db.sh"
"$SCRIPT_DIR/backup_files.sh"
"$SCRIPT_DIR/backup_offsite.sh"
