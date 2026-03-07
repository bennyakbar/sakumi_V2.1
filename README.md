# SAKUMI - Sistem Keuangan Sekolah

## English

### Overview

SAKUMI is a y `MI`, `RA`, `DTA`.

### What This App Covers

1. Student and fee master data management.
2. Monthly obligation and invoice generation.
3. Income/expense transaction recording.
4. Receipt printing and reprint controls.
5. Settlement and reconciliation.
6. Daily, monthly, and arrears reporting.
7. Audit trail and permission-based access.

### Role Overview

- `super_admin`: full system control.
- `operator_tu`: daily operations and master data.
- `bendahara`: finance operations, settlements, reporting.
- `kepala_sekolah`: executive review and monitoring.
- `auditor`: read-only audit/report access.

### Daily Operational Flow (Non-Technical)

1. Verify users and master data.
2. Generate obligations/invoices for active period.
3. Record payments and print receipts.
4. Close settlements and reconcile totals.
5. Review reports and audit events.

### Default Login

Seeded by `FixedLoginSeeder`:

- Super Admin: `superadmin@sakumi.local` / `SuperAdmin#2026`
- Admin TU MI: `admin.tu.mi@sakumi.local` / `AdminTU-MI#2026`
- Admin TU RA: `admin.tu.ra@sakumi.local` / `AdminTU-RA#2026`
- Admin TU DTA: `admin.tu.dta@sakumi.local` / `AdminTU-DTA#2026`
- Staff: `staff@sakumi.local` / `Staff#2026`
- Bendahara: `bendahara@sakumi.local` / `Bendahara#2026`
- Kepala Sekolah: `kepala.sekolah@sakumi.local` / `KepalaSekolah#2026`

Note: `admin_tu_mi`, `admin_tu_ra`, and `admin_tu_dta` are unit-scoped operational admin roles (not `super_admin`).
Legacy login `admin.tu@sakumi.local` is deprecated and intentionally retired (disabled/archived) by seeder.

Recovery commands if login/roles are inconsistent:

```bash
php artisan db:seed --class=Database\\Seeders\\UnitSeeder
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder
php artisan db:seed --class=Database\\Seeders\\FixedLoginSeeder
php artisan permission:cache-reset
```

### Quick Start (Developer)

Prerequisites:

- PHP `>=8.2`
- Composer
- Node.js + npm
- SQLite (dummy mode)
- PostgreSQL (real mode)

Setup:

```bash
composer install
npm install
cp .env.example .env
cp .env.example .env.dummy
cp .env.example .env.real
php artisan key:generate
```

Start in dummy mode (recommended for local):

```bash
./start.sh dummy
```

Start in real mode (PostgreSQL via Docker):

```bash
./start.sh real
```

Start with current mode (no switch):

```bash
./start.sh
```

App URL: `http://127.0.0.1:8002`

Switch database without starting server:

```bash
./scripts/switch-env.sh dummy
./scripts/switch-env.sh real
./scripts/switch-env.sh          # show current mode
```

### Database Profiles and Safety

SAKUMI enforces strict profile separation to avoid cross-environment mistakes.

- `DB_SAKUMI_MODE=dummy` -> `sakumi_dummy` -> SQLite (`database/sakumi_dummy.sqlite`)
- `DB_SAKUMI_MODE=real` -> `sakumi_real` -> PostgreSQL

Safety rules:

- Mode must be explicitly `dummy` or `real`; otherwise app fails at boot.
- In `dummy` mode, writes to `sakumi_real` are blocked.
- In `real` mode, writes to `sakumi_dummy` are blocked.

Switch profiles:

```bash
./start.sh dummy          # switch + start server
./start.sh real           # switch + start server
./scripts/switch-env.sh dummy   # switch only
./scripts/switch-env.sh real    # switch only
./scripts/switch-env.sh         # show current mode
```

Script behavior:

- Edits `.env` in-place (preserves `APP_KEY` across switches)
- No confirmation prompt required
- Clears Laravel config/cache
- **Switching to `real`**: auto-starts the Docker PostgreSQL container (`sakumi_db`) and runs migrations
- **Switching to `dummy`**: auto-migrates and seeds the SQLite dummy database

Real mode PostgreSQL example (`.env.real`):

