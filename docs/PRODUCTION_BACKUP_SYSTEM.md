# SAKUMI Production Backup System (Laravel + PostgreSQL on Ubuntu VPS)

This document defines a production-safe backup infrastructure for SAKUMI without changing application logic.

## Scope
- PostgreSQL logical backup (primary priority)
- Laravel file backup (`storage/`, `public/uploads/`)
- Secure `.env` backup (separate encrypted file)
- Offsite replication with `rclone`
- Logging + email alert on failure
- Restore procedures

## Folder Structure
All backup data is stored outside the app root.

```text
/backup/
  db/
    sakumi_db_YYYY-MM-DD.sql.gz
  files/
    sakumi_files_YYYY-MM-DD.tar.gz
    sakumi_env_YYYY-MM-DD.enc
  logs/
    backup.log
```

Required ownership/permissions:
- Owner: `root:root`
- `/backup`, `/backup/db`, `/backup/files`, `/backup/logs`: `700`
- Backup artifacts + key files: `600`

## Scripts
Location:
- [common.sh](/home/abusyauqi/sakumi/scripts/backup/common.sh)
- [backup_db.sh](/home/abusyauqi/sakumi/scripts/backup/backup_db.sh)
- [backup_files.sh](/home/abusyauqi/sakumi/scripts/backup/backup_files.sh)
- [backup_offsite.sh](/home/abusyauqi/sakumi/scripts/backup/backup_offsite.sh)
- [backup_run_all.sh](/home/abusyauqi/sakumi/scripts/backup/backup_run_all.sh)

Make executable:

```bash
sudo chmod 700 /home/abusyauqi/sakumi/scripts/backup/*.sh
```

## 1) Database Backup (Daily 02:00)
`backup_db.sh` performs:
- `pg_dump` logical backup (no service downtime, no blocking table locks in normal operation)
- gzip compression to:
  - `sakumi_db_YYYY-MM-DD.sql.gz`
- save into:
  - `/backup/db/`
- retention:
  - delete files older than 30 days
- failure handling:
  - log error to `/backup/logs/backup.log`
  - send email alert (if `ALERT_EMAIL` is set and `mail` exists)

## 2) File Backup (Secondary)
`backup_files.sh` performs:
- backup of:
  - `storage/`
  - `public/uploads/` (if present)
- archive output:
  - `sakumi_files_YYYY-MM-DD.tar.gz`
- separate `.env` handling:
  - copy to temp
  - encrypt to `sakumi_env_YYYY-MM-DD.enc` using AES-256 (`openssl enc -aes-256-cbc -pbkdf2`)
  - securely remove temp plaintext file
- retention:
  - delete archives older than 30 days
- preserve permissions via `tar -p`

Required env key file:
- default: `/root/.sakumi_env_backup.key`
- permission: `600`

Generate key once:

```bash
sudo sh -c 'openssl rand -hex 32 > /root/.sakumi_env_backup.key'
sudo chmod 600 /root/.sakumi_env_backup.key
```

## 3) Encryption Layer (Recommended)
Database backup encryption example (AES-256):

```bash
openssl enc -aes-256-cbc -pbkdf2 -salt \
  -in /backup/db/sakumi_db_2026-03-01.sql.gz \
  -out /backup/db/sakumi_db_2026-03-01.sql.gz.enc \
  -pass file:/root/.sakumi_backup_encrypt.key
```

Alternative (GPG symmetric):

```bash
gpg --batch --yes --symmetric --cipher-algo AES256 \
  --passphrase-file /root/.sakumi_backup_gpg.pass \
  -o /backup/db/sakumi_db_2026-03-01.sql.gz.gpg \
  /backup/db/sakumi_db_2026-03-01.sql.gz
```

Secure password strategy:
- Store passphrase only in root-owned file (`600`)
- Never hardcode passphrase in script or cron command
- Rotate key/passphrase periodically (for example every 6 months)
- Keep historical decryption keys in an offline password vault

## 4) Offsite Backup (Critical for VPS)
`backup_offsite.sh` uploads:
- `/backup/db/` -> `${RCLONE_REMOTE}/db`
- `/backup/files/` -> `${RCLONE_REMOTE}/files`

Important safety:
- if upload fails, local backup is NOT deleted
- error is logged and alert email is sent

`rclone` config example:
- [rclone.conf.example](/home/abusyauqi/sakumi/scripts/backup/rclone.conf.example)

Default remote path used by script:
- `RCLONE_REMOTE=sakumi-remote:sakumi-prod`

