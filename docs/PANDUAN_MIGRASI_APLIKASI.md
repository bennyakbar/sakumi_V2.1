# Panduan Lengkap Migrasi Aplikasi Sakumi ke Komputer Lain

Dokumen ini menjelaskan langkah-langkah detail (best practices) untuk memindahkan atau meng-copy aplikasi Sakumi dari satu PC ke PC lainnya agar terhindar dari masalah error database, masalah *environment* (`.env`), atau kredensial login yang tiba-tiba tidak valid.
Jika Anda butuh versi ringkas untuk eksekusi cepat, gunakan `PANDUAN_MIGRASI_QUICKSTART.md`.

---

## Prasyarat Minimum Sebelum Migrasi

Pastikan PC tujuan sudah memenuhi kebutuhan minimum berikut:

- PHP `8.2+` (disarankan sama dengan PC asal)
- Composer `2.x`
- Node.js `18+` (atau sesuai kebutuhan project)
- NPM `9+`
- Salah satu database: PostgreSQL `14+` atau MySQL/MariaDB `8+`
- Ekstensi PHP penting aktif: `mbstring`, `openssl`, `pdo`, `pdo_pgsql`/`pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`

Contoh cek versi cepat di terminal:
```bash
php -v
composer -V
node -v
npm -v
```

## Konsep Dasar yang Perlu Dipahami

Aplikasi (kode program) dan Lingkungan/Data (Database & Konfigurasi) adalah dua hal yang terpisah. 
* File `.env` tidak boleh di-copy secara mentah-mentah ke PC lain. File ini memuat password, username database, dan kunci keamanan yang spesifik untuk masing-masing komputer.
* Men-zip atau meng-copy folder aplikasi **hanya menyalin instruksi kodenya saja**, BUKAN isi database transaksinya (kecuali jika menggunakan mode Dummy/SQLite). Itulah penyebab utama login menjadi tidak valid di PC baru, karena tabel penggunanya memang masih kosong.

---

## Langkah 1: Persiapan di PC Asal (Lama)

1. **(Opsional) Export Database (Jika menggunakan Data Asli / Real DB)**
   Jika Anda ingin memindahkan aplikasi **beserta seluruh data** yang sudah dikerjakan di PC lama:
   - Buka aplikasi Database Manager (misalnya phpMyAdmin, DBeaver, atau pgAdmin).
   - Export database `sakumi` menjadi file `.sql`.
   - Simpan file `.sql` ini untuk nantinya di-import di PC baru.

2. **Membuat File ZIP Aplikasi**
   Buka terminal di dalam folder aplikasi (`sakumi`) dan jalankan perintah berikut. Perintah ini akan mengompres aplikasi secara bersih **tanpa menyertakan** folder pihak ketiga berukuran raksasa (`vendor` dan `node_modules`):
   ```bash
   zip -r sakumi_backup.zip . -x "vendor/*" "node_modules/*" ".git/*"
   ```
   Pindahkan file `sakumi_backup.zip` (dan file `.sql` jika ada) ke flashdisk atau media transfer lainnya.

---

## Langkah 2: Proses Instalasi di PC Tujuan (Baru)

1. **Ekstrak Folder Aplikasi**
   Pindahkan `sakumi_backup.zip` ke PC tujuan dan ekstrak (unzip) di folder yang Anda inginkan (misal di folder `Documents` atau `htdocs`).

2. **Download Ulang Dependencies (Suku Cadang)**
   Karena folder `vendor` dan `node_modules` tadi tidak kita ikutkan saat zip, kita harus menyuruh sistem men-downloadnya ulang via terminal di dalam folder aplikasi yang baru diekstrak:
   ```bash
   composer install
   npm install
   npm run build
   ```

3. **Menyiapkan File Identitas Komputer (`.env`)**
   - Copy file `.env.example` dan ubah namanya menjadi `.env`.
     *(Di Windows/Linux, cukup copy dan paste file `.env.example` di tempat yang sama, lalu rename copy-nya menjadi `.env`)*
   - Hasilkan kunci keamanan (App Key) baru khusus untuk komputer ini dengan perintah:
     ```bash
     php artisan key:generate
     ```

---

## Langkah 3: Konfigurasi Database & Environment

Di Sakumi, terdapat fitur khusus untuk memilih apakah Anda ingin menggunakan database uji coba (Dummy via SQLite) atau database bawaan (Real via PostgreSQL atau MySQL). 

Buka file `.env` di text editor (seperti VS Code atau Notepad), dan pilih salah satu dari 3 skenario berikut sesuai kebutuhan Anda:

### Skenario A: Menggunakan Database Dummy (Untuk Uji Coba Lintas PC)
Gunakan ini jika Anda memindahkan aplikasi ke PC lain hanya untuk menguji coba fitur, presentasi dadakan, atau coding tanpa perlu report setup server database baru.

1. Buka `.env` dan atur mode Sakumi menjadi dummy:
   ```env
   DB_SAKUMI_MODE=dummy
   ```
