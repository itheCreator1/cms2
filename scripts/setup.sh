#!/usr/bin/env bash
# Install and configure WordPress for ΕΑΡΕΣ. Safe to run repeatedly:
# every step either checks first or simply re-applies the same setting.
#
#   docker compose up -d && scripts/setup.sh
source "$(dirname "$0")/lib.sh"

wait_for_wordpress

if ! wp core is-installed 2>/dev/null; then
  echo "Installing WordPress..."
  wp core install \
    --url="$WP_URL" \
    --title="$WP_TITLE" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_email="$WP_ADMIN_EMAIL" \
    --admin_password="$WP_ADMIN_PASSWORD" \
    --skip-email
fi

echo "Languages: Greek (site default) + English (built in, per-user choice)..."
if wp language core install el; then
  wp site switch-language el
else
  warn "Could not download the Greek language pack (no access to wordpress.org?). Re-run setup.sh later."
fi

echo "Plugins..."
# Remove bundled plugins we do not use.
for bundled in akismet hello; do
  if wp plugin is-installed "$bundled"; then wp plugin delete "$bundled"; fi
done
for slug in two-factor simple-history; do
  if ! wp plugin is-installed "$slug"; then
    wp plugin install "$slug"
  fi
  wp plugin activate "$slug"
done
wp language plugin install --all el || warn "Some plugin translations could not be installed."
wp language core update || warn "Language pack update failed."

echo "Site settings..."
wp option update blogname "$WP_TITLE"
wp option update blogdescription "$WP_TAGLINE"
wp option update admin_email "$WP_ADMIN_EMAIL"
wp option update users_can_register 0
wp option update default_role contributor
wp option update timezone_string "Europe/Athens"
wp option update date_format "j F Y"
wp option update time_format "H:i"
wp option update start_of_week 1
wp option update blog_public 0          # local dev: keep search engines out
wp option update default_pingback_flag 0
wp option update default_ping_status closed
wp rewrite structure '/%postname%/'

if [[ -n "${WP_BACKUP_ADMIN_USER:-}" ]]; then
  if ! wp user get "$WP_BACKUP_ADMIN_USER" --field=ID >/dev/null 2>&1; then
    echo "Creating backup administrator $WP_BACKUP_ADMIN_USER..."
    wp user create "$WP_BACKUP_ADMIN_USER" "$WP_BACKUP_ADMIN_EMAIL" \
      --role=administrator --user_pass="$WP_BACKUP_ADMIN_PASSWORD"
  fi
fi

# The mu-plugin rebuilds roles on the first request; trigger it now.
wp eval 'eares_sync_roles();'
wp role list --fields=role,name

echo
echo "Done. Site: $WP_URL  ·  Admin: $WP_URL/wp-admin  ·  Mail: http://localhost:${MAILPIT_PORT:-8025}"