## 5) Monitoring + Failure Alert
All scripts use centralized logging:
- log file: `/backup/logs/backup.log`
- format: `YYYY-MM-DD HH:MM:SS [LEVEL] [SCRIPT] message`

Failure detection:
- `set -Eeuo pipefail`
- explicit command checks (`require_cmd`)
- `fail()` function writes error log and sends email alert

Email alert requirement:
- install local mail utility (`mailutils` or equivalent)
- define `ALERT_EMAIL` in cron environment

## 6) Cron Configuration
Use root crontab:

```bash
sudo crontab -e
```

Recommended entries:

```cron
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
ALERT_EMAIL=ops@example.com

0 2 * * * /home/abusyauqi/sakumi/scripts/backup/backup_db.sh
15 2 * * * /home/abusyauqi/sakumi/scripts/backup/backup_files.sh
35 2 * * * /home/abusyauqi/sakumi/scripts/backup/backup_offsite.sh
```

Reference file:
- [crontab.example](/home/abusyauqi/sakumi/scripts/backup/crontab.example)

## 7) .pgpass Configuration
Example:
- [pgpass.example](/home/abusyauqi/sakumi/scripts/backup/pgpass.example)

Deploy:

```bash
sudo cp /home/abusyauqi/sakumi/scripts/backup/pgpass.example /root/.pgpass
sudo chmod 600 /root/.pgpass
sudo chown root:root /root/.pgpass
```

## Restore Procedure
Precautions before restore:
- stop scheduler/queue workers temporarily to avoid concurrent writes
- put app in maintenance mode if restoring production in-place
- verify target database name and host carefully
- always restore first into staging when possible
- never delete original backup files before successful verification

### Database Restore
1. Decompress backup:

```bash
gunzip -c /backup/db/sakumi_db_YYYY-MM-DD.sql.gz > /tmp/sakumi_restore.sql
```

2. Restore into PostgreSQL:

```bash
PGPASSFILE=/root/.pgpass psql -h 127.0.0.1 -p 5432 -U sakumi -d sakumi_real -f /tmp/sakumi_restore.sql
```

3. Cleanup temporary file:

```bash
shred -u /tmp/sakumi_restore.sql
```

### File Restore
Restore `storage/` and `public/uploads/`:

```bash
tar -xzpf /backup/files/sakumi_files_YYYY-MM-DD.tar.gz -C /home/abusyauqi/sakumi
```

Restore `.env`:

```bash
openssl enc -d -aes-256-cbc -pbkdf2 \
  -in /backup/files/sakumi_env_YYYY-MM-DD.enc \
  -out /home/abusyauqi/sakumi/.env \
  -pass file:/root/.sakumi_env_backup.key
chmod 600 /home/abusyauqi/sakumi/.env
```

Estimated recovery time (single school VPS):
- DB restore 1-10 GB: 10-45 minutes
- File restore (depending on size): 5-20 minutes
- Validation/restart checks: 5-15 minutes
- Total typical RTO: 20-80 minutes

## Safety Requirements Validation
- No DB locking for normal reads/writes: `pg_dump` logical backup on MVCC snapshot
- No service downtime required for backup scripts
- No active-data deletion: only backup artifacts older than 30 days are removed
- Idempotent execution: rerun safely overwrites current date artifact and keeps previous days
- Root-owned files + restrictive permissions enforced

## Risk Assessment
- Local DB backup reliability: LOW
- Local file backup reliability: LOW
- Offsite upload dependency (network/cloud): MEDIUM
- Key management/encryption operational risk: MEDIUM
- Overall backup architecture risk (with monitoring + offsite): LOW to MEDIUM

## Quick Install Checklist
1. Install tools:

```bash
sudo apt update
sudo apt install -y postgresql-client rclone mailutils
```

2. Deploy credentials:
- `/root/.pgpass` (600)
- `/root/.sakumi_env_backup.key` (600)
- `/root/.config/rclone/rclone.conf` (600)

3. Create backup root:

```bash
sudo mkdir -p /backup/{db,files,logs}
sudo chown -R root:root /backup
sudo chmod 700 /backup /backup/db /backup/files /backup/logs
```

4. Set cron from [crontab.example](/home/abusyauqi/sakumi/scripts/backup/crontab.example).
5. Run dry test:

```bash
sudo /home/abusyauqi/sakumi/scripts/backup/backup_db.sh
sudo /home/abusyauqi/sakumi/scripts/backup/backup_files.sh
sudo /home/abusyauqi/sakumi/scripts/backup/backup_offsite.sh
```
