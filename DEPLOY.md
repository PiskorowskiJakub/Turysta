# Wdrożenie na serwerze

Gra działa za **istniejącym Traefikiem** (tym samym, który obsługuje drugą stronę na serwerze). Nie uruchamia własnego
Traefika, phpMyAdmina ani Mailpita. Używa pliku `docker-compose.prod.yml`.

Usługi w tym pliku nazywają się `turysta-web` i `turysta-api` (nie `frontend`/`backend`), bo druga strona na serwerze
ma kontenery o takich nazwach w tej samej sieci Traefika. Dlatego polecenia produkcyjne używają `turysta-api`.

## 1. Dane z istniejącego Traefika
Potrzebne są trzy nazwy (z konfiguracji Traefika drugiej strony):

- sieć Traefika: `docker network ls` (sieć, do której podłączone są kontenery strony),
- nazwa entrypointu HTTPS (zwykle `websecure`),
- nazwa resolvera Let's Encrypt (`certificatesResolvers.<nazwa>` w konfiguracji Traefika).

Przekierowanie HTTP → HTTPS ustawiasz tak, jak masz je na drugiej stronie (zwykle na poziomie entrypointu).

## 2. Domena
Rekord DNS **A** (i opcjonalnie AAAA) domeny wskazuje na adres IP serwera. Poczekaj, aż się rozpropaguje.

## 3. Pliki na serwerze
```bash
git clone https://github.com/PiskorowskiJakub/Turysta.git && cd Turysta
git checkout main
cp .env.prod.example .env
nano .env        # domena, nazwy z Traefika, hasła do bazy, SMTP
```
W `.env` jest `COMPOSE_FILE=docker-compose.prod.yml`, więc zwykłe `docker compose ...` używa pliku produkcyjnego
(także skrypty `scripts/backup.sh` i `scripts/restore.sh`).

## 4. Start
```bash
docker compose up -d --build
docker compose exec turysta-api php bin/migrate.php
docker compose exec turysta-api php bin/set_role.php twoj@email.pl admin
docker builder prune -f
```
Po chwili gra jest pod `https://twojadomena.pl`. Certyfikat wystawia Traefik drugiej strony przy pierwszym wejściu.

## 5. Poczta (konieczna do rejestracji)
Ustaw w `.env` prawdziwy SMTP (`MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION=tls`, `MAIL_USER`, `MAIL_PASSWORD`, `MAIL_FROM`).
Dla domeny dodaj rekordy **SPF** i **DKIM** (podaje je dostawca poczty), a najlepiej też **DMARC**, inaczej wiadomości
z linkiem potwierdzającym trafią do spamu. Sprawdź: załóż konto testowe i potwierdź adres.

## 6. Po uruchomieniu
- Otwórz grę z konsolą przeglądarki. Jeśli nie ma ostrzeżeń CSP, w `frontend/security-headers.inc` zmień
  `Content-Security-Policy-Report-Only` na `Content-Security-Policy` i przebuduj frontend.
- Gdy HTTPS działa poprawnie, możesz włączyć HSTS (etykiety Traefika w `docker-compose.prod.yml`, dla obu routerów):
  ```
  - traefik.http.middlewares.turysta-hsts.headers.stsSeconds=31536000
  - traefik.http.routers.turysta-web.middlewares=turysta-hsts
  - traefik.http.routers.turysta-api.middlewares=turysta-hsts
  ```
- Kopie zapasowe: `scripts/backup.sh` z crona (np. `15 3 * * * /sciezka/Turysta/scripts/backup.sh`), najlepiej z `RCLONE_REMOTE`
  poza serwer. Raz sprawdź odtwarzanie: `scripts/restore.sh`.
- Zapora: otwarte tylko SSH, 80 i 443. Baza nie wystawia żadnych portów na zewnątrz.
- Aktualizacja: `git pull && docker compose up -d --build && docker compose exec turysta-api php bin/migrate.php && docker builder prune -f`.

## Zasoby
Obrazy ok. 2,2 GB, dane bazy na start ok. 230 MB, RAM: 1–2 GB dla gry. Logi kontenerów są ograniczone (3 × 10 MB na usługę).