```env
APP_ENV=production
DB_SAKUMI_MODE=real
DB_CONNECTION=sakumi_real

DB_HOST=127.0.0.1
DB_PORT=5432
DB_REAL_DATABASE=sakumi_real
DB_REAL_USERNAME=sakumi
DB_REAL_PASSWORD=your_password
```

If PostgreSQL is Docker-published to host `5433`:

```env
DB_HOST=127.0.0.1
DB_PORT=5433
```

If Laravel and PostgreSQL are in the same Docker network:

```env
DB_HOST=tu_db
DB_PORT=5432
```

After env changes:

```bash
php artisan config:clear
```

### Important Commands

Development:

```bash
./start.sh
./stop.sh
php artisan optimize:clear
php artisan test
```

Production preflight:

```bash
bash scripts/preflight-prod.sh
# Optional, include full test suite:
bash scripts/preflight-prod.sh --with-tests
```

Database/ops:

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=SettingsSeeder
php artisan db:seed --class=FixedLoginSeeder
php artisan app:init-dummy
php artisan obligations:generate --unit=MI
php artisan arrears:remind
```

Backup and permissions:

```bash
php artisan backup:run
php artisan backup:list
php artisan permission:show
php artisan permission:cache-reset
```

### Scheduled Jobs

Defined in `routes/console.php`:

- `obligations:generate` monthly (`00:00`, day 1)
- `arrears:remind` weekly (Monday, `09:00`)
- `backup:run` daily (`02:00`)

Notes:

- `obligations:generate` requires `--unit` (example: `--unit=MI`).
- Ensure scheduler setup reflects unit requirements.

Production scheduler cron:

```bash
* * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1
```

### Health and Monitoring

- Liveness endpoint: `GET /health/live`
- Diagnostic endpoint: `GET /health` (authenticated + restricted role)

### Troubleshooting

- Port `8002` already used:

```bash
./stop.sh
```

- Frontend assets not loading:

```bash
npm install
npm run dev
```

- Database connection issues:
- Verify `DB_SAKUMI_MODE`
- Verify `DB_HOST` and `DB_PORT` match your topology
- Run `php artisan config:clear`

- Migration/seed issues:
- Prefer non-destructive flow: `php artisan migrate` then targeted seeders
- Avoid `migrate:fresh` on non-disposable data

### Architecture Pointers (Contributors)

- `app/Http/Controllers/`: feature controllers
- `app/Services/`: business rules for transactions/invoices/receipts
- `app/Console/Commands/`: operational automation commands
- `database/migrations/`: schema evolution
- `database/seeders/`: baseline and dummy test data
- `scripts/switch-env.sh`: safety-first profile switching

### Portable Transfer (Cross-OS)

To transfer SAKUMI to another PC (Windows/macOS/Linux):

1. Create a clean zip (excludes `node_modules`, `vendor`, `.git`):

```bash
zip -r sakumi_transfer.zip . -x "./node_modules/*" -x "./vendor/*" -x "./.git/*" -x "./storage/logs/*"
```

2. On the target PC with Docker Desktop installed:

```powershell
unzip sakumi_transfer.zip -d sakumi
cd sakumi
docker compose -f portable-transfer-kit-docker/docker-compose.yml up -d --build
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app composer install
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec node sh -lc "npm install && npm run build"
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan key:generate --force
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan migrate --force
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan db:seed --class=Database\\Seeders\\UnitSeeder --force
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan db:seed --class=Database\\Seeders\\SettingsSeeder --force
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan db:seed --class=Database\\Seeders\\FixedLoginSeeder --force
```

3. Access: `http://localhost:8080`

### Cloud Deployment Guide

Requirements:

- VPS/Cloud server (DigitalOcean, AWS Lightsail, Hetzner, etc.)
- Ubuntu 22.04/24.04, min 1GB RAM
- Domain name pointed to server IP

Server dependencies:

