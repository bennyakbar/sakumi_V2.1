#!/usr/bin/env bash
set -euo pipefail

php artisan db:seed --class=Database\\Seeders\\ReconciliationStagingSeeder
