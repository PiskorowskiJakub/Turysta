# Turysta

Monorepo:

- `frontend/` React (Docker standalone: `cd frontend && docker compose up --build`)
- `backend/` PHP 8.3 + Apache, REST pod `/api`
- `scripts/` backup.sh, restore.sh
- `docker-compose.yml` wspolny stos

## Uruchomienie calosci

```
cp .env.example .env   # uzupelnij hasla
docker compose --profile dev up --build -d
docker compose exec backend php bin/migrate.php
```

- Gra: http://localhost (Traefik)
- Health: http://localhost/api/health
- phpMyAdmin: http://127.0.0.1:8081
- Mailpit: http://127.0.0.1:8025

Nigdy nie uruchamiaj `docker compose down -v` na produkcji (kasuje baze).
TLS (Let's Encrypt) dojdzie przy wdrozeniu na VPS.
