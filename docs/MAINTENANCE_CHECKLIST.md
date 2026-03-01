# MAINTENANCE CHECKLIST — SAKUMI

**Versi:** 1.0  
**Tanggal:** 26 Februari 2026  
**Dijalankan oleh:** Super Admin / Pengelola Server

---

> **Cara Baca Dokumen Ini**  
> Setiap item checklist dilengkapi dengan **command terminal** yang bisa langsung dijalankan.  
> Jalankan dari dalam folder project: `cd /path/to/sakumi`

---

## ✅ CHECKLIST HARIAN (Setiap Hari Kerja)

### 🔹 1. Health Check Sistem

**Tujuan:** Pastikan semua komponen sistem berjalan normal sebelum jam operasional.

```bash
# Cek koneksi database, storage, queue, dan cache sekaligus
php artisan about

# Cek via endpoint health (butuh app running)
# Sesuaikan BASE_URL, misalnya http://localhost:8000 jika pakai `php artisan serve`
BASE_URL=${BASE_URL:-http://localhost:8000}
curl -s "$BASE_URL/health/live" | python3 -m json.tool
```

> **Ekspektasi output `health/live`:**
> ```json
> {"status": "ok"}
> ```
> Jika `"status": "degraded"` → database tidak bisa dijangkau, cek koneksi DB segera.

**Checklist:**
- [ ] Status DB: OK
- [ ] Status Storage: OK
- [ ] Status Queue: OK
- [ ] Status Cache: OK

---

### 🔹 2. Cek Failed Jobs (Antrian Gagal)

**Tujuan:** Pastikan tidak ada job yang gagal (notifikasi, PDF, dll).

```bash
# Lihat jumlah job gagal
php artisan queue:failed

# Atau cek langsung di database
php artisan tinker --execute="echo \DB::table('failed_jobs')->count() . ' failed jobs';"
```

**Batas aman:** `failed_jobs ≤ 10`

Jika ada job gagal:
```bash
# Lihat detail job yang gagal
php artisan queue:failed

# Coba jalankan ulang semua job gagal
php artisan queue:retry all

# Hapus job gagal yang sudah tidak relevan (hati-hati)
php artisan queue:flush
```

**Checklist:**
- [ ] Jumlah failed_jobs: ___ (batas aman ≤ 10)
- [ ] Jika ada failed_jobs: sudah di-retry atau dihapus

---

### 🔹 3. Cek Status Backup

**Tujuan:** Pastikan backup otomatis semalam berhasil.

```bash
# Lihat daftar backup dan statusnya
php artisan backup:list

# Cek kesehatan backup
php artisan backup:monitor
```

> **Ekspektasi:** Tidak ada `❌` pada output. Harus ada backup dari ≤ 1 hari yang lalu.

Jika backup gagal atau belum ada:
```bash
# Jalankan backup manual sekarang
php artisan backup:run

# Jalankan backup khusus database saja
php artisan backup:run --only-db
```

**Checklist:**
- [ ] Backup terakhir: tanggal _______________ (maks 1 hari lalu)
- [ ] Status backup: ✅ Sehat / ❌ Error (tindak lanjut: _______________)

---

### 🔹 4. Cek Log Error Aplikasi

**Tujuan:** Deteksi error di aplikasi sebelum dilaporkan pengguna.

```bash
# Tampilkan 50 baris terakhir dari log aplikasi
tail -n 50 storage/logs/laravel.log

# Filter hanya baris ERROR dan CRITICAL
grep -E "\[ERROR\]|\[CRITICAL\]|\[ALERT\]" storage/logs/laravel.log | tail -20

# Cek ukuran log (jika >100MB perlu dirotasi)
du -sh storage/logs/laravel.log
```

Jika log terlalu besar:
```bash
# Pilih salah satu opsi di bawah:
# Opsi A: kosongkan log aktif (hati-hati: data lama hilang)
: > storage/logs/laravel.log

# Opsi B: rotasi manual (simpan arsip log lama)
mv storage/logs/laravel.log storage/logs/laravel-$(date +%Y%m%d).log
touch storage/logs/laravel.log
```

