# Docker Portable Kit (Lintas OS)

Kit ini untuk menjalankan SAKUMI di PC lain dengan Docker (Windows/Linux/macOS), tanpa mengubah folder app existing.

## Folder

- `docker-compose.yml`: stack `app + web + db + node`
- `Dockerfile`: image PHP-FPM Laravel
- `docker/nginx/default.conf`: Nginx config
- `scripts/bootstrap.sh`: setup cepat end-to-end
- `scripts/import-db.sh`: import dump SQL ke PostgreSQL container
- `scripts/export-db.sh`: export dump SQL dari PostgreSQL container
- `templates/.env.docker.example`: template env untuk mode Docker
- `templates/POST_DOCKER_IMPORT_CHECKLIST.md`: checklist validasi

## Prasyarat

- Docker Desktop (Windows/macOS) atau Docker Engine + Compose (Linux)
- Folder project SAKUMI sudah ada di PC tujuan

## 1) Jalankan Stack Docker

Dari root project:

```bash
bash portable-transfer-kit-docker/scripts/bootstrap.sh
```

Script ini menjalankan:

1. Build + start container
2. `composer install`
3. `npm install`
4. `npm run build`
5. `php artisan key:generate --force`
6. `php artisan migrate --force`
7. `php artisan storage:link`

App URL:

- `http://localhost:8080`

## 2) Import Data Dari PC Lama

Jika sebelumnya Anda pakai `portable-transfer-kit` (non-docker), ambil file SQL hasil export lalu import:

```bash
bash portable-transfer-kit-docker/scripts/import-db.sh /path/ke/database.sql
```

Jika sumber data SQLite dummy dan ingin tetap pakai dummy, copy file sqlite ke:

- `database/sakumi_dummy.sqlite`

Lalu atur `.env` ke mode dummy.

## 3) Command Operasional

Start/stop:

```bash
docker compose -f portable-transfer-kit-docker/docker-compose.yml up -d
docker compose -f portable-transfer-kit-docker/docker-compose.yml down
```

Masuk shell app:

```bash
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app bash
```

Jalankan artisan:

```bash
docker compose -f portable-transfer-kit-docker/docker-compose.yml exec app php artisan optimize:clear
```

## 4) Windows (PowerShell)

Gunakan command yang sama (Docker Compose), contoh:

```powershell
docker compose -f .\portable-transfer-kit-docker\docker-compose.yml up -d --build
docker compose -f .\portable-transfer-kit-docker\docker-compose.yml exec app composer install
docker compose -f .\portable-transfer-kit-docker\docker-compose.yml exec node sh -lc "npm install && npm run build"
docker compose -f .\portable-transfer-kit-docker\docker-compose.yml exec app php artisan key:generate --force
docker compose -f .\portable-transfer-kit-docker\docker-compose.yml exec app php artisan migrate --force
```

## 5) Catatan Penting

- Kit ini default ke PostgreSQL container (`db`) agar lintas OS lebih stabil.
- Kredensial default untuk container DB:
  - DB: `sakumi`
  - User: `sakumi`
  - Password: `sakumi`
- Untuk produksi, ganti password dan harden env.
