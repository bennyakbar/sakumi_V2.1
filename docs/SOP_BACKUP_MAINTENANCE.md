# SOP PEMELIHARAAN BACKUP & RESTORASI DATABASE
## Standar Operasional Prosedur — Backup Otomatis, Verifikasi, dan Pemulihan

**Versi:** 1.0
**Tanggal Efektif:** 3 Maret 2026
**Penanggung Jawab:** Super Administrator / Pengelola Server
**Klasifikasi:** Internal — Rahasia (Confidential)

---

## INFORMASI DOKUMEN

| Atribut | Keterangan |
|---|---|
| **Sistem** | SAKUMI — Sistem Administrasi Keuangan Madrasah Ibtidaiyah |
| **Stack** | Laravel 11 + PostgreSQL pada Ubuntu VPS |
| **Cakupan** | Backup harian (7z), restore-test mingguan, pemulihan darurat |
| **Referensi Terkait** | `PRODUCTION_BACKUP_SYSTEM.md`, `MAINTENANCE_CHECKLIST.md`, `SOP_SAKUMI.md` |

---

## DAFTAR ISI

1. [BAB I — Pendahuluan](#bab-i--pendahuluan)
2. [BAB II — Arsitektur Backup](#bab-ii--arsitektur-backup)
3. [BAB III — Instalasi & Persiapan Awal](#bab-iii--instalasi--persiapan-awal)
4. [BAB IV — SOP Backup Harian Otomatis](#bab-iv--sop-backup-harian-otomatis)
5. [BAB V — SOP Restore-Test Mingguan](#bab-v--sop-restore-test-mingguan)
6. [BAB VI — SOP Pemulihan Darurat (Disaster Recovery)](#bab-vi--sop-pemulihan-darurat-disaster-recovery)
7. [BAB VII — Monitoring & Eskalasi](#bab-vii--monitoring--eskalasi)
8. [BAB VIII — Pemeliharaan Berkala](#bab-viii--pemeliharaan-berkala)
9. [BAB IX — Formulir & Checklist](#bab-ix--formulir--checklist)

---

## BAB I — Pendahuluan

### 1.1 Tujuan
SOP ini mengatur seluruh prosedur teknis untuk:
- Backup otomatis database PostgreSQL secara harian dengan kompresi 7z
- Verifikasi otomatis integritas backup setiap minggu
- Pemulihan database dari backup saat terjadi bencana atau kerusakan data
- Monitoring keberhasilan/kegagalan backup dan eskalasi insiden

### 1.2 Prinsip Keamanan
1. **Tidak ada kredensial dalam skrip** — Semua autentikasi PostgreSQL menggunakan file `.pgpass` (permission `600`).
2. **Production-safe** — Seluruh operasi backup menggunakan `nice -n 19` dan `ionice -c 3` sehingga mengalah kepada beban kerja produksi.
3. **Isolasi restore-test** — Validasi dilakukan pada database disposable terpisah, tidak menyentuh database produksi.
4. **Retensi terbatas** — Hanya 14 backup terbaru yang disimpan, mencegah disk penuh.

### 1.3 Inventaris Skrip

| Skrip | Lokasi | Fungsi | Jadwal |
|---|---|---|---|
| `common.sh` | `scripts/backup/common.sh` | Library bersama (logging, error handling, validasi) | — |
| `backup_db_7z.sh` | `scripts/backup/backup_db_7z.sh` | Backup database + kompresi 7z | Harian 02:45 |
| `restore_test.sh` | `scripts/backup/restore_test.sh` | Validasi restore ke database dummy | Minggu 04:00 |
| `backup_db.sh` | `scripts/backup/backup_db.sh` | Backup database + gzip (legacy) | Harian 02:00 |
| `backup_files.sh` | `scripts/backup/backup_files.sh` | Backup storage + .env terenkripsi | Harian 02:15 |
| `backup_offsite.sh` | `scripts/backup/backup_offsite.sh` | Upload ke remote via rclone | Harian 02:35 |

### 1.4 Struktur Direktori Backup

```text
/var/backups/sakumi/          ← Root backup (7z)
  db/
    sakumi_db_YYYYMMDD_HHMM.sql.7z
  logs/
    backup.log

/backup/                      ← Root backup (legacy gzip)
  db/
    sakumi_db_YYYY-MM-DD.sql.gz
  files/
    sakumi_files_YYYY-MM-DD.tar.gz
    sakumi_env_YYYY-MM-DD.enc
  logs/
    backup.log
```

Seluruh direktori dan file backup dimiliki `root:root` dengan permission `700` (direktori) dan `600` (file).

---

## BAB II — Arsitektur Backup

### 2.1 Alur Backup Harian (7z)

```
[Cron 02:45] → backup_db_7z.sh
       │
       ├─ 1. Validasi prasyarat (pg_dump, 7z, .pgpass mode 600)
       ├─ 2. pg_dump → file .sql sementara (nice/ionice)
       ├─ 3. 7z compress → .sql.7z (mx=9, mmt=1)
       ├─ 4. Set permission 600, chown root:root
       ├─ 5. Hapus backup lama (simpan 14 terbaru)
       ├─ 6. Catat ke /var/backups/sakumi/logs/backup.log
       └─ 7. Kirim email alert jika GAGAL
```

### 2.2 Alur Restore-Test Mingguan

```
[Cron Minggu 04:00] → restore_test.sh
       │
       ├─ 1. Cari file .sql.7z terbaru
       ├─ 2. Ekstrak ke direktori sementara
       ├─ 3. CREATE DATABASE sakumi_restore_test
       ├─ 4. psql restore (single-transaction, ON_ERROR_STOP)
       ├─ 5. Sanity check:
       │     ├─ Hitung tabel publik (harus > 0)
       │     ├─ Verifikasi tabel kunci: users, students, academic_years
       │     └─ Sample row count dari tabel users
       ├─ 6. DROP DATABASE sakumi_restore_test
       ├─ 7. Catat PASSED/FAILED ke log
       └─ 8. Bersihkan file sementara (EXIT trap)
```

### 2.3 Perbandingan Metode Kompresi

| Metode | Skrip | Rasio Kompresi | CPU | Kegunaan |
|---|---|---|---|---|
| gzip -c9 | `backup_db.sh` | Baik (~60-70%) | Rendah | Backup cepat, kompatibilitas tinggi |
| 7z -mx=9 -mmt=1 | `backup_db_7z.sh` | Sangat baik (~75-85%) | Sedang (1 thread) | Backup hemat disk, arsip jangka panjang |

---

## BAB III — Instalasi & Persiapan Awal

### 3.1 Prasyarat Paket

```bash
sudo apt update
sudo apt install -y postgresql-client p7zip-full mailutils
```

Verifikasi:

```bash
pg_dump --version     # harus tersedia
7z --help             # harus tersedia
```

### 3.2 Konfigurasi Kredensial PostgreSQL (.pgpass)

**Langkah 1 — Buat file `.pgpass`:**

```bash
sudo cp /home/abusyauqi/sakumi/scripts/backup/pgpass.example /root/.pgpass
```

**Langkah 2 — Edit dan isi password sebenarnya:**

```bash
sudo nano /root/.pgpass
```

Format isi file:

```
# hostname:port:database:username:password
127.0.0.1:5432:sakumi_real:sakumi:PASSWORD_SESUNGGUHNYA
127.0.0.1:5432:postgres:sakumi:PASSWORD_SESUNGGUHNYA
```

> Baris kedua (`postgres`) diperlukan oleh `restore_test.sh` untuk CREATE/DROP database test.

**Langkah 3 — Amankan permission:**

```bash
sudo chmod 600 /root/.pgpass
sudo chown root:root /root/.pgpass
```

**Langkah 4 — Verifikasi koneksi:**

```bash
sudo PGPASSFILE=/root/.pgpass psql -h 127.0.0.1 -p 5432 -U sakumi -d sakumi_real -c "SELECT 1;"
```

> Jika berhasil, output: `1`. Jika gagal, periksa password dan firewall.

### 3.3 Persiapan Direktori Backup

```bash
sudo mkdir -p /var/backups/sakumi/{db,logs}
sudo chown -R root:root /var/backups/sakumi
sudo chmod 700 /var/backups/sakumi /var/backups/sakumi/db /var/backups/sakumi/logs
```

### 3.4 Amankan Permission Skrip

```bash
sudo chmod 700 /home/abusyauqi/sakumi/scripts/backup/*.sh
sudo chown root:root /home/abusyauqi/sakumi/scripts/backup/*.sh
```

### 3.5 Konfigurasi Cron

```bash
sudo crontab -e
```

Tambahkan baris berikut:

```cron
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
ALERT_EMAIL=ops@example.com

# Backup database harian (7z, /var/backups/sakumi)
45 2 * * * /home/abusyauqi/sakumi/scripts/backup/backup_db_7z.sh

# Restore-test mingguan (setiap Minggu)
0 4 * * 0 /home/abusyauqi/sakumi/scripts/backup/restore_test.sh
```

> **Catatan:** Jika menjalankan backup gzip (legacy) dan 7z secara bersamaan, pastikan jadwal tidak bertabrakan. Jadwal default:
> - `02:00` — `backup_db.sh` (gzip)
> - `02:15` — `backup_files.sh`
> - `02:35` — `backup_offsite.sh`
> - `02:45` — `backup_db_7z.sh` (7z)
> - `04:00 Minggu` — `restore_test.sh`

### 3.6 Uji Coba Awal (Dry Run)

Jalankan secara manual untuk memastikan seluruh konfigurasi benar:

```bash
# 1. Uji backup
sudo /home/abusyauqi/sakumi/scripts/backup/backup_db_7z.sh

# 2. Verifikasi file backup tercipta
sudo ls -lah /var/backups/sakumi/db/

# 3. Verifikasi log
sudo tail -5 /var/backups/sakumi/logs/backup.log

# 4. Uji restore-test
sudo /home/abusyauqi/sakumi/scripts/backup/restore_test.sh

# 5. Verifikasi log restore-test
sudo tail -10 /var/backups/sakumi/logs/backup.log
```

**Ekspektasi output log setelah uji coba berhasil:**

```
2026-03-03 02:45:01 [INFO] [backup_db_7z.sh] database backup started (db=sakumi_real, ...)
2026-03-03 02:45:38 [INFO] [backup_db_7z.sh] retention cleanup completed (kept=14, deleted=0)
2026-03-03 02:45:38 [INFO] [backup_db_7z.sh] database backup completed (file=..., size=12M)
2026-03-03 04:00:01 [INFO] [restore_test.sh] restore test started (backup=...)
2026-03-03 04:01:15 [INFO] [restore_test.sh] sanity checks passed (tables=42, users_rows=8)
2026-03-03 04:01:16 [INFO] [restore_test.sh] restore test PASSED (backup=...)
```

---

## BAB IV — SOP Backup Harian Otomatis

### 4.1 Prosedur Normal (Otomatis via Cron)

**Tidak ada tindakan manual diperlukan.** Cron menjalankan `backup_db_7z.sh` setiap hari pukul 02:45.

**Tugas Super Admin setiap pagi:**

1. Periksa log backup:

```bash
sudo tail -5 /var/backups/sakumi/logs/backup.log
```

2. Konfirmasi baris terakhir mengandung: `database backup completed`
3. Jika terdapat `[ERROR]` — lihat [BAB VII — Eskalasi](#73-prosedur-eskalasi-kegagalan).

### 4.2 Prosedur Backup Manual (Ad-hoc)

Jalankan backup di luar jadwal normal, misalnya sebelum deployment atau migrasi:

```bash
sudo /home/abusyauqi/sakumi/scripts/backup/backup_db_7z.sh
```

Verifikasi:

```bash
sudo ls -lht /var/backups/sakumi/db/ | head -5
```

### 4.3 Kustomisasi Parameter

Variabel environment yang dapat di-override:

| Variabel | Default | Keterangan |
|---|---|---|
| `BACKUP_ROOT` | `/var/backups/sakumi` | Root direktori penyimpanan |
| `DB_BACKUP_DIR` | `$BACKUP_ROOT/db` | Lokasi file .sql.7z |
| `LOG_DIR` | `$BACKUP_ROOT/logs` | Lokasi log |
| `PGHOST` | `127.0.0.1` | Host PostgreSQL |
| `PGPORT` | `5432` | Port PostgreSQL |
| `PGDATABASE` | `sakumi_real` | Nama database |
| `PGUSER` | `sakumi` | User PostgreSQL |
| `PGPASSFILE` | `$HOME/.pgpass` | Lokasi file kredensial |
| `RETENTION_COUNT` | `14` | Jumlah backup yang disimpan |
| `ALERT_EMAIL` | *(kosong)* | Email notifikasi kegagalan |

Contoh override di crontab:

```cron
45 2 * * * RETENTION_COUNT=30 /home/abusyauqi/sakumi/scripts/backup/backup_db_7z.sh
```

---

## BAB V — SOP Restore-Test Mingguan

### 5.1 Prosedur Normal (Otomatis via Cron)

Cron menjalankan `restore_test.sh` setiap Minggu pukul 04:00.

**Tugas Super Admin setiap Senin pagi:**

1. Periksa hasil restore-test:

```bash
sudo grep 'restore_test.sh' /var/backups/sakumi/logs/backup.log | tail -5
```

2. Konfirmasi baris mengandung: `restore test PASSED`
3. Jika terdapat `restore test failed` atau `[ERROR]` — lihat [BAB VII — Eskalasi](#73-prosedur-eskalasi-kegagalan).

### 5.2 Prosedur Restore-Test Manual

```bash
sudo /home/abusyauqi/sakumi/scripts/backup/restore_test.sh
```

### 5.3 Apa yang Divalidasi

| Pemeriksaan | Kriteria Lulus |
|---|---|
| Ekstraksi 7z | File .sql berhasil diekstrak tanpa error |
| Restore ke database test | `psql` selesai tanpa error (ON_ERROR_STOP) |
| Jumlah tabel publik | > 0 tabel ditemukan |
| Tabel kunci ada | `users`, `students`, `academic_years` ada |
| Data sampel | Tabel `users` memiliki baris data |

### 5.4 Kustomisasi

| Variabel | Default | Keterangan |
|---|---|---|
| `RESTORE_TEST_DB` | `sakumi_restore_test` | Nama database test (akan di-DROP setelah selesai) |

> **Peringatan:** Database test akan dihapus secara otomatis (EXIT trap). Jangan pernah gunakan nama database produksi pada variabel ini.

---

## BAB VI — SOP Pemulihan Darurat (Disaster Recovery)

### 6.1 Kapan Pemulihan Diperlukan

- Database produksi rusak (corrupt) atau tidak bisa diakses
- Data terhapus secara tidak sengaja
- Server mengalami kegagalan total
- Migrasi database gagal dan rollback tidak memungkinkan

### 6.2 Prosedur Pemulihan dari Backup 7z

**Langkah 1 — Aktifkan mode maintenance:**

```bash
cd /home/abusyauqi/sakumi
php artisan down --secret="maintenance-bypass-token"
```

**Langkah 2 — Hentikan queue worker:**

```bash
sudo supervisorctl stop sakumi-worker:*
```

**Langkah 3 — Identifikasi backup yang akan digunakan:**

```bash
sudo ls -lht /var/backups/sakumi/db/ | head -10
```

Catat nama file yang dipilih (contoh: `sakumi_db_20260303_0245.sql.7z`).

**Langkah 4 — Ekstrak backup:**

```bash
sudo mkdir -p /tmp/sakumi_restore
sudo 7z x -o/tmp/sakumi_restore /var/backups/sakumi/db/sakumi_db_20260303_0245.sql.7z
```

**Langkah 5 — Identifikasi file SQL:**

```bash
ls -la /tmp/sakumi_restore/
```

**Langkah 6 — Restore ke database produksi:**

```bash
# PERINGATAN: Ini akan MENIMPA seluruh data di database produksi.
# Pastikan langkah ini sudah disetujui oleh penanggung jawab.

# Drop dan recreate database
sudo -u postgres psql -c "
  SELECT pg_terminate_backend(pid)
  FROM pg_stat_activity
  WHERE datname = 'sakumi_real' AND pid <> pg_backend_pid();
"
sudo -u postgres psql -c "DROP DATABASE IF EXISTS sakumi_real;"
sudo -u postgres psql -c "CREATE DATABASE sakumi_real OWNER sakumi ENCODING 'UTF8';"

# Restore
sudo PGPASSFILE=/root/.pgpass psql \
  -h 127.0.0.1 -p 5432 -U sakumi -d sakumi_real \
  --single-transaction \
  --set ON_ERROR_STOP=1 \
  -f /tmp/sakumi_restore/*.sql
```

**Langkah 7 — Verifikasi hasil restore:**

```bash
sudo PGPASSFILE=/root/.pgpass psql \
  -h 127.0.0.1 -p 5432 -U sakumi -d sakumi_real \
  -c "SELECT COUNT(*) AS total_tables
      FROM information_schema.tables
      WHERE table_schema = 'public' AND table_type = 'BASE TABLE';"

sudo PGPASSFILE=/root/.pgpass psql \
  -h 127.0.0.1 -p 5432 -U sakumi -d sakumi_real \
  -c "SELECT COUNT(*) AS total_users FROM users;"

sudo PGPASSFILE=/root/.pgpass psql \
  -h 127.0.0.1 -p 5432 -U sakumi -d sakumi_real \
  -c "SELECT COUNT(*) AS total_students FROM students;"
```

**Langkah 8 — Bersihkan cache Laravel:**

```bash
cd /home/abusyauqi/sakumi
php artisan optimize:clear
php artisan optimize
```

**Langkah 9 — Nyalakan kembali layanan:**

```bash
sudo supervisorctl start sakumi-worker:*
php artisan up
```

**Langkah 10 — Bersihkan file sementara:**

```bash
sudo shred -u /tmp/sakumi_restore/*.sql
sudo rm -rf /tmp/sakumi_restore
```

**Langkah 11 — Dokumentasikan insiden:**

Catat pada formulir di [BAB IX](#bab-ix--formulir--checklist):
- Tanggal dan waktu insiden
- Penyebab pemulihan
- Backup yang digunakan (nama file + timestamp)
- Waktu mulai dan selesai pemulihan
- Data yang mungkin hilang (jarak antara backup dan insiden)
- Penanggung jawab

### 6.3 Prosedur Pemulihan dari Backup Gzip (Legacy)

Jika hanya tersedia backup gzip (`.sql.gz`):

```bash
# Ekstrak
gunzip -c /backup/db/sakumi_db_YYYY-MM-DD.sql.gz > /tmp/sakumi_restore.sql

# Restore (ikuti Langkah 6-11 di atas, ganti path file SQL)
sudo PGPASSFILE=/root/.pgpass psql \
  -h 127.0.0.1 -p 5432 -U sakumi -d sakumi_real \
  --single-transaction \
  --set ON_ERROR_STOP=1 \
  -f /tmp/sakumi_restore.sql

# Bersihkan
sudo shred -u /tmp/sakumi_restore.sql
```

### 6.4 Estimasi Waktu Pemulihan (RTO)

| Tahap | Estimasi |
|---|---|
| Persiapan (maintenance mode, stop worker) | 2-5 menit |
| Ekstraksi 7z | 1-5 menit |
| Restore database (tergantung ukuran) | 10-45 menit |
| Verifikasi + cache clear + start worker | 5-10 menit |
| **Total RTO tipikal** | **20-65 menit** |

---

## BAB VII — Monitoring & Eskalasi

### 7.1 Monitoring Harian

**Tugas: Super Admin / Pengelola Server — setiap pagi sebelum jam operasional**

```bash
# Cek log backup 7z (3 baris terakhir)
sudo tail -3 /var/backups/sakumi/logs/backup.log

# Cek jumlah backup yang ada
sudo ls /var/backups/sakumi/db/*.sql.7z 2>/dev/null | wc -l

# Cek ukuran total backup
sudo du -sh /var/backups/sakumi/db/

# Cek disk space
df -h /var/backups/sakumi
```

**Indikator sehat:**
- Log terakhir menunjukkan `database backup completed` dengan timestamp hari ini
- Jumlah file backup: 1-14
- Disk usage: < 80%

### 7.2 Monitoring Mingguan

**Tugas: Super Admin / Pengelola Server — setiap Senin**

```bash
# Cek hasil restore-test terakhir
sudo grep 'restore_test' /var/backups/sakumi/logs/backup.log | tail -3

# Cek apakah ada ERROR dalam 7 hari terakhir
sudo grep '\[ERROR\]' /var/backups/sakumi/logs/backup.log | tail -10

# Cek ukuran backup terbaru (deteksi anomali)
sudo ls -lht /var/backups/sakumi/db/ | head -5
```

**Indikator sehat:**
- Restore-test terakhir: `PASSED`
- Tidak ada `[ERROR]` dalam 7 hari terakhir
- Ukuran backup konsisten (variasi besar mengindikasikan anomali)

### 7.3 Prosedur Eskalasi Kegagalan

**Level 1 — Kegagalan backup harian:**

| Gejala | Tindakan |
|---|---|
| `pg_dump failed` | Periksa koneksi DB: `psql -h 127.0.0.1 -U sakumi -d sakumi_real -c "SELECT 1;"` |
| `7z compression failed` | Periksa disk space: `df -h /var/backups/sakumi` |
| `required command not found: 7z` | Install: `sudo apt install p7zip-full` |
| `insecure permission on .pgpass` | Perbaiki: `sudo chmod 600 /root/.pgpass` |
| `required file not found: .pgpass` | Buat ulang sesuai [BAB III — 3.2](#32-konfigurasi-kredensial-postgresql-pgpass) |

**Level 2 — Kegagalan restore-test:**

| Gejala | Tindakan |
|---|---|
| `no .sql.7z backup found` | Jalankan backup manual terlebih dahulu |
| `failed to extract` | Backup mungkin rusak — periksa file: `7z t /var/backups/sakumi/db/FILE.sql.7z` |
| `restore into ... failed` | Dump SQL mungkin corrupt — bandingkan dengan backup gzip di `/backup/db/` |
| `missing key tables` | Skema database mungkin berubah — sesuaikan daftar tabel di skrip jika perlu |
| `failed to create test database` | Periksa privilege user: `\du sakumi` di psql, pastikan `CREATEDB` |

**Level 3 — Kegagalan tidak teratasi:**
1. Laporkan kepada Kepala Sekolah
2. Eskalasikan ke penyedia hosting / konsultan IT
3. Pastikan backup gzip legacy masih berjalan sebagai fallback

---

## BAB VIII — Pemeliharaan Berkala

### 8.1 Pemeliharaan Bulanan

| Tugas | Command | Penanggung Jawab |
|---|---|---|
| Review ukuran backup bulanan | `sudo du -sh /var/backups/sakumi/db/` | Super Admin |
| Review log error bulanan | `sudo grep '\[ERROR\]' /var/backups/sakumi/logs/backup.log \| wc -l` | Super Admin |
| Rotasi log jika > 50 MB | Lihat prosedur 8.3 | Super Admin |
| Verifikasi `.pgpass` masih valid | `sudo PGPASSFILE=/root/.pgpass psql -h 127.0.0.1 -U sakumi -d sakumi_real -c "SELECT 1;"` | Super Admin |

### 8.2 Pemeliharaan Semester (6 Bulan)

| Tugas | Keterangan |
|---|---|
| Ganti password PostgreSQL | Update di `.pgpass` dan konfigurasi server |
| Review retensi backup | Sesuaikan `RETENTION_COUNT` jika disk berubah |
| Uji pemulihan penuh ke server staging | Restore backup ke server terpisah, verifikasi aplikasi berjalan |
| Update paket `p7zip-full` dan `postgresql-client` | `sudo apt update && sudo apt upgrade -y p7zip-full postgresql-client` |
| Review SOP ini | Perbarui jika ada perubahan infrastruktur atau skrip |

### 8.3 Rotasi Log Backup

```bash
# Periksa ukuran log
sudo du -sh /var/backups/sakumi/logs/backup.log

# Jika > 50 MB, rotasi manual
sudo mv /var/backups/sakumi/logs/backup.log \
        /var/backups/sakumi/logs/backup-$(date +%Y%m).log
sudo touch /var/backups/sakumi/logs/backup.log
sudo chmod 600 /var/backups/sakumi/logs/backup.log
sudo chown root:root /var/backups/sakumi/logs/backup.log
```

Atau konfigurasikan logrotate:

```bash
sudo tee /etc/logrotate.d/sakumi-backup <<'CONF'
/var/backups/sakumi/logs/backup.log {
    monthly
    rotate 12
    compress
    delaycompress
    missingok
    notifempty
    create 600 root root
}
CONF
```

---

## BAB IX — Formulir & Checklist

### 9.1 Checklist Harian Backup (Cetak)

```
================================================================
CHECKLIST MONITORING BACKUP HARIAN — SAKUMI
================================================================
Tanggal    : _______________
Dikerjakan : _______________

PAGI (sebelum jam operasional):
[ ] Cek log backup 7z: ✅ Sukses / ❌ Gagal
    Timestamp backup terakhir: _______________
    Ukuran file backup: _______________
[ ] Cek disk usage /var/backups/sakumi: ___% (batas aman < 80%)
[ ] Jika gagal — tindak lanjut: ________________________________

Paraf: _______________
================================================================
```

### 9.2 Checklist Mingguan Restore-Test (Cetak)

```
================================================================
CHECKLIST RESTORE-TEST MINGGUAN — SAKUMI
================================================================
Minggu ke  : ___  Bulan: _______________
Dikerjakan : _______________

[ ] Cek log restore-test: ✅ PASSED / ❌ FAILED
    Backup yang diuji: _______________
    Jumlah tabel terdeteksi: ___
    Jumlah baris users: ___
[ ] Jika FAILED — tindak lanjut: ________________________________
[ ] Tidak ada ERROR di log 7 hari terakhir

Paraf: _______________
================================================================
```

### 9.3 Formulir Insiden Pemulihan Database

```
================================================================
FORMULIR INSIDEN PEMULIHAN DATABASE — SAKUMI
================================================================
Tanggal Insiden        : _______________
Waktu Insiden Terdeteksi: _______________
Dilaporkan Oleh        : _______________
Penanggung Jawab Restore: _______________

DESKRIPSI INSIDEN:
___________________________________________________________________
___________________________________________________________________

BACKUP YANG DIGUNAKAN:
  Nama File     : _______________
  Timestamp File: _______________
  Lokasi        : [ ] /var/backups/sakumi/db/  [ ] /backup/db/

TIMELINE PEMULIHAN:
  Mulai maintenance mode : _______________
  Mulai restore          : _______________
  Selesai restore        : _______________
  Verifikasi selesai     : _______________
  Aplikasi kembali online: _______________
  Total durasi           : _______________ menit

VERIFIKASI PASCA-RESTORE:
  [ ] Jumlah tabel publik: ___ (bandingkan dengan baseline: ___)
  [ ] Jumlah users: ___
  [ ] Jumlah students: ___
  [ ] Aplikasi login normal
  [ ] Dashboard menampilkan data benar
  [ ] Queue worker berjalan

DATA YANG MUNGKIN HILANG:
  Periode gap: dari _______________ hingga _______________
  Estimasi transaksi: ___
  Catatan: ___________________________________________________

TINDAK LANJUT:
___________________________________________________________________
___________________________________________________________________

Paraf Penanggung Jawab : _______________
Paraf Kepala Sekolah   : _______________
================================================================
```

---

## REFERENSI SILANG

| Dokumen | Keterangan |
|---|---|
| `docs/PRODUCTION_BACKUP_SYSTEM.md` | Detail teknis arsitektur backup (gzip + offsite) |
| `docs/MAINTENANCE_CHECKLIST.md` | Checklist maintenance harian/mingguan/bulanan keseluruhan |
| `docs/SOP_SAKUMI.md` | SOP operasional per role |
| `docs/DOKUMEN_INDUK_SAKUMI.md` | Dokumen induk tata kelola sistem |
| `scripts/backup/crontab.example` | Contoh konfigurasi crontab lengkap |
| `scripts/backup/pgpass.example` | Template file .pgpass |

---

*Dokumen ini harus dievaluasi dan diperbarui setiap 6 bulan atau bila ada perubahan signifikan pada infrastruktur backup.*
