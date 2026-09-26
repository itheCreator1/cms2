#!/usr/bin/env bash
# Back up the database and the uploads folder into ./backups/.
# WordPress core and plugins are not included: setup.sh reinstalls them.
# Restore instructions: README.md, "Backup & restore".
source "$(dirname "$0")/lib.sh"

stamp="$(date +%Y%m%d-%H%M%S)"
mkdir -p backups

echo "Database -> backups/db-$stamp.sql.gz"
docker compose exec -T -e MYSQL_PWD="$DB_ROOT_PASSWORD" db \
  mariadb-dump -uroot --single-transaction --default-character-set=utf8mb4 "$DB_NAME" \
  | gzip > "backups/db-$stamp.sql.gz"

echo "Uploads  -> backups/uploads-$stamp.tar.gz"
docker compose exec -T -u www-data wordpress \
  sh -c 'mkdir -p /var/www/html/wp-content/uploads && tar -C /var/www/html/wp-content -cz uploads' \
  > "backups/uploads-$stamp.tar.gz"

ls -lh "backups/db-$stamp.sql.gz" "backups/uploads-$stamp.tar.gz"
