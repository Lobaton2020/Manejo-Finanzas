#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

COMPOSE="podman-compose"

usage() {
    cat <<EOF
Uso: ./scripts/dev.sh <comando>

Comandos:
  up         Construye (si es necesario) y levanta el container
  down       Detiene y elimina el container (conserva datos)
  restart    Reinicia el container (mantiene imagen)
  rebuild    Reconstruye la imagen desde cero y levanta
  logs       Sigue los logs en vivo (Ctrl+C para salir)
  shell      Abre una shell dentro del container como www-data
  status     Muestra estado actual del container
  clean      Down + elimina imagen construida
EOF
}

require_podman() {
    if ! command -v podman >/dev/null 2>&1; then
        echo "ERROR: podman no está instalado. Ejecuta: sudo pacman -S podman podman-compose"
        exit 1
    fi
    if ! command -v podman-compose >/dev/null 2>&1; then
        echo "ERROR: podman-compose no está instalado. Ejecuta: sudo pacman -S podman-compose"
        exit 1
    fi
}

cmd_up() {
    require_podman
    echo ">>> Levantando container de desarrollo..."
    $COMPOSE up -d --build
    echo ""
    echo "App disponible en: http://localhost:8080"
    echo "Logs en vivo con:   ./scripts/dev.sh logs"
}

cmd_down() {
    require_podman
    echo ">>> Deteniendo container..."
    $COMPOSE down
}

cmd_restart() {
    require_podman
    echo ">>> Reiniciando container..."
    $COMPOSE restart app
}

cmd_rebuild() {
    require_podman
    echo ">>> Reconstruyendo imagen desde cero..."
    $COMPOSE down
    $COMPOSE build --no-cache
    $COMPOSE up -d
    echo "App disponible en: http://localhost:8080"
}

cmd_logs() {
    require_podman
    podman logs -f manejo-finanzas-dev
}

cmd_shell() {
    require_podman
    podman exec -it --user 1000:1000 manejo-finanzas-dev /bin/bash
}

cmd_status() {
    require_podman
    podman ps -a --filter name=manejo-finanzas-dev --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
}

cmd_clean() {
    require_podman
    echo ">>> Deteniendo container y eliminando imagen..."
    $COMPOSE down --rmi local
}

case "${1:-}" in
    up)        cmd_up ;;
    down)      cmd_down ;;
    restart)   cmd_restart ;;
    rebuild)   cmd_rebuild ;;
    logs)      cmd_logs ;;
    shell)     cmd_shell ;;
    status)    cmd_status ;;
    clean)     cmd_clean ;;
    *)         usage; exit 1 ;;
esac