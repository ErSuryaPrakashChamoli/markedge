#!/usr/bin/env bash
# Markedge restore rehearsal (Phase 11 §13): restores a backup into a SEPARATE database and verifies it.
# Never points at the production database.
#   BACKUP=/var/backups/markedge/db/markedge-....sql.gz RESTORE_DB=markedge_restore MYSQL_ADMIN="mysql -uroot" ./scripts/ops/restore-rehearsal.sh
set -euo pipefail
BACKUP="${BACKUP:?set BACKUP to a .sql.gz (or .age) file}"
RESTORE_DB="${RESTORE_DB:-markedge_restore}"
MYSQL_ADMIN="${MYSQL_ADMIN:?set MYSQL_ADMIN, e.g. 'mysql --defaults-extra-file=/root/.my.cnf'}"
START=$(date +%s)

case "$RESTORE_DB" in markedge|production|*_prod) echo "Refusing to restore into $RESTORE_DB"; exit 1;; esac

echo "[restore] creating $RESTORE_DB"
$MYSQL_ADMIN -e "DROP DATABASE IF EXISTS \`$RESTORE_DB\`; CREATE DATABASE \`$RESTORE_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if [[ "$BACKUP" == *.age ]]; then age -d "$BACKUP" | gunzip | $MYSQL_ADMIN "$RESTORE_DB"; else gunzip -c "$BACKUP" | $MYSQL_ADMIN "$RESTORE_DB"; fi

echo "[restore] verifying schema and content"
$MYSQL_ADMIN "$RESTORE_DB" -e "
SELECT 'tables' AS metric, COUNT(*) AS value FROM information_schema.tables WHERE table_schema='$RESTORE_DB'
UNION ALL SELECT 'migrations', COUNT(*) FROM migrations
UNION ALL SELECT 'pages', COUNT(*) FROM pages
UNION ALL SELECT 'services', COUNT(*) FROM services
UNION ALL SELECT 'products', COUNT(*) FROM products
UNION ALL SELECT 'leads', COUNT(*) FROM leads
UNION ALL SELECT 'media', COUNT(*) FROM media
UNION ALL SELECT 'orphan_seo', COUNT(*) FROM seo_meta s WHERE s.seoable_type='service' AND NOT EXISTS (SELECT 1 FROM services x WHERE x.id=s.seoable_id)
UNION ALL SELECT 'orphan_media', COUNT(*) FROM media m WHERE m.model_type='service' AND NOT EXISTS (SELECT 1 FROM services x WHERE x.id=m.model_id);"
echo "[restore] done in $(( $(date +%s) - START ))s. Now run the application against $RESTORE_DB (DB_DATABASE override) and smoke-test."
