# Portable Transfer Kit (Lintas OS)

Kit ini dibuat untuk memindahkan aplikasi SAKUMI ke PC lain (Linux/macOS/Windows) tanpa mengubah folder app yang sudah ada.

## Isi Folder

- `scripts/export-project.sh`: buat paket transfer dari PC sumber (Linux/macOS).
- `scripts/import-project.sh`: ekstrak paket ke PC tujuan (Linux/macOS).
- `scripts/export-project.ps1`: buat paket transfer dari PC sumber (Windows PowerShell).
- `scripts/import-project.ps1`: ekstrak paket ke PC tujuan (Windows PowerShell).
- `templates/POST_IMPORT_CHECKLIST.md`: checklist setelah import.

## A. Export di PC Sumber (Linux/macOS)

Jalankan dari root project:

```bash
bash portable-transfer-kit/scripts/export-project.sh
```

Output paket ada di:

- `_portable_exports/sakumi-transfer-YYYYmmdd-HHMMSS.tar.gz`
- `_portable_exports/sakumi-transfer-YYYYmmdd-HHMMSS.zip` (jika `zip` tersedia)

## B. Import di PC Tujuan (Linux/macOS)

1. Copy file paket ke PC tujuan.
2. Ekstrak:

```bash
bash portable-transfer-kit/scripts/import-project.sh /path/ke/sakumi-transfer-YYYYmmdd-HHMMSS.tar.gz /path/tujuan/sakumi
```

3. Lanjut setup aplikasi:

```bash
cd /path/tujuan/sakumi
composer install
npm install
npm run build
php artisan key:generate --force   # jika APP_KEY belum ada
php artisan migrate --force
php artisan storage:link
```

## C. Export/Import di Windows (PowerShell)

Export dari root project:

```powershell
powershell -ExecutionPolicy Bypass -File .\portable-transfer-kit\scripts\export-project.ps1
```

Import:

```powershell
powershell -ExecutionPolicy Bypass -File .\portable-transfer-kit\scripts\import-project.ps1 -ArchivePath C:\path\sakumi-transfer-YYYYmmdd-HHMMSS.zip -Destination C:\sakumi
```

## Catatan DB

- Script akan mencoba dump DB bila koneksi terdeteksi:
  - PostgreSQL: `pg_dump`
  - MySQL/MariaDB: `mysqldump`
  - SQLite: copy file `.sqlite`
- Jika tool dump tidak tersedia, source code tetap terpaket dan script akan menulis warning.
- File `.env` disalin sebagai `.env.transfer` di bundle (berisi kredensial, simpan dengan aman).

## Catatan Keamanan

- Jangan kirim bundle lewat kanal publik tanpa enkripsi.
- Disarankan kompres + proteksi password (7z/zip encrypted) sebelum transfer.
