# Post Import Checklist

## 1) Basic App Checks

- [ ] `php -v` minimal sesuai project (>= 8.2).
- [ ] `composer install` sukses.
- [ ] `npm install` dan `npm run build` sukses.
- [ ] `.env` terisi benar dan `APP_KEY` valid.

## 2) Database Checks

- [ ] Jika SQLite dummy: `database/sakumi_dummy.sqlite` ada.
- [ ] Jika PostgreSQL/MySQL: dump berhasil diimport.
- [ ] `php artisan migrate --force` sukses.

## 3) Storage and Cache

- [ ] `php artisan storage:link` sukses.
- [ ] File upload lama muncul kembali.
- [ ] `php artisan optimize:clear` sukses.

## 4) Functional Checks

- [ ] Login berhasil.
- [ ] Invoice, Settlement, Daily Report tampil normal.
- [ ] Arrears outstanding sesuai data.
- [ ] Dashboard tidak kosong/error.

## 5) Ops Checks (if used)

- [ ] Scheduler aktif: `* * * * * php artisan schedule:run`
- [ ] Queue worker aktif (jika dipakai).
