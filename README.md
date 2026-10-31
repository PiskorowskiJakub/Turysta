# Turysta

Monorepo:

- `frontend/` React + TypeScript + Vite
- `backend/` PHP 8.3 + Apache, REST pod `/api`
- `scripts/` backup.sh, restore.sh, simulate_economy.py (symulacja ekonomii)
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

## Ekonomia i symulacja gry (30 dni i rok)

Cele:

1. Przez pierwszy miesiąc gracz ma codziennie co rozwijać (dużo krótkich szkoleń) i dość dukatów, żeby kolejka (1 aktywne + 2 oczekujące) nie stała pusta.
2. Ukończenie wszystkich 49 awansów (5 światów) ma zająć **ok. roku** graczowi, który przez 2 miesiące gra aktywnie, a potem jak zwykły gracz.
3. W nowym świecie pojawiają się umiejętności o trudności **proporcjonalnej do etapu gry**: dają powiew świeżości, ale doświadczony gracz nie zaczyna od sekund.
4. Brak hiperinflacji: w pierwszym roku nie ma kwot w milionach.

### Parametry (migracje `011`, `015`)

- Koszt poziomu `n`: `floor(3 * 1,16^(n-1))` groszy, czas `round(5 * 1,16^(n-1))` sekund. Koszt rośnie tak samo szybko jak czas (ok. 0,6 grosza na sekundę), więc pieniądze nie są ścianką.
- Od poziomu 50 koszt i czas rosną **kwadratowo**: `(n/50)^2` (wcześniej koszt rósł sześciennie i po ok. 4 miesiącach blokował graczy brakiem dukatów). Poziom 50 to ok. 43 dk i ok. 2 h.
- Awans numer `g` (1..49) wymaga Kondycji `3 + 209 * (g/49)^0,55` (Orientacja 0,95 ×, Handel 0,8 × tej wartości), czyli ok. 28 na pierwszym awansie i 212 na ostatnim. Koszt awansu `max(50, 2,5 * koszt(f))`, czas `max(30, 2 * czas(f))`.
- Start: 2 dk premii. Bazowe wypłaty prac: targ 8 h (247 dk), hostel 12 h (420 dk), wyprawa 24 h (900 dk), z bonusem Handlu (patrz niżej).

### Profile graczy w symulacji

| Profil | Logowania (godziny) | Założenia |
|---|---|---|
| aktywny | 8, 11, 14, 17, 20, 22:30 | 6 wejść dziennie, uzupełnia kolejkę, odbiera prace |
| zwykły | 8:30, 13, 20:30 | 3 wejścia dziennie |
| okazjonalny | 19:30 | 1 wejście dziennie |
| mieszany | 60 dni aktywny, potem zwykły | profil docelowy do kalibracji tempa |

Gracz zawsze uzupełnia kolejkę, co 4. element to umiejętność świata (najtańsza z odblokowanych), przy każdej wizycie rozpoczyna dostępną pracę i inwestuje w awans, gdy ma wymagane poziomy.

Uruchomienie: `python scripts/simulate_economy.py --profile mieszany --days 365 --markdown` (profile: `aktywny`, `zwykly`, `okazjonalny`, `mieszany`; `--bez-bonusu` wyłącza bonus Handlu; `--sql` wypisuje parametry migracji 015). Kolumna „Zarobek” to wypłaty rozliczone danego dnia (dni bez zakończonej pracy pokazują 0).

### Tempo: kiedy gracz wchodzi do kolejnych światów

| Profil | Świat 2 (awans 10) | Świat 3 (awans 20) | Świat 4 (awans 30) | Świat 5 (awans 40) | Koniec (awans 49) |
|---|---:|---:|---:|---:|---:|
| mieszany (cel) | dzień 32 | dzień 83 | dzień 155 | dzień 250 | **dzień 363** |
| aktywny cały rok | dzień 32 | dzień 83 | dzień 155 | dzień 250 | dzień 363 |
| zwykły | dzień 46 | dzień 98 | dzień 170 | dzień 265 | dzień 378 |
| okazjonalny | dzień 115 | dzień 177 | dzień 251 | dzień 347 | dzień 460 |

Tempo jest ograniczone czasem kolejki (3 miejsca), a nie liczbą logowań, więc różnica między aktywnym a zwykłym graczem jest niewielka. Bez szkolenia umiejętności światów koniec wypada w dniu ok. 369.