**Checklist:**
- [ ] Tidak ada error CRITICAL di log hari ini
- [ ] Ukuran log wajar (< 100 MB)
- [ ] Error yang ditemukan (jika ada): _______________

---

### 🔹 5. Cek Queue Worker Berjalan

**Tujuan:** Pastikan proses queue worker aktif untuk memproses notifikasi dan PDF.

```bash
# Cek apakah queue worker running (via supervisor)
sudo supervisorctl status

# Atau cek proses PHP yang berjalan
ps aux | grep "queue:work" | grep -v grep

# Cek jumlah job yang pending di antrian
php artisan tinker --execute="echo \DB::table('jobs')->count() . ' pending jobs';"
```

Jika queue worker mati:
```bash
# Restart queue worker via supervisor
sudo supervisorctl restart sakumi-worker:*

# Atau start manual (untuk testing saja, bukan production)
php artisan queue:work --sleep=3 --tries=3 &
```

**Checklist:**
- [ ] Queue worker berjalan: Ya / Tidak (restart jika mati)
- [ ] Pending jobs: ___ (normal jika < 50)

---

### 🔹 6. Cek Disk Space

**Tujuan:** Pastikan ruang penyimpanan tidak hampir penuh.

```bash
# Cek penggunaan disk keseluruhan
df -h

# Cek ukuran folder storage (PDF, backup, log)
du -sh storage/*
du -sh storage/app/backups/ 2>/dev/null || echo "Folder backup belum ada"
du -sh storage/logs/

# Cek ukuran total project
du -sh /path/to/sakumi
```

> **Batas aman:** Disk usage < 80%. Jika > 90% → tindak lanjut segera.

Jika storage penuh, bersihkan file lama:
```bash
# Hapus backup lama (lebih dari 30 hari)
find storage/app/backups/ -name "*.zip" -mtime +30 -delete

# Bersihkan log lama
find storage/logs/ -name "*.log" -mtime +7 -delete

# Bersihkan cache aplikasi
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

**Checklist:**
- [ ] Disk usage: ___% (batas aman < 80%)
- [ ] Folder storage/app/backups ukuran: ___
- [ ] Folder storage/logs ukuran: ___

---

### 🔹 7. Verifikasi Migration Status

**Tujuan:** Pastikan tidak ada migration yang tertinggal setelah update kode.

```bash
# Cek status migrasi
php artisan migrate:status

# Jalankan migrasi jika ada yang pending (setelah backup!)
php artisan migrate
```

> ⚠ **Selalu backup database sebelum menjalankan `migrate` di production.**

**Checklist:**
- [ ] Tidak ada migration berstatus `Pending`
- [ ] Jika ada pending: sudah dijalankan setelah backup

---

### 🔹 8. Cek Permission File

**Tujuan:** Pastikan PHP bisa menulis ke folder yang dibutuhkan.

```bash
# Cek permission folder kritis
ls -la storage/
ls -la bootstrap/cache/

# Perbaiki permission jika bermasalah
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Atau jika pakai user sendiri (non-Docker)
chown -R $USER:www-data storage bootstrap/cache
```

**Checklist:**
- [ ] `storage/` writable oleh web server
- [ ] `bootstrap/cache/` writable oleh web server

---

## 📅 CHECKLIST MINGGUAN (Setiap Senin)

### 🔸 1. Review Audit Log Kritis

```bash
# Cek aktivitas user di minggu lalu via tinker
php artisan tinker --execute="
\DB::table('activity_log')
  ->where('created_at', '>=', now()->subDays(7))
  ->whereIn('description', ['deleted', 'cancelled', 'role_changed'])
  ->orderByDesc('created_at')
  ->limit(20)
  ->get(['description', 'subject_type', 'causer_id', 'created_at'])
  ->each(fn(\$a) => dump(\$a));
