#!/usr/bin/env bash
# Dosypuje oddzialowi skladniki schroniska (tylko lokalnie). Przyklad: scripts/dev-grant-squad.sh 1 --ember=5000 --stamps=50 --coins=20000
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose cp backend/bin backend:/var/www/html/ >/dev/null
docker compose exec -T -u root backend sh -c 'chown -R www-data:www-data bin && chmod -R a+rX bin'
docker compose exec -T backend php bin/dev_grant_squad.php "$@"
