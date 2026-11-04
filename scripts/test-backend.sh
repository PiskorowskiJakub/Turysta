#!/usr/bin/env bash
# Testy backendu w kontenerze docker compose. Argumenty trafiaja do phpunita, np.: scripts/test-backend.sh --filter SquadTrailTest
#
# Obraz backendu jest budowany bez wolumenow, wiec kontener ma kod z chwili budowy. Skrypt kopiuje do niego biezacy kod
# (src, bin, migrations), testy i konfiguracje oraz stosuje migracje, dzieki czemu testy zawsze dotycza tego, co masz na dysku.
# Zmiany sa tylko w dzialajacym kontenerze; do ogladania aplikacji w przegladarce nadal przebuduj obraz (docker compose up -d --build).
set -euo pipefail
cd "$(dirname "$0")/.."

# Obraz ma zaleznosci bez dev, wiec doinstalowujemy phpunita.
docker compose exec -T backend sh -c 'test -x vendor/bin/phpunit || composer install --no-interaction --quiet'

for path in src bin migrations tests; do
  docker compose cp "backend/$path" backend:/var/www/html/ >/dev/null
done
docker compose cp backend/phpunit.xml backend:/var/www/html/phpunit.xml >/dev/null
docker compose exec -T backend composer dump-autoload --quiet
docker compose exec -T backend php bin/migrate.php >/dev/null

docker compose exec backend vendor/bin/phpunit "$@"