"
```

**Checklist:**
- [ ] Tidak ada perubahan role yang tidak disetujui
- [ ] Tidak ada pembatalan massal yang mencurigakan
- [ ] Aktifitas di luar jam kerja normal diperiksa

---

### 🔸 2. Bersihkan Cache Aplikasi

```bash
# Bersihkan semua cache
php artisan optimize:clear

# Atau lebih spesifik:
php artisan cache:clear       # Application cache
php artisan config:clear      # Config cache
php artisan route:clear       # Route cache
php artisan view:clear        # View cache

# Rebuild cache untuk production (mempercepat load)
php artisan optimize
```

**Checklist:**
- [ ] Cache dibersihkan dan direbuild

---

### 🔸 3. Cek Status Notifikasi WhatsApp

```bash
# Cek notifikasi yang pending atau gagal
php artisan tinker --execute="
\DB::table('notifications')
  ->whereIn('whatsapp_status', ['pending', 'failed'])
  ->where('created_at', '>=', now()->subDays(7))
  ->count();
"
```

**Checklist:**
- [ ] Notifikasi failed: ___ (jika banyak, cek gateway URL di Settings)
- [ ] Notifikasi pending lama (>1 hari): ___ (queue worker mungkin mati)

---

### 🔸 4. Test Koneksi Database

```bash
# Tes koneksi dan response time DB
php artisan tinker --execute="
\$start = microtime(true);
\DB::connection()->getPdo();
\$ms = round((microtime(true) - \$start) * 1000, 2);
echo 'DB connected OK. Response time: ' . \$ms . ' ms';
"
```

> Jika response time > 500ms secara konsisten → investigasi query lambat.

**Checklist:**
- [ ] DB response time: ___ ms (normal < 100ms)

---

## 📆 CHECKLIST BULANAN (Setiap Tanggal 1)

### 🔻 1. Generate Kewajiban Bulanan Siswa

**Tujuan:** Membuat kewajiban pembayaran (obligations) untuk seluruh siswa aktif bulan ini.

```bash
# Generate kewajiban otomatis (biasanya sudah dijadwalkan via cron)
php artisan obligations:generate

# Atau untuk bulan/tahun tertentu
php artisan obligations:generate --month=3 --year=2026
```

> ✅ Command ini **idempotent** — aman dijalankan berkali-kali, tidak akan duplikat.

**Checklist:**
- [ ] Kewajiban bulan ini berhasil digenerate
- [ ] Jumlah siswa yang terkena kewajiban baru: ___

---

### 🔻 2. Backup Manual Database

```bash
# Backup database saja (sebelum operasional bulan baru)
php artisan backup:run --only-db

# Backup lengkap (DB + files)
php artisan backup:run

# Verifikasi backup berhasil
php artisan backup:list
```

**Checklist:**
- [ ] Backup manual bulan ini berhasil
- [ ] File backup tersimpan dan dapat diakses

---

### 🔻 3. Export Laporan Bulanan

```bash
# Trigger export harian via curl (jika app running)
# Sesuaikan URL dan periode
curl -b "cookies.txt" \
  "http://localhost/reports/monthly/export?month=2&year=2026" \
  -o "laporan_bulanan_$(date +%Y%m).xlsx"
```

Atau lakukan export melalui UI browser di menu **Reports → Monthly → Export Excel/PDF**.

**Checklist:**
- [ ] Monthly Report bulan lalu sudah dieksport dan diarsipkan
- [ ] Arrears Report bulan lalu sudah dieksport
- [ ] Laporan diserahkan ke Kepala Sekolah

---

### 🔻 4. Bersihkan Notifikasi Lama

```bash
# Hapus notifikasi lebih dari 6 bulan (sesuai policy)
php artisan tinker --execute="
\$deleted = \DB::table('notifications')
  ->where('created_at', '<', now()->subMonths(6))
  ->delete();
echo \$deleted . ' notifikasi lama dihapus.';
"
```

**Checklist:**
- [ ] Notifikasi lama (>6 bulan) sudah dibersihkan

---

### 🔻 5. Cek Integritas Data Keuangan

```bash
# Cek invoice dengan outstanding negatif (tidak boleh terjadi)
php artisan tinker --execute="
\$anomali = \DB::table('invoices')
  ->whereRaw('(invoice_total - already_paid) < 0')
  ->count();