2. Aplikasi otomatis akan menggunakan koneksi SQLite lokal (file `database/database.sqlite`).
3. Anda tetap harus membuat tabel dan akun admin bawaan agar bisa login. Jalankan:
   ```bash
   php artisan migrate:fresh --seed
   ```

### Skenario B: Menggunakan Database Real (Mulai Transaksi dari Nol)
Gunakan skenario ini jika Anda mendeploy sistem ke server produksi baru, dan ingin menggunakan PostgreSQL/MySQL yang bersih dari awal.

1. Buat database kosong bernama `sakumi` di sistem database PC/Server baru tersebut (via phpMyAdmin, pgAdmin, dsb).
2. Buka `.env` dan arahkan koneksi ke PC baru:
   ```env
   DB_SAKUMI_MODE=real
   DB_CONNECTION=pgsql # (Ganti menjadi mysql jika memakai MySQL/MariaDB)
   DB_HOST=127.0.0.1
   DB_PORT=5432        # (Gunakan 3306 untuk MySQL)
   DB_DATABASE=sakumi
   DB_USERNAME=root    # (Isi dengan username database milik komputer baru)
   DB_PASSWORD=rahasia # (Isi dengan password database milik komputer)
   ```
3. Susun tabel dan akun admin default:
   ```bash
   php artisan migrate:fresh --seed
   ```

### Skenario C: Menggunakan Database Real (Melanjutkan Data dari PC Lama)
Gunakan ini jika Anda membawa file `.sql` dari Langkah 1, dan ingin melanjutkan seluruh progres transaksi dari PC lama.

1. Buka Database Manager di PC baru, buat database kosong bernama `sakumi`.
2. **Import** file `.sql` yang Anda bawa dari PC lama ke dalam database tersebut agar seluruh tabel terisi data utuh.
3. Buka file `.env` dan sesuaikan pengaturan seperti pada **Skenario B** di atas.
4. **Berhenti di sini! JANGAN MENJALANKAN `artisan migrate:fresh`**. Sistem dan database Anda langsung siap digunakan 100% lengkap dengan akun loginnya.

#### Alternatif Import via Terminal (CLI)

Jika tidak memakai GUI (phpMyAdmin/pgAdmin), gunakan salah satu:

**PostgreSQL**
```bash
psql -h 127.0.0.1 -U postgres -d sakumi -f backup_sakumi.sql
```

**MySQL/MariaDB**
```bash
mysql -h 127.0.0.1 -u root -p sakumi < backup_sakumi.sql
```

---

## Langkah 4: Menjalankan Aplikasi

Setelah `.env` dan Database selesai dikonfigurasi, Anda tinggal mengaktifkan server di PC baru tersebut dengan menjalankan:
```bash
php artisan serve
```

Buka browser dan akses `http://localhost:8000`. 
Selamat! Aplikasi Sakumi sudah berhasil dipindahkan ke komputer baru tanpa error.

---

## Langkah 4.1: Checklist Verifikasi Pasca Migrasi (Wajib)

Setelah aplikasi jalan, lakukan validasi cepat berikut:

1. Status migrasi valid:
   ```bash
   php artisan migrate:status
   ```
2. Login admin berhasil.
3. Cek modul inti: dashboard, transaksi, laporan, export/import (jika ada).
4. Cek upload file (jika ada fitur upload):
   ```bash
   php artisan storage:link
   ```
5. Cek log error:
   - File log Laravel ada di `storage/logs/laravel.log`
   - Pastikan tidak ada error berulang setelah diuji.

---

## Troubleshooting Umum

### 1) Error `No application encryption key has been specified`
Penyebab: `APP_KEY` belum dibuat.
Solusi:
```bash
php artisan key:generate
```

### 2) Error koneksi database (`SQLSTATE[HY000]`, `connection refused`, dsb)
Penyebab umum:
- Host/port/user/password di `.env` salah
- Service database belum berjalan
- Jenis koneksi tidak sesuai (`pgsql` vs `mysql`)

Langkah cek:
```bash
php artisan config:clear
php artisan cache:clear
```
Lalu periksa ulang variabel `DB_*` di `.env`.

### 3) Error permission folder `storage` / `bootstrap/cache`
Solusi (Linux):
```bash
sudo chown -R www-data:www-data /var/www/sakumi
sudo chmod -R 775 /var/www/sakumi/storage
sudo chmod -R 775 /var/www/sakumi/bootstrap/cache
```

### 4) Tampilan tidak memuat asset (CSS/JS tidak muncul)
Solusi:
```bash
npm install
npm run build
php artisan optimize:clear
```

### 5) Setelah ubah `.env` tapi tidak berdampak
Solusi:
```bash
php artisan optimize:clear
php artisan config:cache
```

---

## Rencana Rollback Jika Migrasi Gagal

Gunakan prosedur ini agar downtime minimal:

1. Backup kondisi saat ini:
   - Simpan salinan `.env`
   - Dump database saat ini (jika ada)
