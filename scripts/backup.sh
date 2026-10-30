#!/usr/bin/env bash
# Kopia zapasowa bazy: mysqldump (single-transaction) -> gz, retencja 14 dni.
# Cron (VPS): 15 3 * * * /sciezka/do/turysta/scripts/backup.sh
set -euo pipefail
cd "$(dirname "$0")/.."
set -a; . ./.env; set +a
DIR="${BACKUP_DIR:-./backups}"
mkdir -p "$DIR"
FILE="$DIR/turysta-$(date -u +%Y%m%d-%H%M%S).sql.gz"
docker compose exec -T db mysqldump -uroot -p"$DB_ROOT_PASSWORD" \
  --single-transaction --routines --triggers "$DB_NAME" | gzip > "$FILE"
find "$DIR" -name 'turysta-*.sql.gz' -mtime +14 -delete
[ -n "${RCLONE_REMOTE:-}" ] && rclone copy "$FILE" "$RCLONE_REMOTE"
echo "OK: $FILE"
