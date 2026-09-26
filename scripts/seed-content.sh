#!/usr/bin/env bash
# Create FAKE sample posts so the theme can be reviewed (local development
# only). Safe to re-run: existing sample posts are left alone.
# The work happens in scripts/seed-content.php.
source "$(dirname "$0")/lib.sh"

if [[ "$(wp eval 'echo wp_get_environment_type();')" == "production" ]]; then
  echo "Refusing to seed sample content in production." >&2
  exit 1
fi

wp eval-file - < scripts/seed-content.php
