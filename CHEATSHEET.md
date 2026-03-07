SAKUMI VPS Operations Cheatsheet

Server Path: /var/www/sakumi

------------------------------------------------------------------------

1. SSH SERVER

Login ke server: ssh sakumi

atau ssh superadmin@SERVER_IP

Masuk folder project: cd /var/www/sakumi

------------------------------------------------------------------------

2. POSTGRESQL COMMANDS

Masuk PostgreSQL: sudo -u postgres psql

Keluar PostgreSQL:

Lihat semua database:

Masuk database SAKUMI: akumi_prod

Lihat semua tabel:

Lihat struktur tabel: sers

Lihat isi tabel: SELECT * FROM users;

------------------------------------------------------------------------

3. LARAVEL ARTISAN

Masuk Laravel Tinker: php artisan tinker

Keluar tinker: exit

Migration

Cek status migration: php artisan migrate:status

Jalankan migration: php artisan migrate

Reset database (DEV ONLY): php artisan migrate:fresh

Reset + seeder: php artisan migrate:fresh –seed

------------------------------------------------------------------------

4. SEEDER

Jalankan seeder: php artisan db:seed

Seeder tertentu: php artisan db:seed –class=RolePermissionSeeder

------------------------------------------------------------------------

5. CACHE MANAGEMENT

Clear semua cache: php artisan optimize:clear

Manual clear: php artisan cache:clear php artisan config:clear php
artisan route:clear php artisan view:clear

------------------------------------------------------------------------

6. LOG DEBUGGING

Realtime log: tail -f storage/logs/laravel.log

Buka log: less storage/logs/laravel.log

------------------------------------------------------------------------

7. SERVICE MANAGEMENT

Cek PHP-FPM: systemctl status php8.2-fpm

Restart PHP: sudo systemctl restart php8.2-fpm

Cek Nginx: systemctl status nginx

Restart Nginx: sudo systemctl restart nginx

------------------------------------------------------------------------

8. GIT DEPLOYMENT

Pull update: git pull origin main

Install dependency: composer install –no-dev

Update autoload: composer dump-autoload

Setelah update: php artisan optimize:clear php artisan migrate

------------------------------------------------------------------------

9. RBAC (ROLE PERMISSION)

SAKUMI menggunakan Spatie Laravel Permission.

Contoh di Tinker:

Buat role: Role::create([‘name’=>‘super_admin’]);

Assign role: $user->assignRole(‘super_admin’);

Cek role user: $user->getRoleNames();

------------------------------------------------------------------------

10. SERVER SHORTCUT

Edit bashrc: nano ~/.bashrc

Tambahkan:

alias sakumi=“cd /var/www/sakumi” alias art=“php artisan” alias
psqlsakumi=“sudo -u postgres psql”

Reload: source ~/.bashrc

Sekarang bisa: sakumi art tinker psqlsakumi

------------------------------------------------------------------------

11. FILE PENTING

ENV: /var/www/sakumi/.env

LOG: /var/www/sakumi/storage/logs/laravel.log

NGINX CONFIG: /etc/nginx/sites-enabled/

------------------------------------------------------------------------

END OF CHEATSHEET
