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
3. Awans wymaga także rozwoju umiejętności danego świata, a przejście do nowego świata to **wydarzenie** (długie i drogie).
4. W nowym świecie umiejętności mają trudność **proporcjonalną do etapu gry**: dają powiew świeżości, ale doświadczony gracz nie zaczyna od sekund.
5. Brak hiperinflacji: w pierwszym roku nie ma kwot w milionach.

### Parametry (migracje `011`, `015`, `016`)

- Koszt poziomu `n`: `floor(3 * 1,16^(n-1))` groszy, czas `round(5 * 1,16^(n-1))` sekund. Koszt rośnie tak samo szybko jak czas (ok. 0,6 grosza na sekundę), więc pieniądze nie są ścianką. Od poziomu 50 koszt i czas rosną **kwadratowo**: `(n/50)^2`.
- **Wymagania awansu `g` (1..49), podstawowe umiejętności:** Kondycja `3 + 177 * (g/49)^0,55` (Orientacja 0,95 ×, Handel 0,8 × tej wartości), czyli ok. 24 na pierwszym awansie i 180 na ostatnim.
- **Wymagania awansu, umiejętności świata:** w obrębie świata rozdział `n` wymaga **każdej z 10 umiejętności świata** na poziomie `ceil(20 * (n-1) / 10)` (rozdział 2: poziom 2, rozdział 10: poziom 18). **Przejście do nowego świata wymaga wszystkich 10 umiejętności obecnego świata na maksymalnym poziomie (20).** Wymaganie jest liczone przez serwer (`ChapterService::worldRequirement`) i widoczne w zakładce Awans.
- **Koszt i czas zwykłego awansu:** `max(50, 2,5 * koszt(f))` oraz `max(30, 2 * czas(f))`, gdzie `f` to wymagany poziom Kondycji.
- **Awans do nowego świata (wydarzenie):** **20× koszt i 6× czas** zwykłego awansu.

| Przejście | Zwykły awans w tym świecie (czas / koszt) | Awans do nowego świata (czas / koszt) |
|---|---|---|
| 1 → 2 (Las) | 8.5 h / 230 dk | **2.4 d / 5 122 dk** |
| 2 → 3 (Tundra) | 18.7 h / 504 dk | **4.9 d / 10 645 dk** |
| 3 → 4 (Pustynia) | 1.2 d / 799 dk | **7.6 d / 16 454 dk** |
| 4 → 5 (Dżungla) | 1.7 d / 1 092 dk | **10.4 d / 22 396 dk** |

- Start: 2 dk premii. Bazowe wypłaty prac: targ 8 h (247 dk), hostel 12 h (420 dk), wyprawa 24 h (900 dk), z bonusem Handlu (niżej).

### Umiejętności światów (migracje `014`, `016`)

Każdy świat ma **10 własnych umiejętności** (razem 50), dobranych do klimatu świata, np. Las: Rozpoznawanie grzybów, Tropienie zwierząt, Budowa szałasu; Tundra: Ubiór warstwowy, Rakiety śnieżne, Zaprzęg psi; Pustynia: Gospodarka wodą, Jazda na wielbłądzie, Nawigacja po gwiazdach; Dżungla: Torowanie drogi maczetą, Wspinaczka po lianach, Spływ kajakiem. Zakładki światów, do których gracz jeszcze nie dotarł, są zablokowane (`skill_locked`). Maksymalnie **20 poziomów**.

**Trudność zależy od etapu gry.** Poziom 1 trwa 15% czasu szkolenia podstawowej umiejętności na poziomie, na którym gracz wchodzi do świata, a ostatni poziom (20) ok. 25% czasu podstawowej na końcu świata. Koszt jest proporcjonalny do czasu. Nowy gracz w świecie 1 zaczyna od sekund; doświadczony wchodzi w świat 5 z godzinami zamiast sekund.

| Świat | Poziom 1 | Poziom 20 | Czas całego zestawu 10 umiejętności | Koszt zestawu |
|---|---|---|---|---|
| 1. Nowy Początek | 5 s | 12 min | 8.8 h | 188 dk |
| 2. Las | 42 min | 2.5 h | 11.8 d | 6 135 dk |
| 3. Tundra | 1.5 h | 3.8 h | 20.6 d | 10 680 dk |
| 4. Pustynia | 2.3 h | 5.2 h | 29.6 d | 15 334 dk |
| 5. Dżungla | 3.1 h | 6.5 h | 38.4 d | 19 882 dk |

