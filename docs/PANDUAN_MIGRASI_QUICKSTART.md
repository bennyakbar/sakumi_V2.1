# Quickstart Migrasi Sakumi (10-15 Menit)

Panduan ini untuk eksekusi cepat pindah aplikasi Sakumi ke komputer lain.
Jika butuh penjelasan lengkap, baca `PANDUAN_MIGRASI_APLIKASI.md`.

---

## 1) Di PC Asal (Lama)

Jika ingin bawa data lama:
- Export database `sakumi` ke file `backup_sakumi.sql`.

*Pilih salah satu cara Zip:*

**Opsi A: Zip Ringan (Disarankan)**
Zip source code tanpa folder pihak ketiga berukuran raksasa.
```bash
zip -r sakumi_backup.zip . -x "vendor/*" "node_modules/*" ".git/*"
```

**Opsi B: Zip Full (Semua Folder)**
Bawalah folder `vendor` dan `node_modules`. Pilih ini jika PC tujuan tidak ada internet.
```bash
zip -r sakumi_full_backup.zip .
```

Pindahkan `sakumi_backup.zip` (dan `backup_sakumi.sql` jika ada) ke PC baru.

---

## 2) Di PC Tujuan (Baru)

Ekstrak zip, lalu masuk folder aplikasi:
```bash
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

---

## 3) Pilih Mode Database (Paling Penting)

Edit `.env`, pilih satu:

### A. Dummy (SQLite, untuk demo/uji cepat)
```env
DB_SAKUMI_MODE=dummy
```
Lalu:
```bash
php artisan migrate:fresh --seed
```

### B. Real DB baru (mulai dari nol)
```env
DB_SAKUMI_MODE=real
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sakumi
DB_USERNAME=your_user
DB_PASSWORD=your_password
```
Lalu:
```bash
php artisan migrate:fresh --seed
```

### C. Real DB lanjut data lama (pakai `.sql`)
1. Buat database kosong `sakumi`.
2. Import file SQL.
3. Isi `.env` seperti mode Real.
4. Jangan jalankan `migrate:fresh`.

Contoh import SQL via CLI:

PostgreSQL:
```bash
psql -h 127.0.0.1 -U postgres -d sakumi -f backup_sakumi.sql
```

MySQL/MariaDB:
```bash
mysql -h 127.0.0.1 -u root -p sakumi < backup_sakumi.sql
```

---

## 4) Jalankan dan Verifikasi

Jalankan aplikasi:
```bash
php artisan serve
```

Cek cepat:
```bash
php artisan migrate:status
php artisan optimize:clear
```

Checklist:
- Login berhasil
- Dashboard terbuka
- Fitur transaksi/laporan normal
- Tidak ada error berulang di `storage/logs/laravel.log`

---

## 5) Jika Error Cepat

`APP_KEY` error:
```bash
php artisan key:generate
```

Perubahan `.env` tidak terbaca:
```bash
php artisan optimize:clear
php artisan config:cache
```

Koneksi DB gagal:
- Cek `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Pastikan service database berjalan

