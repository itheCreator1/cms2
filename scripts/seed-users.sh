#!/usr/bin/env bash
# Create one FAKE test user per role (local development only).
# All share the password SEED_PASSWORD from .env. Safe to re-run.
# The work happens in scripts/seed-users.php, in a single wp-cli container.
source "$(dirname "$0")/lib.sh"

if [[ "$(wp eval 'echo wp_get_environment_type();')" == "production" ]]; then
  echo "Refusing to seed test users in production." >&2
  exit 1
fi

EARES_PASSWORD="$SEED_PASSWORD" wp eval-file - < scripts/seed-users.php

echo "Password for all test users: $SEED_PASSWORD"