### Pierwsze 30 dni: aktywny

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 1–8 | 0 | 18 | 0% | 7 s | 448 | 1 | 449 |
| 2 | 5–12 | 0 | 18 | 0% | 11 s | 705 | 1 | 1153 |
| 3 | 10–17 | 0 | 18 | 0% | 22 s | 735 | 2 | 1885 |
| 5 | 19–26 | 0 | 18 | 2% | 1 min | 788 | 9 | 3422 |
| 7 | 26–35 | 1 | 18 | 7% | 5 min | 834 | 37 | 5013 |
| 10 | 39–48 | 2 | 17 | 41% | 34 min | 880 | 214 | 7190 |
| 14 | 47–60 | 4 | 10 | 92% | 1.9 h | 929 | 408 | 9256 |
| 18 | 56–69 | 5 | 8 | 86% | 3.1 h | 952 | 576 | 11053 |
| 21 | 61–75 | 6 | 7 | 100% | 3.6 h | 971 | 592 | 12376 |
| 25 | 68–81 | 8 | 6 | 100% | 4.0 h | 991 | 517 | 14220 |
| 30 | 70–89 | 9 | 5 | 100% | 3.2 h | 1002 | 349 | 16937 |

### Pierwsze 30 dni: zwykły

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 0–5 | 0 | 9 | 0% | 6 s | 370 | 0 | 372 |
| 2 | 1–8 | 0 | 9 | 0% | 8 s | 652 | 0 | 1023 |
| 3 | 3–10 | 0 | 9 | 0% | 10 s | 656 | 1 | 1679 |
| 5 | 8–14 | 0 | 9 | 0% | 17 s | 685 | 1 | 3031 |
| 7 | 12–19 | 0 | 9 | 0% | 37 s | 710 | 2 | 4434 |
| 10 | 19–26 | 0 | 9 | 1% | 1 min | 745 | 6 | 6625 |
| 14 | 26–35 | 1 | 9 | 4% | 6 min | 783 | 20 | 9661 |
| 18 | 34–44 | 2 | 9 | 16% | 26 min | 820 | 85 | 12680 |
| 21 | 41–51 | 3 | 9 | 46% | 1.2 h | 841 | 255 | 14704 |
| 25 | 46–59 | 4 | 7 | 53% | 1.9 h | 868 | 287 | 17009 |
| 30 | 54–68 | 5 | 7 | 71% | 2.9 h | 891 | 432 | 19598 |

### Pierwsze 30 dni: okazjonalny

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 0–3 | 0 | 3 | 0% | 6 s | 0 | 0 | 2 |
| 2 | 0–4 | 0 | 3 | 0% | 6 s | 900 | 0 | 902 |
| 3 | 0–5 | 0 | 3 | 0% | 7 s | 0 | 0 | 902 |
| 5 | 0–7 | 0 | 3 | 0% | 9 s | 0 | 0 | 1801 |
| 7 | 2–8 | 0 | 3 | 0% | 7 s | 0 | 0 | 2701 |
| 10 | 4–11 | 0 | 3 | 0% | 11 s | 918 | 0 | 4527 |
| 14 | 7–14 | 0 | 3 | 0% | 17 s | 943 | 0 | 6404 |
| 18 | 10–17 | 0 | 3 | 0% | 26 s | 968 | 0 | 8329 |
| 21 | 12–19 | 0 | 3 | 0% | 50 s | 0 | 1 | 9310 |
| 25 | 15–22 | 0 | 3 | 0% | 1 min | 0 | 1 | 11303 |
| 30 | 19–26 | 0 | 3 | 0% | 1 min | 1034 | 2 | 14369 |

### Cały rok: profil mieszany (60 dni aktywny, potem zwykły)

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 30 | 70–89 | 9 | 5 | 100% | 3.2 h | 1002 | 349 | 16937 |
| 60 | 96–116 | 16 | 3 | 100% | 6.9 h | 1063 | 450 | 32102 |
| 90 | 112–135 | 21 | 3 | 100% | 9.4 h | 1028 | 608 | 46123 |
| 120 | 123–150 | 25 | 3 | 100% | 20.3 h | 1047 | 1508 | 60136 |
| 180 | 144–171 | 33 | 0 | 100% | 0 s | 1078 | 0 | 92027 |
| 240 | 162–187 | 39 | 1 | 100% | 1.1 d | 1100 | 554 | 124233 |
| 300 | 180–200 | 44 | 1 | 100% | 1.2 d | 1120 | 630 | 157746 |
| 365 | 195–212 | 49 | 1 | 100% | 5 min | 1135 | 2 | 195687 |

