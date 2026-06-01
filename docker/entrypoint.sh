#!/usr/bin/env bash
set -euo pipefail

APP_DIR=/var/www/html

if [ ! -f "${APP_DIR}/artisan" ]; then
  echo "[entrypoint] Laravel no encontrado en ${APP_DIR}. Instalando skeleton..."
  TMP_DIR="$(mktemp -d)"
  composer create-project --prefer-dist laravel/laravel "${TMP_DIR}" "^11.0"
  cp -a "${TMP_DIR}/." "${APP_DIR}/"
  rm -rf "${TMP_DIR}"
fi

cd "${APP_DIR}"

if [ ! -f ".env" ]; then
  cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env; then
  php artisan key:generate --ansi
fi

# `php artisan serve` no propaga env vars del contenedor a sus workers HTTP,
# así que reescribimos las claves DB_* en .env para que phpdotenv las cargue.
write_env() {
  local key="$1" val="$2"
  if grep -qE "^#?\s*${key}=" .env; then
    sed -i -E "s|^#?\s*${key}=.*|${key}=${val}|" .env
  else
    echo "${key}=${val}" >> .env
  fi
}

write_env DB_CONNECTION "${DB_CONNECTION:-pgsql}"
write_env DB_HOST       "${DB_HOST:-postgres}"
write_env DB_PORT       "${DB_PORT:-5432}"
write_env DB_DATABASE   "${DB_DATABASE:-apifake}"
write_env DB_USERNAME   "${DB_USERNAME:-apifake}"
write_env DB_PASSWORD   "${DB_PASSWORD:-apifake}"

# API_KEY: usar la que venga por env, o la que ya este en .env; si ambas vacias, generar.
existing_key="$( (grep -E '^API_KEY=' .env || true) | head -1 | cut -d= -f2- )"
if [ -z "${API_KEY:-}" ] && [ -z "${existing_key}" ]; then
  API_KEY="apifake_$(php -r 'echo bin2hex(random_bytes(24));')"
  echo ""
  echo "============================================================"
  echo "  API_KEY generada (entregar a los consumidores):"
  echo "  ${API_KEY}"
  echo "============================================================"
  echo ""
fi
if [ -n "${API_KEY:-}" ]; then
  write_env API_KEY "${API_KEY}"
fi

echo "[entrypoint] Esperando a Postgres en ${DB_HOST:-postgres}:${DB_PORT:-5432}..."
until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-apifake}" >/dev/null 2>&1; do
  sleep 1
done
echo "[entrypoint] Postgres listo."

php artisan migrate --force || true

exec "$@"
