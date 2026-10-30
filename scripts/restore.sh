#!/usr/bin/env bash
# Uzycie: scripts/restore.sh backups/turysta-YYYYMMDD-HHMMSS.sql.gz
set -euo pipefail
[ $# -eq 1 ] || { echo "Podaj plik kopii"; exit 1; }
cd "$(dirname "$0")/.."
set -a; . ./.env; set +a
read -r -p "Nadpisze baze $DB_NAME. Wpisz TAK: " ans
[ "$ans" = "TAK" ] || exit 1
gunzip -c "$1" | docker compose exec -T db mysql -uroot -p"$DB_ROOT_PASSWORD" "$DB_NAME"
echo "Przywrocono z $1"
