# shellcheck shell=bash
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

# Run wp-cli inside the one-shot wpcli container. EARES_PASSWORD, when set
# (EARES_PASSWORD=... wp ...), is handed to it as an inherited environment
# variable, so passwords never show up on a command line or in output.
wp() {
  docker compose --progress quiet run --rm -T -e EARES_PASSWORD wpcli wp "$@"
}

set_password() { # login, password
  EARES_PASSWORD="$2" wp eval-file - "$1" <<'PHP'
<?php
$user = get_user_by( 'login', $args[0] );
if ( ! $user ) {
	WP_CLI::error( 'No such user.' );
}
wp_set_password( getenv( 'EARES_PASSWORD' ), $user->ID );
PHP
}

# A throwaway password for account creation; set_password replaces it.
random_password() {
  head -c 32 /dev/urandom | base64 | tr -d '/+='
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

# The .env.example passwords are fine on a laptop, never anywhere else.
require_changed_passwords() {
  local var defaults=()
  for var in DB_PASSWORD DB_ROOT_PASSWORD WP_ADMIN_PASSWORD; do
    if [[ "${!var:-}" == change-me* ]]; then defaults+=("$var"); fi
  done
  (( ${#defaults[@]} )) || return 0
  if [[ "${WP_ENVIRONMENT_TYPE:-local}" == "local" ]]; then
    warn "Default passwords in .env: ${defaults[*]}. Fine for local development only."
  else
    echo "Refusing to run: change ${defaults[*]} in .env (WP_ENVIRONMENT_TYPE=${WP_ENVIRONMENT_TYPE})." >&2
    exit 1
  fi
}
