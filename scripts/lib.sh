# Shared helpers for scripts/*.sh — sourced, not executed.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ ! -f .env ]]; then
  echo "Missing .env — copy .env.example to .env and set the passwords first." >&2
  exit 1
fi
set -a
# shellcheck disable=SC1091
source .env
set +a

# Run wp-cli inside the one-shot wpcli container.
wp() {
  docker compose --progress quiet run --rm -T wpcli wp "$@"
}

warn() {
  echo "WARNING: $*" >&2
}

wait_for_wordpress() {
  echo "Waiting for WordPress files..."
  for _ in $(seq 1 60); do
    if docker compose exec -T wordpress test -f /var/www/html/wp-config.php 2>/dev/null; then
      return 0
    fi
    sleep 2
  done
  echo "WordPress container is not ready. Is 'docker compose up -d' running?" >&2
  exit 1
}