echo \$anomali . ' invoice dengan outstanding negatif (harus = 0)';
"

# Cek transaksi completed tanpa receipt
php artisan tinker --execute="
\$noReceipt = \DB::table('transactions')
  ->where('status', 'completed')
  ->where('type', 'income')
  ->whereNull('receipt_path')
  ->count();
echo \$noReceipt . ' transaksi income tanpa receipt PDF';
"
```

**Checklist:**
- [ ] Tidak ada outstanding negatif: ✅ / ❌ (jumlah: ___)
- [ ] Tidak ada transaksi income tanpa receipt: ✅ / ❌ (jumlah: ___)

---

## 🚨 PROSEDUR DARURAT

### Reset Cache Saat Aplikasi Berperilaku Aneh

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload php8.2-fpm   # sesuaikan versi PHP
sudo systemctl reload nginx
```

---

### Restart Semua Service

```bash
# Restart queue worker
sudo supervisorctl restart sakumi-worker:*

# Restart web server
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm   # sesuaikan versi PHP

# Restart database (hati-hati: akan memutus koneksi aktif)
sudo systemctl restart postgresql
```

---

### Rollback Migration (Darurat)

```bash
# ⚠ SANGAT HATI-HATI — backup dulu sebelum rollback!
php artisan backup:run --only-db

# Rollback 1 migrasi terakhir
php artisan migrate:rollback

# Rollback N migrasi terakhir
php artisan migrate:rollback --step=3
```

---

### Preflight Cek Sebelum Deploy

```bash
# Jalankan script preflight yang sudah ada
bash scripts/preflight-prod.sh

# Dengan test suite sekaligus (lebih lengkap)
bash scripts/preflight-prod.sh --with-tests
```

---

## 📋 FORM CHECKLIST HARIAN (Versi Cetak)

```
============================================================
MAINTENANCE LOG SAKUMI — HARIAN
============================================================
Tanggal    : _______________
Dikerjakan : _______________
Unit       : _______________

PAGI (sebelum jam operasional):
[ ] Health check: DB OK | Storage OK | Queue OK | Cache OK
[ ] Failed jobs: ___ (batas aman ≤ 10)
[ ] Backup kemarin: Sukses / Gagal (tindak lanjut: _________)
[ ] Log error: Aman / Ada error (dicatat: _________________)
[ ] Queue worker berjalan: Ya / Tidak (restart: ___________)
[ ] Disk usage: ___% (batas aman < 80%)

SORE (setelah jam operasional):
[ ] Tidak ada error baru di log aplikasi
[ ] Pending jobs: ___ (normal < 50)
[ ] Catatan insiden hari ini: ______________________________

Paraf Super Admin: _______________
============================================================
```

---

## 🔗 REFERENSI CEPAT COMMAND

| Kebutuhan | Command |
|---|---|
| Cek health sistem | `php artisan about` |
| Cek failed jobs | `php artisan queue:failed` |
| Retry semua failed jobs | `php artisan queue:retry all` |
| Generate kewajiban | `php artisan obligations:generate` |
| Backup database | `php artisan backup:run --only-db` |
| Cek daftar backup | `php artisan backup:list` |
| Bersihkan semua cache | `php artisan optimize:clear` |
| Rebuild cache production | `php artisan optimize` |
| Cek status migrasi | `php artisan migrate:status` |
| Jalankan migrasi | `php artisan migrate` |
| Preflight deployment | `bash scripts/preflight-prod.sh` |
| Kirim arrears reminder | `php artisan arrears:remind` |
| Lihat log real-time | `tail -f storage/logs/laravel.log` |
| Restart queue worker | `sudo supervisorctl restart sakumi-worker:*` |

---

*Dokumen ini harus diperbarui setiap kali ada perubahan infrastruktur atau command baru.*
