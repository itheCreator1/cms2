#!/usr/bin/env bash
# Update WordPress core, plugins and translations. Run at least monthly.
#
# The browser cannot update anything (DISALLOW_FILE_MODS) and pulling a newer
# wordpress image does not touch core in the wp_data volume, so this script is
# the only update path. Take a backup first: scripts/backup.sh
source "$(dirname "$0")/lib.sh"

wait_for_wordpress

echo "Updating WordPress core..."
wp core update
wp core update-db

echo "Updating plugins..."
wp plugin update --all

echo "Updating translations..."
wp language core update || warn "Core translation update failed."
wp language plugin update --all || warn "Plugin translation update failed."

wp core version --extra
wp plugin list --fields=name,status,version