2. Jika migrasi gagal:
   - Restore file `.env` backup
   - Restore database dari dump terakhir yang valid
3. Jalankan:
   ```bash
   php artisan optimize:clear
   ```
4. Verifikasi login dan fitur inti kembali normal.

---

## Langkah 5: Deployment ke VPS (Production Server)

Jika Anda ingin meng-online-kan Sakumi di VPS (Virtual Private Server) agar bisa diakses dari mana saja, langkahnya sedikit berbeda karena mementingkan faktor keamanan dan stabilitas.

### 1. Persiapan VPS
- Pastikan VPS Anda (Ubuntu/Debian) sudah terinstall: Nginx, PHP 8.2+ (tergantung versi server), Composer, Node.js, dan PostgreSQL/MySQL.
- Arahkan domain Anda (misal: `sakumi.sekolah.com`) ke IP Address VPS Anda di pengaturan DNS.

### 2. Upload & Ekstrak Aplikasi
1. Upload file `sakumi_backup.zip` ke VPS, misalnya ke direktori `/var/www/sakumi`.
2. Ekstrak file tersebut:
   ```bash
   cd /var/www/sakumi
   unzip sakumi_backup.zip
   ```
3. Install semua *dependencies* produksi (tanpa paket *development* agar lebih ringan):
   ```bash
   composer install --optimize-autoloader --no-dev
   npm install
   npm run build
   ```

### 3. Konfigurasi Environment & Database Production
1. Buat file `.env` dari `.env.example`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
2. Edit `.env` dengan pengaturan keamanan mutlak untuk *production*:
   ```env
   APP_ENV=production
   APP_DEBUG=false # WAJIB false agar pesan error sistem tidak bocor ke publik
   APP_URL=https://sakumi.sekolah.com

   # Konfigurasi Database Real (Sesuai dengan database VPS Anda)
   DB_SAKUMI_MODE=real
   DB_CONNECTION=pgsql
   # ... (isi host, port, username, password)
   ```
3. Lakukan migrasi database (dan seeding jika mulai dari nol), **atau** import file `.sql` jika Anda membawa data dari PC lama.
   ```bash
   # JIKA DARI NOL, jalankan perintah ini:
   php artisan migrate --force --seed
   ```
   *(Catatan: flag `--force` wajib digunakan di mode production sebagai tindakan keamanan ekstra Laravel).*

### 4. Optimasi Cache & Keamanan Folder
Di server produksi, kita harus melakukan *caching* agar aplikasi berjalan super cepat, dan mengatur hak akses (*permission*) folder agar aman dan dapat ditulis oleh *web server*:

```bash
# Optimasi Cache Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Mengatur Hak Akses Folder Server (Bagi Nginx/Apache di Ubuntu/Debian)
sudo chown -R www-data:www-data /var/www/sakumi
sudo chmod -R 775 /var/www/sakumi/storage
sudo chmod -R 775 /var/www/sakumi/bootstrap/cache
```

Tambahan penting keamanan production:
- Gunakan user database khusus aplikasi (jangan `root`/`postgres` superuser).
- Set `APP_DEBUG=false` dan jangan pernah expose `.env`.
- Aktifkan firewall (`ufw`) dan izinkan hanya port yang diperlukan.
- Pasang SSL/TLS (Let's Encrypt/Certbot) agar semua akses via HTTPS.

### 5. Setup Web Server (Nginx)
Jangan gunakan `php artisan serve` di VPS! Anda harus menggunakan web server sesungguhnya.
Sebagai contoh, buat file konfigurasi server Nginx baru (misalnya di `/etc/nginx/sites-available/sakumi`):

```nginx
server {
    listen 80;
    server_name sakumi.sekolah.com;
    root /var/www/sakumi/public; # PERHATIAN: Harus mengarah ke folder public!

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # Sesuaikan dengan versi PHP VPS Anda
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi Nginx dan restart. *(Sangat disarankan setelah ini Anda memasang SSL/HTTPS menggunakan Certbot)*.
```bash
sudo ln -s /etc/nginx/sites-available/sakumi /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 6. Menjalankan Queue dan Scheduler (Jika Digunakan)

Jika aplikasi memakai Job Queue dan Task Scheduler, jangan dilewatkan:

- Queue worker: jalankan via `systemd` agar otomatis restart
- Scheduler Laravel: jalankan tiap menit via cron

Contoh cron:
```bash
* * * * * cd /var/www/sakumi && php artisan schedule:run >> /dev/null 2>&1
```

### 7. Backup & Monitoring Production

Minimum standar operasional:

- Backup database harian (retensi 7-30 hari)
- Backup file penting (`storage`, `.env`, konfigurasi Nginx)
- Monitoring log Laravel + log Nginx
- Uji restore backup secara berkala (jangan hanya backup tanpa simulasi restore)

Aplikasi Sakumi Anda kini sudah online secara profesional dan siap melayani ratusan pengguna!
