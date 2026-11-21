#!/usr/bin/env bash
#
# Servidor de desarrollo para Manejo-Finanzas.
#
# Usa el servidor embebido de PHP con un router que emula el rewrite del
# .htaccess, para poder probar la app completa (login, UI, webhook) sin
# Apache ni containers.
#
#   ./scripts/dev-server.sh          # http://127.0.0.1:8124
#   ./scripts/dev-server.sh 9000     # otro puerto
#
# Requisitos: PHP CLI con pdo_mysql y curl. La base de datos es la que
# este en el .env (por defecto 192.168.20.240/finanzas).
#
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

PORT="${1:-8124}"
PHP_BIN="${PHP_BIN:-php}"

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    echo "ERROR: no se encontro PHP en el PATH."
    exit 1
fi

PHP_ARGS=()
# pdo_mysql no viene en el PHP del sistema (Arch): se carga si hace falta.
if ! "$PHP_BIN" -m 2>/dev/null | grep -q "^pdo_mysql$"; then
    if [ -f /usr/lib/php/modules/pdo_mysql.so ]; then
        PHP_ARGS+=(-d extension=pdo_mysql)
    else
        echo "AVISO: pdo_mysql no esta disponible. Instala el paquete de PHP con pdo_mysql."
    fi
fi
# opcache en CLI sirve codigo viejo (revalidate_freq=180) y hace que los
# cambios no se vean al instante mientras se developa.
PHP_ARGS+=(-d opcache.enable=0)

echo ">>> Manejo-Finanzas dev server"
echo ">>> http://127.0.0.1:${PORT}"
echo ">>> (Ctrl+C para detener)"
echo ""

exec "$PHP_BIN" "${PHP_ARGS[@]}" -S "127.0.0.1:${PORT}" -t "$PROJECT_ROOT" "$PROJECT_ROOT/scripts/dev-router.php"
