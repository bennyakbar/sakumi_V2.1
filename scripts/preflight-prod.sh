#!/usr/bin/env bash

set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR" || exit 1

if [[ ! -f "artisan" ]]; then
  echo "Error: artisan not found. Run from project root."
  exit 1
fi

WITH_TESTS=0
for arg in "$@"; do
  case "$arg" in
    --with-tests) WITH_TESTS=1 ;;
    *)
      echo "Unknown option: $arg"
      echo "Usage: ./scripts/preflight-prod.sh [--with-tests]"
      exit 1
      ;;
  esac
done

PASS_COUNT=0
WARN_COUNT=0
FAIL_COUNT=0

pass() {
  PASS_COUNT=$((PASS_COUNT + 1))
  echo "PASS: $1"
}

warn() {
  WARN_COUNT=$((WARN_COUNT + 1))
  echo "WARN: $1"
}

fail() {
  FAIL_COUNT=$((FAIL_COUNT + 1))
  echo "FAIL: $1"
}

env_value() {
  local key="$1"
  local value
  value="$(grep -E "^${key}=" .env 2>/dev/null | head -n1 | cut -d'=' -f2- || true)"
  value="${value%\"}"
  value="${value#\"}"
  echo "$value"
}

run_quiet() {
  local output
  output="$("$@" 2>&1)"
  local status=$?
  echo "$output"
  return $status
}

echo "== Sakumi Production Preflight =="
echo "Project: $ROOT_DIR"
echo

# 1) Source code readiness (tests)
if [[ $WITH_TESTS -eq 1 ]]; then
  if run_quiet php artisan test >/tmp/preflight_tests.log; then
    pass "Source code quality gate passed (php artisan test)."
  else
    fail "Tests failed. See /tmp/preflight_tests.log"
  fi
else
  warn "Source code test gate skipped (run with --with-tests)."
fi

# 2) .env production checks
APP_ENV_VALUE="$(env_value "APP_ENV")"
APP_DEBUG_VALUE="$(env_value "APP_DEBUG")"
APP_URL_VALUE="$(env_value "APP_URL")"
APP_KEY_VALUE="$(env_value "APP_KEY")"
DB_MODE_VALUE="$(env_value "DB_SAKUMI_MODE")"
DB_REAL_DB_VALUE="$(env_value "DB_REAL_DATABASE")"
DB_REAL_USER_VALUE="$(env_value "DB_REAL_USERNAME")"
DB_REAL_PASSWORD_VALUE="$(env_value "DB_REAL_PASSWORD")"

if [[ "$APP_ENV_VALUE" == "production" ]]; then
  pass ".env APP_ENV=production"
else
  fail ".env APP_ENV must be production (current: ${APP_ENV_VALUE:-<empty>})"
fi

if [[ "${APP_DEBUG_VALUE,,}" == "false" || "$APP_DEBUG_VALUE" == "0" ]]; then
  pass ".env APP_DEBUG disabled"
else
  fail ".env APP_DEBUG must be false in production (current: ${APP_DEBUG_VALUE:-<empty>})"
fi

if [[ "$APP_URL_VALUE" == https://* ]]; then
  pass ".env APP_URL uses https"
else
  fail ".env APP_URL must start with https:// (current: ${APP_URL_VALUE:-<empty>})"
fi

if [[ -n "$APP_KEY_VALUE" ]]; then
  pass ".env APP_KEY is set"
else
  fail ".env APP_KEY is empty"
fi

if [[ "$DB_MODE_VALUE" == "real" ]]; then
  pass ".env DB_SAKUMI_MODE=real"
else
  fail ".env DB_SAKUMI_MODE must be real (current: ${DB_MODE_VALUE:-<empty>})"
fi

if [[ -n "$DB_REAL_DB_VALUE" && -n "$DB_REAL_USER_VALUE" && -n "$DB_REAL_PASSWORD_VALUE" ]]; then
  pass ".env real DB credentials are filled"
else
  fail ".env real DB credentials are incomplete"
fi

SESSION_SECURE_COOKIE_VALUE="$(env_value "SESSION_SECURE_COOKIE")"
if [[ "${SESSION_SECURE_COOKIE_VALUE,,}" == "true" || "$SESSION_SECURE_COOKIE_VALUE" == "1" ]]; then
  pass ".env SESSION_SECURE_COOKIE enabled"
else
  warn ".env SESSION_SECURE_COOKIE is not explicitly enabled"
fi

# 3) Server stack checks (local command availability)
if command -v php >/dev/null 2>&1; then
  pass "PHP binary available ($(php -r 'echo PHP_VERSION;'))"
else
  fail "PHP binary not found"
fi

if command -v composer >/dev/null 2>&1; then
  pass "Composer binary available ($(composer --version | head -n1))"
else
  warn "Composer not found in PATH"
fi

for ext in pdo pdo_pgsql mbstring openssl tokenizer json xml ctype fileinfo; do
  if php -m | grep -qi "^${ext}$"; then
    pass "PHP extension loaded: ${ext}"
  else
    fail "Missing PHP extension: ${ext}"
  fi
done

# 4) Database readiness
MIGRATE_STATUS_OUTPUT="$(run_quiet php artisan migrate:status --no-ansi)"
if [[ $? -ne 0 ]]; then
  fail "Unable to read migration status"
elif echo "$MIGRATE_STATUS_OUTPUT" | grep -q "Pending"; then
  warn "There are pending migrations — review the SQL preview below"
  echo
  echo "--- Migration SQL Preview (migrate --pretend) ---"
  php artisan migrate --pretend --no-ansi 2>&1
  echo "--- End of Migration SQL Preview ---"
  echo
else
  pass "All migrations are applied"
fi

# 5) Permission and RBAC checks
if run_quiet php artisan permission:show --no-ansi >/tmp/preflight_permission.log; then
  pass "Role/permission matrix is readable"
else
  fail "Unable to read role/permission matrix"
fi

if [[ -w "storage" && -w "bootstrap/cache" ]]; then
  pass "Filesystem permission OK for storage and bootstrap/cache"
else
  fail "storage or bootstrap/cache is not writable"
fi

# 6) HTTPS wiring checks
if grep -q "ForceHttps::class" bootstrap/app.php && grep -q "app()->environment('production')" app/Http/Middleware/ForceHttps.php; then
  pass "HTTPS middleware wiring exists for production"
else
  fail "HTTPS middleware wiring not detected"
fi

# 7) Backup readiness
BACKUP_LIST_OUTPUT="$(run_quiet php artisan backup:list --no-ansi)"
if [[ $? -ne 0 ]]; then
  fail "backup:list command failed"
elif echo "$BACKUP_LIST_OUTPUT" | grep -q "No backups present"; then
  fail "No backups present yet"
elif echo "$BACKUP_LIST_OUTPUT" | grep -q "❌"; then
  fail "Backup destination unhealthy"
else
  pass "Backup destination healthy with existing backup"
fi

if grep -q "Schedule::command('backup:run')" routes/console.php; then
  pass "Scheduled backup command registered"
else
  fail "Scheduled backup command missing in routes/console.php"
fi

echo
echo "Summary: ${PASS_COUNT} PASS, ${WARN_COUNT} WARN, ${FAIL_COUNT} FAIL"

if [[ $FAIL_COUNT -gt 0 ]]; then
  exit 1
fi

exit 0
