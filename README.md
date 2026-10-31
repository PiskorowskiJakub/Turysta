# Turysta

Monorepo:

- `frontend/` React + TypeScript + Vite
- `backend/` PHP 8.3 + Apache, REST pod `/api`
- `scripts/` backup.sh, restore.sh
- `docker-compose.yml` wspolny stos

## Uruchomienie calosci

```
cp .env.example .env   # uzupelnij hasla
docker compose build
docker compose up -d
docker compose exec backend php bin/migrate.php
```

- Gra: http://localhost (Traefik)
- Health: http://localhost/api/health
- phpMyAdmin: http://127.0.0.1:8081
- Mailpit: http://127.0.0.1:8025
- Frontend z hot reload (Vite): `docker compose --profile hmr up -d frontend-dev` -> http://127.0.0.1:5173

Nigdy nie uruchamiaj `docker compose down -v` na produkcji (kasuje baze).
TLS (Let's Encrypt) dojdzie przy wdrozeniu na VPS.

## Ekonomia i symulacja pierwszych 30 dni

Cel: przez pierwszy miesiac gracz ma codziennie co rozwijac i wystarczajaco duzo monet, zeby kolejka (1 aktywne + 2 oczekujace szkolenia) nigdy nie stala pusta z braku pieniedzy.

### Parametry (migracja `011_economy_v3.sql`)

- Koszt poziomu: `floor(3 * 1,16^(n-1))` groszy, czas: `round(5 * 1,16^(n-1))` sekund. Koszt rosnie tak samo szybko jak czas (stosunek ~0,6 grosza na sekunde), wiec pieniadze nigdy nie "uciekaja" przed czasem.
- Powyzej poziomu 50 krzywa splaszcza sie: koszt `* (n/50)^3`, czas `* (n/50)^2`.
- Poziom 50 to ok. 43 monety i ok. 2 h. Pierwsze poziomy trwaja kilka sekund, minuty pojawiaja sie okolo dnia 7-10, godziny okolo dnia 10-18 (zaleznie od aktywnosci).
- Awans (rozdzial): wymagania `req(g) = (3+round(2g), 3+round(1,9g), 2+round(1,6g))` poziomow Kondycji/Orientacji/Handlu, koszt `max(50, round(2,5 * koszt(f)))`, czas `max(30, 2 * czas(f))`.
- Start: 200 groszy (2 monety) premii na koncie.
- Prace: targ 8 h (247 monet), hostel 12 h (420 monet, cooldown 2 h), wyprawa 24 h (900 monet, cooldown 4 h).

### Profile graczy w symulacji

| Profil | Logowania (godziny) | Zalozenia |
|---|---|---|
| aktywny | 8, 11, 14, 17, 20, 22:30 | 6 wejsc dziennie, uzupelnia kolejke i odbiera prace |
| zwykly | 8:30, 13, 20:30 | 3 wejscia dziennie |
| okazjonalny | 19:30 | 1 wejscie dziennie |

Symulacja zaklada ze gracz zawsze uzupelnia kolejke do 3 pozycji (najtansze/najkrotsze wg sensownej kolejnosci), przy kazdej wizycie rozpoczyna dostepna prace i inwestuje w awans, gdy ma wymagane poziomy.

Uruchomienie: `python scripts/simulate_economy.py --profile aktywny --days 30 --markdown` (profile: `aktywny`, `zwykly`, `okazjonalny`). Kolumna "Czas pracy kolejki" to procent doby, w ktorym kolejka cos trenuje; kwoty w monetach.

### Wyniki: aktywny

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 5–6 | 1 | 18 | 0% | 8 s | 448 | 1 | 448 |
| 2 | 11–11 | 3 | 18 | 0% | 17 s | 695 | 2 | 1141 |
| 3 | 15–17 | 6 | 18 | 1% | 40 s | 695 | 5 | 1831 |
| 5 | 26–27 | 11 | 18 | 4% | 2 min | 695 | 20 | 3190 |
| 7 | 36–37 | 17 | 18 | 18% | 14 min | 695 | 100 | 4433 |
| 10 | 47–50 | 23 | 11 | 75% | 1.7 h | 695 | 432 | 5478 |
| 14 | 56–59 | 27 | 7 | 83% | 3.1 h | 695 | 572 | 6101 |
| 18 | 64–67 | 31 | 6 | 100% | 4.0 h | 695 | 744 | 6036 |
| 21 | 69–71 | 34 | 5 | 98% | 4.8 h | 695 | 800 | 5948 |
| 25 | 74–77 | 37 | 4 | 100% | 5.8 h | 695 | 844 | 5435 |
| 30 | 80–83 | 40 | 3 | 100% | 5.3 h | 695 | 565 | 4434 |

### Wyniki: zwykły

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 2–4 | 0 | 9 | 0% | 6 s | 370 | 0 | 372 |
| 2 | 5–6 | 1 | 9 | 0% | 11 s | 652 | 1 | 1023 |
| 3 | 7–9 | 2 | 9 | 0% | 15 s | 652 | 1 | 1673 |
| 5 | 13–14 | 5 | 9 | 0% | 34 s | 652 | 2 | 2973 |
| 7 | 18–19 | 8 | 9 | 1% | 1 min | 652 | 4 | 4269 |
| 10 | 26–27 | 11 | 9 | 2% | 3 min | 652 | 11 | 6198 |
| 14 | 36–37 | 17 | 9 | 10% | 16 min | 652 | 56 | 8658 |
| 18 | 45–48 | 22 | 9 | 47% | 1.3 h | 652 | 273 | 10588 |
| 21 | 51–55 | 25 | 7 | 73% | 2.6 h | 652 | 435 | 11361 |
| 25 | 58–62 | 29 | 6 | 82% | 3.4 h | 652 | 567 | 11974 |
| 30 | 67–69 | 33 | 5 | 89% | 4.6 h | 652 | 734 | 11890 |

### Wyniki: okazjonalny

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 0–2 | 0 | 3 | 0% | 5 s | 0 | 0 | 2 |
| 2 | 1–3 | 0 | 3 | 0% | 6 s | 900 | 0 | 902 |
| 3 | 2–4 | 0 | 3 | 0% | 7 s | 0 | 0 | 902 |
| 5 | 5–5 | 0 | 3 | 0% | 8 s | 0 | 0 | 1801 |
| 7 | 6–7 | 1 | 3 | 0% | 11 s | 0 | 0 | 2701 |
| 10 | 9–9 | 3 | 3 | 0% | 20 s | 900 | 1 | 4499 |
| 14 | 12–13 | 4 | 3 | 0% | 28 s | 900 | 0 | 6297 |
| 18 | 15–17 | 6 | 3 | 0% | 48 s | 900 | 1 | 8094 |
| 21 | 18–19 | 8 | 3 | 0% | 1 min | 0 | 2 | 8989 |
| 25 | 21–23 | 9 | 3 | 0% | 1 min | 0 | 2 | 10782 |
| 30 | 26–27 | 11 | 3 | 1% | 3 min | 900 | 3 | 13464 |

### Wnioski

- **Brak "scian pieniezny":** w zadnym profilu kolejka nie stoi z braku monet; saldo caly czas rosnie lub jest stabilne.
- **Aktywny** ma co rozwijac codziennie: dni 1-7 to kilkanascie szybkich szkolen dziennie, od ok. dnia 10 szkolenia trwaja godziny i kolejka pracuje niemal caly czas. Do dnia 30 osiaga ok. 80 poziomow i 40. rozdzial.
- **Zwykly** (3 wejscia) ma od pierwszego dnia 9 szkolen, godzinowe szkolenia pojawiaja sie okolo dnia 18, a po 30 dniach jest na ok. 33. rozdziale.
- **Okazjonalny** (1 wejscie) jest ograniczony liczba miejsc w kolejce (3 na wizyte), wiec postep jest wolniejszy, ale kazda wizyta ma 3 sensowne szkolenia. Dzwignia na przyszlosc: dodatkowe miejsca w kolejce za rangi.
- **Nadwyzka monet** (saldo 4-13 tys.) jest zarezerwowana na przyszle wydatki (sklep, wyposazenie).
- Wartosci mozna zmieniac w `skill_definitions` (migracja) i sprawdzac ponownie symulatorem.
