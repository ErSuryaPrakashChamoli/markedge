#!/usr/bin/env bash
# Markedge database + media backup (Phase 11 §12). Encrypts with age when AGE_RECIPIENT is set, otherwise gzip only.
#   BACKUP_DIR=/var/backups/markedge APP_DIR=/var/www/markedge/current ./scripts/ops/backup.sh
# Retention: RETAIN_DAYS (default 30). Copy $BACKUP_DIR offsite with your object-storage tool (documented separately).
set -euo pipefail
umask 077   # backups are readable by the backup user only
APP_DIR="${APP_DIR:?set APP_DIR}"
BACKUP_DIR="${BACKUP_DIR:?set BACKUP_DIR}"
RETAIN_DAYS="${RETAIN_DAYS:-30}"
STAMP="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR/db" "$BACKUP_DIR/media"
chmod 700 "$BACKUP_DIR"

# Read connection settings from the application's .env without echoing them.
env_get() { grep -E "^$1=" "$APP_DIR/.env" | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }
DB_HOST="$(env_get DB_HOST)"; DB_PORT="$(env_get DB_PORT)"; DB_NAME="$(env_get DB_DATABASE)"; DB_USER="$(env_get DB_USERNAME)"; DB_PASS="$(env_get DB_PASSWORD)"
DEFAULTS="$(mktemp)"; chmod 600 "$DEFAULTS"
printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n' "${DB_HOST:-127.0.0.1}" "${DB_PORT:-3306}" "$DB_USER" "$DB_PASS" > "$DEFAULTS"

DB_FILE="$BACKUP_DIR/db/$DB_NAME-$STAMP.sql.gz"
mysqldump --defaults-extra-file="$DEFAULTS" --single-transaction --quick --no-tablespaces --routines --triggers --set-gtid-purged=OFF "$DB_NAME" | gzip -6 > "$DB_FILE"
rm -f "$DEFAULTS"
if [ -n "${AGE_RECIPIENT:-}" ]; then age -r "$AGE_RECIPIENT" -o "$DB_FILE.age" "$DB_FILE" && rm -f "$DB_FILE" && DB_FILE="$DB_FILE.age"; fi

MEDIA_FILE="$BACKUP_DIR/media/media-$STAMP.tar.gz"
tar -C "$APP_DIR/storage/app" -czf "$MEDIA_FILE" public 2>/dev/null || tar -czf "$MEDIA_FILE" --files-from /dev/null

find "$BACKUP_DIR/db" "$BACKUP_DIR/media" -type f -mtime +"$RETAIN_DAYS" -delete
printf '%s db=%s (%s) media=%s (%s)\n' "$STAMP" "$DB_FILE" "$(du -h "$DB_FILE" | cut -f1)" "$MEDIA_FILE" "$(du -h "$MEDIA_FILE" | cut -f1)" | tee -a "$BACKUP_DIR/backup.log"