- Szkolenia trwają kilka sekund w pierwszych dniach, minuty około dnia 7, godziny od dnia ~14, a po pół roku ponad dobę. Kolejka pracuje bez przerwy (100%).
- Przychód roczny ok. 389 tys. dk, wypłaty stabilne na poziomie ok. 1,0-1,1 tys. dk dziennie. Saldo rośnie do ok. 196 tys. dk, bo koszty są ok. dwa razy mniejsze od wpływów. To zapas na przyszłe wydatki (sklep, wyposażenie); gdy się pojawią, wypłaty można zostawić bez zmian. Nigdzie nie ma kwot w milionach.

### Bonus Handlu do wypłat z prac

Poziom **Handlu** zwiększa wypłatę ze wszystkich prac: `bonus = 120% * L / (L + 120)`, sufit +120% (wypłata nigdy nie przekroczy 2,2 × bazowej).

| Poziom Handlu | 10 | 30 | 50 | 80 | 100 | 150 | 200 |
|---|---:|---:|---:|---:|---:|---:|---:|
| Bonus do wypłat | +9% | +24% | +35% | +48% | +55% | +67% | +75% |

Wypłata jest zapisywana przy starcie pracy (`job_runs.reward_snapshot_cents`), więc późniejszy awans Handlu nie zmienia trwającej pracy.

### Umiejętności światów (migracje `014`, `015`)

Każdy świat ma **10 własnych umiejętności** (razem 50), dobranych do jego klimatu, np. Las: Rozpoznawanie grzybów, Tropienie zwierząt, Budowa szałasu; Tundra: Ubiór warstwowy, Rakiety śnieżne, Zaprzęg psi; Pustynia: Gospodarka wodą, Jazda na wielbłądzie, Nawigacja po gwiazdach; Dżungla: Torowanie drogi maczetą, Wspinaczka po lianach, Spływ kajakiem. Zakładki światów, do których gracz jeszcze nie dotarł, są zablokowane (serwer odrzuca szkolenie błędem `skill_locked`). Maksymalnie 30 poziomów, nie wchodzą w wymagania awansu.

**Trudność zależy od etapu gry.** Poziom 1 umiejętności świata trwa 15% czasu szkolenia podstawowej umiejętności na poziomie, na którym gracz wchodzi do świata, a koszt jest proporcjonalny do czasu. Wzrost na poziom dobrany tak, że poziom 30 trwa tyle, ile podstawowa umiejętność na koniec świata. Nowy gracz w świecie 1 zaczyna od sekund; doświadczony wchodzi w świat 5 z godzinami zamiast sekund, gdy podstawowe trwają ponad dobę.

| Świat | Poziom podstawowych przy wejściu | Poziom 1 | Poziom 30 | Cały zestaw 10 umiejętności |
|---|---:|---|---|---|
| 1. Nowy Początek | 1 | 0,03 dk, 5 s | 19 dk, 53 min | ok. 1,9 d, 967 dk |
| 2. Las | 90 | 21,00 dk, 58 min | 297 dk, 13,7 h | ok. 61,3 d, 31 792 dk |
| 3. Tundra | 131 | 44,49 dk, 2,1 h | 459 dk, 21,3 h | ok. 104,3 d, 54 068 dk |
| 4. Pustynia | 163 | 68,88 dk, 3,2 h | 624 dk, 1,2 d | ok. 147,7 d, 76 541 dk |
| 5. Dżungla | 190 | 93,59 dk, 4,3 h | 777 dk, 1,5 d | ok. 189,1 d, 98 014 dk |

### Wnioski

- Pierwszy miesiąc: kilkanaście krótkich szkoleń dziennie u aktywnego gracza do ok. dnia 10, brak niedoborów dukatów w żadnym profilu.
- Tempo roku jest ograniczone przez czas kolejki, nie przez dukaty (zmieniony wzór kosztu po poziomie 50).
- Każde wejście do świata odświeża grę: 10 nowych umiejętności o trudności dostosowanej do etapu.
- Do ustalenia później: efekty umiejętności światów, ujścia dla nadwyżki dukatów, dodatkowe miejsca w kolejce za rangi (poprawiłyby tempo okazjonalnych graczy).