Zestaw jest wymagany do awansów (stopniowo) i do przejścia dalej, więc to część głównej ścieżki, a nie dodatek.

### Profile graczy w symulacji

| Profil | Logowania (godziny) | Założenia |
|---|---|---|
| aktywny | 8, 11, 14, 17, 20, 22:30 | 6 wejść dziennie |
| zwykły | 8:30, 13, 20:30 | 3 wejścia dziennie |
| okazjonalny | 19:30 | 1 wejście dziennie |
| mieszany | 60 dni aktywny, potem zwykły | profil docelowy do kalibracji tempa |

Gracz zawsze uzupełnia kolejkę: najpierw tym, czego wymaga następny awans (najkrótsze szkolenie), a gdy wymagania są spełnione, najkrótszymi dostępnymi szkoleniami (podstawowe i odblokowane światy). Przy każdej wizycie rozpoczyna dostępną pracę.

Uruchomienie: `python scripts/simulate_economy.py --profile mieszany --days 365 --markdown` (profile: `aktywny`, `zwykly`, `okazjonalny`, `mieszany`; `--bez-bonusu` wyłącza bonus Handlu; `--sql` wypisuje migrację 016). Kolumna „Zarobek” to wypłaty rozliczone danego dnia (dni bez zakończonej pracy pokazują 0).

### Tempo: kiedy gracz wchodzi do kolejnych światów

| Profil | Świat 2 | Świat 3 | Świat 4 | Świat 5 | Koniec (awans 49) |
|---|---:|---:|---:|---:|---:|
| mieszany (cel) | dzień 33 | dzień 86 | dzień 163 | dzień 263 | dzień 360 |
| aktywny cały rok | dzień 33 | dzień 84 | dzień 158 | dzień 254 | dzień 349 |
| zwykły | dzień 54 | dzień 113 | dzień 190 | dzień 290 | dzień 387 |
| okazjonalny | dzień 144 | dzień 251 | dzień 369 | dzień 498 | dzień 618 |

Tempo jest ograniczone czasem kolejki (3 miejsca), więc różnica między aktywnym a zwykłym graczem jest niewielka; okazjonalny (1 wejście dziennie) potrzebuje blisko dwóch lat.

### Pierwsze 30 dni: aktywny

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 2–2 | 0 | 18 | 0% | 5 s | 451 | 1 | 453 |
| 2 | 5–6 | 0 | 18 | 0% | 7 s | 707 | 1 | 1159 |
| 3 | 11–12 | 0 | 18 | 0% | 16 s | 733 | 2 | 1890 |
| 5 | 19–24 | 1 | 18 | 2% | 1 min | 801 | 10 | 3446 |
| 7 | 26–30 | 1 | 18 | 4% | 3 min | 823 | 23 | 5054 |
| 10 | 32–41 | 3 | 18 | 21% | 16 min | 870 | 114 | 7446 |
| 14 | 42–50 | 4 | 14 | 50% | 1.1 h | 902 | 333 | 10307 |
| 18 | 47–59 | 6 | 13 | 70% | 52 min | 929 | 276 | 12867 |
| 21 | 51–64 | 7 | 9 | 62% | 1.1 h | 943 | 256 | 14697 |
| 25 | 56–68 | 8 | 14 | 34% | 1.1 h | 954 | 319 | 17208 |
| 30 | 61–77 | 9 | 6 | 99% | 12.8 h | 976 | 5557 | 14877 |

### Pierwsze 30 dni: zwykły

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 1–1 | 0 | 9 | 0% | 5 s | 373 | 0 | 375 |
| 2 | 2–2 | 0 | 9 | 0% | 5 s | 658 | 0 | 1032 |
| 3 | 2–3 | 0 | 9 | 0% | 6 s | 660 | 0 | 1692 |
| 5 | 8–9 | 0 | 9 | 0% | 12 s | 681 | 1 | 3039 |
| 7 | 14–15 | 0 | 9 | 0% | 31 s | 716 | 2 | 4450 |
| 10 | 19–24 | 1 | 9 | 1% | 1 min | 758 | 6 | 6675 |
| 14 | 26–30 | 1 | 9 | 3% | 4 min | 785 | 15 | 9717 |
| 18 | 32–34 | 2 | 9 | 5% | 7 min | 795 | 24 | 12833 |
| 21 | 32–41 | 3 | 9 | 0% | 27 s | 816 | 1 | 15166 |
| 25 | 38–48 | 4 | 9 | 13% | 20 min | 840 | 82 | 18133 |
| 30 | 42–53 | 5 | 9 | 1% | 1 min | 854 | 4 | 21773 |

