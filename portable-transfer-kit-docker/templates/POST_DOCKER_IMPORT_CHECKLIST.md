# Post Docker Import Checklist

- [ ] `docker compose ... up -d --build` sukses.
- [ ] `composer install` di container `app` sukses.
- [ ] `npm install && npm run build` di container `node` sukses.
- [ ] `.env` sudah menyesuaikan host DB `db` port `5432`.
- [ ] `php artisan key:generate --force` sukses.
- [ ] `php artisan migrate --force` sukses.
- [ ] Jika ada dump SQL: import ke container `db` sukses.
- [ ] `php artisan storage:link` sukses.
- [ ] Aplikasi terbuka di `http://localhost:8080`.
- [ ] Login, invoice, settlement, daily report tervalidasi.
