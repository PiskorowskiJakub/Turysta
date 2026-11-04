#!/usr/bin/env bash
# Testy backendu w kontenerze docker compose. Argumenty trafiaja do phpunita, np.: scripts/test-backend.sh --filter SquadTrailTest
set -euo pipefail
cd "$(dirname "$0")/.."

# Obraz ma zaleznosci bez dev i bez katalogu tests, wiec doinstalowujemy phpunita i kopiujemy testy.
docker compose exec -T backend sh -c 'test -x vendor/bin/phpunit || composer install --no-interaction --quiet'
docker compose cp backend/tests backend:/var/www/html/
docker compose cp backend/phpunit.xml backend:/var/www/html/phpunit.xml
docker compose exec backend vendor/bin/phpunit "$@"