### Pierwsze 30 dni: okazjonalny

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 1–1 | 0 | 3 | 0% | 5 s | 0 | 0 | 2 |
| 2 | 1–1 | 0 | 3 | 0% | 5 s | 900 | 0 | 902 |
| 3 | 1–1 | 0 | 3 | 0% | 5 s | 0 | 0 | 902 |
| 5 | 1–2 | 0 | 3 | 0% | 5 s | 0 | 0 | 1810 |
| 7 | 2–2 | 0 | 3 | 0% | 6 s | 0 | 0 | 2719 |
| 10 | 3–4 | 0 | 3 | 0% | 7 s | 918 | 0 | 4554 |
| 14 | 7–8 | 0 | 3 | 0% | 12 s | 943 | 0 | 6423 |
| 18 | 11–12 | 0 | 3 | 0% | 23 s | 975 | 0 | 8357 |
| 21 | 14–15 | 0 | 3 | 0% | 36 s | 0 | 1 | 9346 |
| 25 | 18–19 | 0 | 3 | 0% | 1 min | 0 | 1 | 11368 |
| 30 | 19–24 | 1 | 3 | 0% | 1 min | 1048 | 2 | 14488 |

### Cały rok: profil mieszany (60 dni aktywny, potem zwykły)

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 30 | 61–77 | 9 | 6 | 99% | 12.8 h | 976 | 5557 | 14877 |
| 60 | 81–99 | 16 | 3 | 100% | 7.9 h | 1030 | 512 | 31476 |
| 90 | 93–113 | 20 | 2 | 100% | 6.0 h | 993 | 260 | 38888 |
| 120 | 101–125 | 25 | 5 | 82% | 6.6 h | 1007 | 715 | 54585 |
| 180 | 114–143 | 32 | 7 | 77% | 2.8 h | 1032 | 418 | 73662 |
| 240 | 127–157 | 38 | 1 | 100% | 5.0 h | 1054 | 107 | 104958 |
| 300 | 135–170 | 43 | 3 | 100% | 18.2 h | 1065 | 1432 | 119879 |
| 365 | 145–180 | 49 | 1 | 100% | 17.5 h | 1078 | 378 | 154913 |

- Szkolenia trwają kilka sekund w pierwszych dniach, minuty około dnia 7, godziny od dnia ~14. W dniu 30 w „Wydatkach” widać wydarzenie: awans do świata 2 kosztuje ok. 5 tys. dk.
- Przychód roczny ok. 373 tys. dk (ok. 1,0 tys. dk dziennie). Saldo rośnie do ok. 155-175 tys. dk, mimo że każde przejście do nowego świata pochłania 5-22 tys. dk. To zapas na przyszłe wydatki (sklep, wyposażenie). Nigdzie nie ma kwot w milionach, a w żadnym profilu kolejka nie stoi z braku dukatów.

### Bonus Handlu do wypłat z prac

Poziom **Handlu** zwiększa wypłatę ze wszystkich prac: `bonus = 120% * L / (L + 120)`, sufit +120% (wypłata nigdy nie przekroczy 2,2 × bazowej).

| Poziom Handlu | 10 | 30 | 50 | 80 | 100 | 150 | 200 |
|---|---:|---:|---:|---:|---:|---:|---:|
| Bonus do wypłat | +9% | +24% | +35% | +48% | +55% | +67% | +75% |

Wypłata jest zapisywana przy starcie pracy (`job_runs.reward_snapshot_cents`), więc późniejszy awans Handlu nie zmienia trwającej pracy.

### Wnioski

- Pierwszy miesiąc: kilkanaście krótkich szkoleń dziennie u aktywnego gracza do ok. dnia 10, brak niedoborów dukatów w żadnym profilu.
- Rok na 5 światów jest ograniczony czasem kolejki, nie dukatami (kwadratowy koszt po poziomie 50).
- Każdy świat to nowy zestaw 10 umiejętności o trudności dostosowanej do etapu gry, wymagany do dalszych awansów.
- Awans do nowego świata to wydarzenie: 2,4 do 10,4 dnia i 5-22 tys. dk.
- Do ustalenia później: efekty umiejętności światów, ujścia dla nadwyżki dukatów, dodatkowe miejsca w kolejce za rangi (poprawiłyby tempo okazjonalnych graczy).