- PHP 8.2+ (`pgsql`, `pdo_pgsql`, `mbstring`, `xml`, `zip`)
- PostgreSQL 15/16
- Nginx
- Composer, Node.js 20+
- Certbot (Let's Encrypt SSL)

Deploy steps:

```bash
# 1. Clone repository
git clone <repo-url> /var/www/sakumi
cd /var/www/sakumi

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. Configure environment
cp .env.real .env
# Edit .env: set APP_URL, DB credentials, APP_ENV=production, APP_DEBUG=false
php artisan key:generate

# 4. Setup database
php artisan migrate --force
php artisan db:seed --class=UnitSeeder --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=SettingsSeeder --force
php artisan db:seed --class=FixedLoginSeeder --force

# 5. Optimize
php artisan optimize
php artisan storage:link
chown -R www-data:www-data storage bootstrap/cache

# 6. Setup scheduler (add to crontab)
# * * * * * php /var/www/sakumi/artisan schedule:run >> /dev/null 2>&1
```

Production `.env` example:

```env
APP_NAME="Sistem Keuangan MI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_SAKUMI_MODE=real
DB_HOST=127.0.0.1
DB_PORT=5432
DB_REAL_DATABASE=sakumi
DB_REAL_USERNAME=sakumi
DB_REAL_PASSWORD=<strong-password>

SESSION_DRIVER=database
CACHE_STORE=database
```

Security checklist:

- `APP_DEBUG=false`
- Strong database password
- SSL/HTTPS via Let's Encrypt
- Firewall: only ports 22, 80, 443
- Run `bash scripts/preflight-prod.sh` before deploy

Preflight check:

```bash
bash scripts/preflight-prod.sh
bash scripts/preflight-prod.sh --with-tests
```

### Security and CI

- OWASP ZAP baseline workflow: `.github/workflows/zap-baseline.yml`
- Pipeline fails on Medium/High findings
- Uploads `zap-baseline-report` artifact

---

## Bahasa Indonesia

### Ringkasan

SAKUMI adalah aplikasi web keuangan sekolah untuk mengelola tagihan siswa, pembayaran, kuitansi, settlement, dan laporan dalam satu sistem.

Didesain untuk tim operasional sekolah sekaligus tim teknis.

### Gambaran Singkat

- Tujuan: operasional keuangan sekolah yang terstruktur, ter-audit, dan berbasis peran.
- Pengguna utama: TU/Admin, Operator, Bendahara, Kepala Sekolah, Auditor.
- Modul utama: master data, kewajiban/invoice, transaksi, kuitansi, settlement, laporan.
- Mendukung multi-unit: `MI`, `RA`, `DTA`.

### Cakupan Aplikasi

1. Kelola master data siswa dan biaya.
2. Generate kewajiban bulanan dan invoice.
3. Catat transaksi pemasukan/pengeluaran.
4. Cetak dan cetak ulang kuitansi (sesuai kontrol akses).
5. Settlement dan rekonsiliasi.
6. Laporan harian, bulanan, dan tunggakan.
7. Audit trail dan kontrol akses berbasis permission.

### Ringkasan Peran

- `super_admin`: kontrol penuh sistem.
- `operator_tu`: operasional harian dan master data.
- `bendahara`: operasional keuangan, settlement, pelaporan.
- `kepala_sekolah`: review dan monitoring eksekutif.
- `auditor`: akses baca untuk audit/laporan.

### Alur Operasional Harian (Non-Teknis)

1. Verifikasi user dan master data.
2. Generate kewajiban/invoice untuk periode aktif.
3. Input pembayaran dan cetak kuitansi.
4. Tutup settlement dan rekonsiliasi total.
5. Review laporan dan event audit.

### Akun Login Default

Di-seed oleh `FixedLoginSeeder`:

- Super Admin: `superadmin@sakumi.local` / `SuperAdmin#2026`
    - Admin TU MI: `admin.tu.mi@sakumi.local` / `AdminTU-MI#2026`
- Admin TU RA: `admin.tu.ra@sakumi.local` / `AdminTU-RA#2026`
- Admin TU DTA: `admin.tu.dta@sakumi.local` / `AdminTU-DTA#2026`
- Staff: `staff@sakumi.local` / `Staff#2026`
- Bendahara: `bendahara@sakumi.local` / `Bendahara#2026`
- Kepala Sekolah: `kepala.sekolah@sakumi.local` / `KepalaSekolah#2026`

Catatan: `admin_tu_mi`, `admin_tu_ra`, dan `admin_tu_dta` adalah role admin operasional per unit (bukan `super_admin`).
Login legacy `admin.tu@sakumi.local` sudah deprecated dan sengaja dipensiunkan (disabled/archived) oleh seeder.

Command recovery jika data login/role tidak konsisten:

```bash
php artisan db:seed --class=Database\\Seeders\\UnitSeeder
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder
php artisan db:seed --class=Database\\Seeders\\FixedLoginSeeder
php artisan permission:cache-reset
```

### Quick Start (Developer)

Prasyarat:

- PHP `>=8.2`
- Composer
- Node.js + npm
- SQLite (mode dummy)
- PostgreSQL (mode real)

Setup:

```bash
composer install
npm install
cp .env.example .env
cp .env.example .env.dummy
cp .env.example .env.real
php artisan key:generate
```

Jalankan mode dummy (disarankan untuk lokal):

```bash
./start.sh dummy
```

Jalankan mode real (PostgreSQL via Docker):

```bash
./start.sh real
```

Jalankan dengan mode saat ini (tanpa switch):

```bash
./start.sh
```

URL aplikasi: `http://127.0.0.1:8002`

Switch database tanpa start server:

```bash
./scripts/switch-env.sh dummy
./scripts/switch-env.sh real
./scripts/switch-env.sh          # lihat mode saat ini
```

### Profil Database dan Keamanan

SAKUMI menerapkan pemisahan profil DB yang ketat untuk mencegah kesalahan lintas environment.

- `DB_SAKUMI_MODE=dummy` -> `sakumi_dummy` -> SQLite (`database/sakumi_dummy.sqlite`)
- `DB_SAKUMI_MODE=real` -> `sakumi_real` -> PostgreSQL

Aturan keamanan:

- Mode harus eksplisit `dummy` atau `real`; jika tidak valid, aplikasi gagal saat boot.
- Di mode `dummy`, write ke `sakumi_real` diblokir.
- Di mode `real`, write ke `sakumi_dummy` diblokir.

Ganti profil environment:

```bash
./start.sh dummy          # switch + start server
./start.sh real           # switch + start server
./scripts/switch-env.sh dummy   # switch saja
./scripts/switch-env.sh real    # switch saja
./scripts/switch-env.sh         # lihat mode saat ini
```

Perilaku script:

- Edit `.env` langsung (APP_KEY tetap terjaga saat switch)
- Tidak perlu konfirmasi ketik apapun
- Membersihkan cache/config Laravel
- **Pindah ke `real`**: otomatis start container Docker PostgreSQL (`sakumi_db`) dan jalankan migration
- **Pindah ke `dummy`**: otomatis migrate + seed database SQLite dummy

Contoh minimal mode real PostgreSQL (`.env.real`):

```env
APP_ENV=production
DB_SAKUMI_MODE=real
DB_CONNECTION=sakumi_real

DB_HOST=127.0.0.1
DB_PORT=5432
DB_REAL_DATABASE=sakumi_real
DB_REAL_USERNAME=sakumi
DB_REAL_PASSWORD=your_password
```

Jika PostgreSQL di Docker expose ke host port `5433`:

```env
DB_HOST=127.0.0.1
DB_PORT=5433
```

Jika Laravel dan PostgreSQL berada di network Docker yang sama:

```env
DB_HOST=tu_db
DB_PORT=5432
```

Setelah mengubah env:

```bash
php artisan config:clear
```

### Command Penting

Development:

```bash
./start.sh
./stop.sh
php artisan optimize:clear
php artisan test
```

Database/operasional:

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=SettingsSeeder
php artisan db:seed --class=FixedLoginSeeder
php artisan app:init-dummy
php artisan obligations:generate --unit=MI
php artisan arrears:remind
```

Backup dan permission:

```bash
php artisan backup:run
php artisan backup:list
php artisan permission:show
php artisan permission:cache-reset
```

### Scheduled Jobs

Didefinisikan di `routes/console.php`:

- `obligations:generate` bulanan (`00:00`, tanggal 1)
- `arrears:remind` mingguan (Senin, `09:00`)
- `backup:run` harian (`02:00`)

Catatan:

- `obligations:generate` wajib `--unit` (contoh: `--unit=MI`).
- Pastikan setup scheduler mempertimbangkan kebutuhan unit.

Cron scheduler production:

```bash
* * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1
```

### Health dan Monitoring

- Endpoint liveness: `GET /health/live`
- Endpoint diagnostik: `GET /health` (authenticated + role terbatas)

### Troubleshooting

- Port `8002` sudah dipakai:

```bash
./stop.sh
```

- Asset frontend tidak muncul:

```bash
npm install
npm run dev
```

- Masalah koneksi database:
- Cek `DB_SAKUMI_MODE`
- Cek `DB_HOST` dan `DB_PORT` sesuai topologi
- Jalankan `php artisan config:clear`

- Masalah migration/seed:
- Gunakan flow non-destruktif dulu: `php artisan migrate` lalu seeder spesifik
- Hindari `migrate:fresh` pada data non-disposable

### Poin Arsitektur (Kontributor)

- `app/Http/Controllers/`: controller fitur
- `app/Services/`: business rules transaksi/invoice/kuitansi
- `app/Console/Commands/`: otomatisasi operasional
- `database/migrations/`: evolusi skema
- `database/seeders/`: baseline data dan dummy testing
- `scripts/switch-env.sh`: switching profil dengan guard keamanan

### Transfer Portabel (Lintas OS)

Untuk memindahkan SAKUMI ke PC lain (Windows/macOS/Linux):

1. Buat zip bersih (tanpa `node_modules`, `vendor`, `.git`):

```bash
zip -r sakumi_transfer.zip . -x "./node_modules/*" -x "./vendor/*" -x "./.git/*" -x "./storage/logs/*"
```

2. Di PC tujuan yang sudah ada Docker Desktop:

```powershell
unzip sakumi_transfer.zip -d sakumi
cd sakumi
docker compose -f portable-transfer-kit-docker/docker-compose.yml up -d --build
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app composer install
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec node sh -lc "npm install && npm run build"
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan key:generate --force
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan migrate --force
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan db:seed --class=Database\\Seeders\\UnitSeeder --force
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan db:seed --class=Database\\Seeders\\SettingsSeeder --force
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan db:seed --class=Database\\Seeders\\FixedLoginSeeder --force
```

3. Akses: `http://localhost:8080`

### Panduan Deploy ke Cloud

Kebutuhan:

- VPS/Cloud server (DigitalOcean, AWS Lightsail, Hetzner, dll)
- Ubuntu 22.04/24.04, min 1GB RAM
- Domain yang sudah diarahkan ke IP server

Dependensi server:

- PHP 8.2+ (`pgsql`, `pdo_pgsql`, `mbstring`, `xml`, `zip`)
- PostgreSQL 15/16
- Nginx
- Composer, Node.js 20+
- Certbot (SSL gratis Let's Encrypt)

Langkah deploy:

```bash
# 1. Clone repository
git clone <repo-url> /var/www/sakumi
cd /var/www/sakumi

# 2. Install dependensi
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. Konfigurasi environment
cp .env.real .env
# Edit .env: set APP_URL, kredensial DB, APP_ENV=production, APP_DEBUG=false
php artisan key:generate

# 4. Setup database
php artisan migrate --force
php artisan db:seed --class=UnitSeeder --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=SettingsSeeder --force
php artisan db:seed --class=FixedLoginSeeder --force

# 5. Optimasi
php artisan optimize
php artisan storage:link
chown -R www-data:www-data storage bootstrap/cache

# 6. Setup scheduler (tambahkan ke crontab)
# * * * * * php /var/www/sakumi/artisan schedule:run >> /dev/null 2>&1
```

Contoh `.env` production:

```env
APP_NAME="Sistem Keuangan MI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_SAKUMI_MODE=real
DB_HOST=127.0.0.1
DB_PORT=5432
DB_REAL_DATABASE=sakumi
DB_REAL_USERNAME=sakumi
DB_REAL_PASSWORD=<password-kuat>

SESSION_DRIVER=database
CACHE_STORE=database
```

Checklist keamanan:

- `APP_DEBUG=false`
- Password database yang kuat
- SSL/HTTPS via Let's Encrypt
- Firewall: hanya port 22, 80, 443
- Jalankan `bash scripts/preflight-prod.sh` sebelum deploy

Preflight check:

```bash
bash scripts/preflight-prod.sh
bash scripts/preflight-prod.sh --with-tests
```

### Keamanan dan CI

- Workflow OWASP ZAP baseline: `.github/workflows/zap-baseline.yml`
- Pipeline gagal jika ada temuan Medium/High
- Mengunggah artifact `zap-baseline-report`

## License

MIT.
