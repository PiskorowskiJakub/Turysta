# Dwa Dukaty

Monorepo:

- `frontend/` React + TypeScript + Vite
- `backend/` PHP 8.3 + Apache, REST pod `/api`
- `scripts/` backup.sh, restore.sh, simulate_economy.py (symulacja ekonomii), simulate_squads.py (symulacja ekonomii oddziałów), simulate_shelter.py (tempo rozbudowy schroniska), simulate_squad_skills.py (umiejętności oddziałowe), dev-grant-squad.sh (lokalnie: `make squad-grant SQUAD=1 EMBER=5000 STAMPS=50 COINS=20000` dosypuje oddziałowi składniki schroniska)
- `docker-compose.yml` wspolny stos

## Uruchomienie calosci

```
cp .env.example .env   # uzupelnij hasla
docker compose build
docker compose up -d
docker compose exec backend php bin/migrate.php
```

- Administrator (dostęp do panelu `/admin`: przegląd statystyk, zarządzanie graczami, ogłoszenia, ciekawostki, logi): `docker compose exec backend php bin/set_role.php adres@email.pl admin` (odebranie roli: `... adres@email.pl player`)
- Sprzątanie kont po testach integracyjnych (`@example.com`; podgląd, a z `--yes` usunięcie): `docker compose exec backend php bin/cleanup_test_users.php [--yes]`
- Usunięcie jednego konta z danymi (np. własnego, administratora też): `docker compose exec backend php bin/delete_user.php adres@email [--yes]`
- Konta graczy bez potwierdzonego adresu e-mail są usuwane po 30 dniach (scheduler robi to co godzinę). Podgląd i ręczne uruchomienie: `docker compose exec backend php bin/cleanup_unverified.php [--yes]` (w produkcji `turysta-api` zamiast `backend`).
- Znajomi: zakładka Profil, Znajomi (zaproszenia po nazwie postaci albo z profilu gracza). Zaakceptowany znajomy widzi status online (sesja aktywna w ciągu 5 minut), dukaty i punkty. Tabela `friendships` (migracja 027), logika w `backend/src/Social/FriendService.php`.
- Porównanie kont: przycisk „Porównaj” przy znajomym (`/profil/znajomi/porownaj/:id`, API `GET /v1/friends/{id}/compare`, tylko dla zaakceptowanych znajomych). Lepszy wynik jest na zielono; umiejętności światów, do których gracz jeszcze nie dotarł, są ukryte (bez spoilerów).
- Ranking znajomych: zakładka „Tylko znajomi” w rankingu (`/ranking?widok=znajomi`, API `GET /v1/ranking/friends?period=week|month|all`). Punkty z okresu liczone z księgi `ledger_entries` (tydzień od poniedziałku, miesiąc od 1. dnia, wg strefy Europe/Warsaw; bez korekt admina i przeliczenia punktów).
- Prezenty dla znajomych: `POST /v1/friends/{id}/gift`, logika w `backend/src/Social/GiftService.php`, tabela `friend_gifts` (migracja 030). Kwota jak dzień 4 codziennej nagrody (rośnie z postępem nadawcy), nadawca dostaje bonus 20%. Limity: 1 prezent dziennie dla osoby, 3 wysłane i 5 odebranych dziennie, 24 godziny od zostania znajomymi.
- Aktywność znajomych na stronie głównej: tabela `activity_events` (nowy świat, odznaka od złotej wzwyż), `GET /v1/friends/activity`, `backend/src/Social/ActivityFeed.php`. Przełącznik „Pokazuj moją aktywność znajomym” (Edycja profilu, Prywatność, `users.share_activity`) działa w obie strony.
- Aktualizacje gry (dziennik zmian) są pod `/aktualizacje` i dostępne bez logowania. Administrator dodaje je w panelu: Treści, Aktualizacje. Ogłoszenie może wskazać aktualizację i wtedy pokazuje link „Szczegóły w aktualizacji”. Ogłoszenia mają zawierać tylko ogólnik.
- Zgłoszenia błędów od graczy (formularz „Zgłoś błąd” w stopce) są w panelu admina, karta „Zgłoszenia błędów”. Regulamin i polityka prywatności: `/regulamin` i `/polityka-prywatnosci` (treść w `frontend/src/locales/pl.json`, blok `legal`).
- Gra: http://localhost (Traefik)
- Health: http://localhost/api/health
- phpMyAdmin: http://127.0.0.1:8081
- Mailpit: http://127.0.0.1:8025
- Frontend z hot reload (Vite): `docker compose --profile hmr up -d frontend-dev` -> http://127.0.0.1:5173

Nigdy nie uruchamiaj `docker compose down -v` na produkcji (kasuje baze).
TLS (Let's Encrypt) dojdzie przy wdrozeniu na VPS.

## Wdrożenie

Na serwerze za istniejącym Traefikiem: [DEPLOY.md](DEPLOY.md) (`docker-compose.prod.yml`, `.env.prod.example`).

## Strona główna i SEO

Build frontendu (`npm run build`, w Dockerze robi to obraz `turysta-web`) poza aplikacją przygotowuje `dist/landing.html`: wyrenderowaną na sztywno stronę główną dla niezalogowanych (treść, tytuł, opis, Open Graph, JSON-LD). nginx podaje ją pod adresem `/`, a pozostałe adresy dostają zwykłą powłokę aplikacji `index.html` z nagłówkiem `X-Robots-Tag: noindex`. Teksty SEO są w `frontend/src/locales/pl.json` (`seo.*`), adres domeny w `frontend/scripts/prerender.mjs` (zmienna `SITE_URL`, domyślnie `https://dwadukaty.pl`) oraz w `public/robots.txt` i `public/sitemap.xml`. Obrazek do udostępniania to `frontend/public/img/og.png` (1200x630).

## Panel administratora

Dostępny pod `/admin` tylko dla roli `admin` (serwer sprawdza rolę przy każdym żądaniu). Karty i podkarty:

- **Przegląd** – konta (razem, aktywne, zawieszone, zablokowane, niezweryfikowane), aktywność (online, DAU/WAU/MAU, logowania), ekonomia (dukaty, punkty, bilety w obiegu, prace i treningi), rozkład graczy po światach, wykresy rejestracji i logowań z 30 dni.
- **Gracze** – lista z wyszukiwaniem (nazwa, e-mail, ID), filtrami statusu i roli oraz sortowaniem; karta gracza: dane i edycja (nazwa postaci, e-mail, rola, płeć, awatar), blokada, zawieszenie na N dni, wylogowanie ze wszystkich urządzeń, portfel z korektą (wpis w księdze), logowania, księga operacji, historia zmian.
- **Treści** – ogłoszenia oraz ciekawostki dnia.
- **Logi** – logowania wszystkich graczy (z IP i przeglądarką) oraz dziennik działań administratorów.

Każda zmiana wykonana w panelu trafia do dziennika `admin_audit` (kto, co, kogo dotyczyło, szczegóły, IP). Zablokowane lub zawieszone konto traci sesje i nie może się zalogować. Nie można zablokować ani zawiesić administratora ani własnego konta.

## Ekonomia i symulacja gry (30 dni i rok)

Cele:

1. Przez pierwszy miesiąc gracz ma codziennie co rozwijać (dużo krótkich szkoleń) i dość dukatów, żeby kolejka (1 aktywne + 3 oczekujące) nie stała pusta.
2. Ukończenie wszystkich 49 awansów (5 światów) ma zająć **ok. roku** graczowi, który przez 2 miesiące gra aktywnie, a potem jak zwykły gracz.
3. Awans wymaga także rozwoju umiejętności danego świata, a przejście do nowego świata to **wydarzenie** (długie i drogie).
4. W nowym świecie umiejętności mają trudność **proporcjonalną do etapu gry**: dają powiew świeżości, ale doświadczony gracz nie zaczyna od sekund.
5. Brak hiperinflacji: w pierwszym roku nie ma kwot w milionach.

### Parametry (migracje `011`, `015`, `016`)

- Koszt poziomu `n`: `floor(3 * 1,16^(n-1))` groszy, czas `round(5 * 1,16^(n-1))` sekund. Koszt rośnie tak samo szybko jak czas (ok. 0,6 grosza na sekundę), więc pieniądze nie są ścianką. Od poziomu 50 koszt i czas rosną **kwadratowo**: `(n/50)^2`.
- **Wymagania awansu `g` (1..49), podstawowe umiejętności:** Kondycja `3 + 177 * (g/49)^0,55` (Orientacja 0,95 ×, Handel 0,8 × tej wartości), czyli ok. 24 na pierwszym awansie i 180 na ostatnim.
- **Wymagania awansu, umiejętności świata:** w obrębie świata rozdział `n` wymaga **każdej z 6 umiejętności świata** na poziomie `ceil(33 * (n-1) / 10)` (rozdział 2: poziom 4, rozdział 10: poziom 30). **Przejście do nowego świata wymaga wszystkich 6 umiejętności obecnego świata na maksymalnym poziomie (33).** Wymaganie jest liczone przez serwer (`ChapterService::worldRequirement`) i widoczne w zakładce Awans.
- **Koszt i czas zwykłego awansu:** `max(50, 2,5 * koszt(f))` oraz `max(30, 2 * czas(f))`, gdzie `f` to wymagany poziom Kondycji.
- **Awans do nowego świata (wydarzenie):** **20× koszt i 6× czas** zwykłego awansu.

| Przejście | Zwykły awans w tym świecie (czas / koszt) | Awans do nowego świata (czas / koszt) |
|---|---|---|
| 1 → 2 (Las) | 8.5 h / 230 dk | **2.4 d / 5 122 dk** |
| 2 → 3 (Tundra) | 18.7 h / 504 dk | **4.9 d / 10 645 dk** |
| 3 → 4 (Pustynia) | 1.2 d / 799 dk | **7.6 d / 16 454 dk** |
| 4 → 5 (Dżungla) | 1.7 d / 1 092 dk | **10.4 d / 22 396 dk** |

- Start: 2 dk premii. Bazowe wypłaty prac: targ 8 h (247 dk), hostel 12 h (420 dk), wyprawa 24 h (900 dk), z bonusem Handlu (niżej).

### Umiejętności światów (migracje `014`, `017`)

Każdy świat ma **6 własnych umiejętności** (razem 30), dobranych do klimatu świata, np. Las: Rozpoznawanie grzybów, Tropienie zwierząt, Budowa szałasu; Tundra: Ubiór warstwowy, Rakiety śnieżne, Zaprzęg psi; Pustynia: Gospodarka wodą, Jazda na wielbłądzie, Nawigacja po gwiazdach; Dżungla: Torowanie drogi maczetą, Wspinaczka po lianach, Spływ kajakiem. Zakładki światów, do których gracz jeszcze nie dotarł, są zablokowane (`skill_locked`). Maksymalnie **33 poziomy** (6 × 33 ≈ 10 × 20 z poprzedniej wersji, więc łączny czas i koszt zestawu się nie zmieniły).

**Trudność zależy od etapu gry.** Poziom 1 trwa 15% czasu szkolenia podstawowej umiejętności na poziomie, na którym gracz wchodzi do świata, a wzrost na poziom dobrany tak, że cały zestaw wymaga tyle pracy, ile zestaw 10 × 20 poziomów (ostatni poziom ok. 25% czasu podstawowej na końcu świata). Koszt jest proporcjonalny do czasu. Nowy gracz w świecie 1 zaczyna od sekund; doświadczony wchodzi w świat 5 z godzinami zamiast sekund.

| Świat | Poziom 1 | Poziom 33 | Czas całego zestawu 6 umiejętności | Koszt zestawu |
|---|---|---|---|---|
| 1. Nowy Początek | 5 s | 12 min | 8.7 h | 188 dk |
| 2. Las | 42 min | 2.5 h | 11.8 d | 6 139 dk |
| 3. Tundra | 1.5 h | 3.9 h | 20.6 d | 10 689 dk |
| 4. Pustynia | 2.3 h | 5.3 h | 29.6 d | 15 346 dk |
| 5. Dżungla | 3.1 h | 6.6 h | 38.3 d | 19 874 dk |

Zestaw jest wymagany do awansów (stopniowo) i do przejścia dalej, więc to część głównej ścieżki, a nie dodatek.

### Tabela awansów

Pełna tabela wymagań, czasu i kosztu awansów jest w [docs/README.md](docs/README.md#tabela-awansów-wymagania-czas-i-koszt).

### Profile graczy w symulacji

| Profil | Logowania (godziny) | Założenia |
|---|---|---|
| aktywny | 8, 11, 14, 17, 20, 22:30 | 6 wejść dziennie |
| zwykły | 8:30, 13, 20:30 | 3 wejścia dziennie |
| okazjonalny | 19:30 | 1 wejście dziennie |
| mieszany | 60 dni aktywny, potem zwykły | profil docelowy do kalibracji tempa |

Gracz zawsze uzupełnia kolejkę: najpierw tym, czego wymaga następny awans (najkrótsze szkolenie), a gdy wymagania są spełnione, najkrótszymi dostępnymi szkoleniami (podstawowe i odblokowane światy). Przy każdej wizycie rozpoczyna dostępną pracę.

Uruchomienie: `python scripts/simulate_economy.py --profile mieszany --days 365 --markdown` (profile: `aktywny`, `zwykly`, `okazjonalny`, `mieszany`; `--bez-bonusu` wyłącza bonus Handlu; `--sql` wypisuje migrację 017, `--sql-chapters` UPDATE-y awansów). Kolumna „Zarobek” to wypłaty rozliczone danego dnia (dni bez zakończonej pracy pokazują 0).

### Tempo: kiedy gracz wchodzi do kolejnych światów

| Profil | Świat 2 | Świat 3 | Świat 4 | Świat 5 | Koniec (awans 49) |
|---|---:|---:|---:|---:|---:|
| mieszany (cel) | dzień 33 | dzień 85 | dzień 162 | dzień 261 | dzień 357 |
| aktywny cały rok | dzień 33 | dzień 84 | dzień 157 | dzień 254 | dzień 348 |
| zwykły | dzień 53 | dzień 112 | dzień 190 | dzień 289 | dzień 385 |
| okazjonalny | dzień 144 | dzień 250 | dzień 368 | dzień 498 | dzień 619 |

Tempo jest ograniczone czasem kolejki (4 miejsca), więc różnica między aktywnym a zwykłym graczem jest niewielka; okazjonalny (1 wejście dziennie) potrzebuje blisko dwóch lat.

### Pierwsze 30 dni: aktywny

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 2–2 | 0 | 18 | 0% | 5 s | 452 | 1 | 453 |
| 2 | 4–4 | 0 | 18 | 0% | 7 s | 712 | 1 | 1165 |
| 3 | 10–10 | 0 | 18 | 0% | 13 s | 729 | 1 | 1893 |
| 5 | 19–24 | 0 | 18 | 2% | 1 min | 797 | 9 | 3441 |
| 7 | 26–29 | 1 | 18 | 4% | 2 min | 819 | 19 | 5046 |
| 10 | 32–41 | 3 | 18 | 21% | 16 min | 870 | 114 | 7434 |
| 14 | 42–50 | 4 | 15 | 48% | 1.0 h | 901 | 333 | 10289 |
| 18 | 47–59 | 6 | 15 | 62% | 35 min | 929 | 220 | 12848 |
| 21 | 51–64 | 7 | 9 | 74% | 1.1 h | 943 | 257 | 14673 |
| 25 | 57–68 | 8 | 12 | 45% | 1.5 h | 955 | 391 | 17110 |
| 30 | 62–77 | 9 | 6 | 100% | 12.6 h | 976 | 5530 | 14794 |

### Pierwsze 30 dni: zwykły

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 1–1 | 0 | 9 | 0% | 5 s | 373 | 0 | 375 |
| 2 | 2–2 | 0 | 9 | 0% | 6 s | 660 | 0 | 1034 |
| 3 | 3–3 | 0 | 9 | 0% | 7 s | 667 | 0 | 1701 |
| 5 | 7–7 | 0 | 9 | 0% | 10 s | 679 | 1 | 3052 |
| 7 | 13–13 | 0 | 9 | 0% | 26 s | 710 | 1 | 4452 |
| 10 | 19–24 | 0 | 9 | 1% | 1 min | 755 | 6 | 6665 |
| 14 | 26–29 | 1 | 9 | 2% | 3 min | 781 | 13 | 9701 |
| 18 | 32–34 | 2 | 9 | 5% | 7 min | 795 | 24 | 12813 |
| 21 | 32–41 | 3 | 9 | 0% | 28 s | 816 | 1 | 15146 |
| 25 | 38–48 | 4 | 9 | 24% | 38 min | 840 | 142 | 18104 |
| 30 | 42–53 | 5 | 9 | 1% | 1 min | 854 | 5 | 21738 |

### Pierwsze 30 dni: okazjonalny

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 1–1 | 0 | 3 | 0% | 5 s | 0 | 0 | 2 |
| 2 | 1–1 | 0 | 3 | 0% | 5 s | 900 | 0 | 902 |
| 3 | 1–1 | 0 | 3 | 0% | 5 s | 0 | 0 | 902 |
| 5 | 2–2 | 0 | 3 | 0% | 6 s | 0 | 0 | 1810 |
| 7 | 3–3 | 0 | 3 | 0% | 7 s | 0 | 0 | 2728 |
| 10 | 4–4 | 0 | 3 | 0% | 8 s | 926 | 0 | 4572 |
| 14 | 6–6 | 0 | 3 | 0% | 11 s | 935 | 0 | 6441 |
| 18 | 10–10 | 0 | 3 | 0% | 19 s | 968 | 0 | 8359 |
| 21 | 13–13 | 0 | 3 | 0% | 30 s | 0 | 1 | 9340 |
| 25 | 17–17 | 0 | 3 | 0% | 54 s | 0 | 1 | 11348 |
| 30 | 19–24 | 0 | 3 | 0% | 2 min | 1048 | 2 | 14455 |

### Cały rok: profil mieszany (60 dni aktywny, potem zwykły)

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 30 | 62–77 | 9 | 6 | 100% | 12.6 h | 976 | 5530 | 14794 |
| 60 | 81–99 | 16 | 4 | 100% | 7.5 h | 1028 | 651 | 31427 |
| 90 | 93–113 | 20 | 2 | 100% | 1.7 h | 993 | 72 | 38706 |
| 120 | 102–125 | 25 | 3 | 100% | 12.1 h | 1008 | 785 | 53740 |
| 180 | 114–143 | 32 | 7 | 84% | 5.1 h | 1032 | 774 | 72758 |
| 240 | 127–159 | 38 | 3 | 100% | 16.8 h | 1054 | 1309 | 103390 |
| 300 | 135–170 | 43 | 0 | 100% | 0 s | 1065 | 0 | 119507 |
| 365 | 149–180 | 49 | 2 | 100% | 18.4 h | 1082 | 793 | 153453 |

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
- Każdy świat to nowy zestaw 6 umiejętności o trudności dostosowanej do etapu gry, wymagany do dalszych awansów.
- Awans do nowego świata to wydarzenie: 2,4 do 10,4 dnia i 5-22 tys. dk.
- Do ustalenia później: efekty umiejętności światów, ujścia dla nadwyżki dukatów, dodatkowe miejsca w kolejce za rangi (poprawiłyby tempo okazjonalnych graczy).

## Symulacja schroniska oddziału

Skrypt `scripts/simulate_shelter.py` (`python scripts/simulate_shelter.py --markdown`) liczy, jak szybko oddział dojdzie do kolejnych poziomów pomieszczeń. Jest to szybki model, nie pełna symulacja Monte Carlo (tę dla pozostałych mechanik oddziałów daje `scripts/simulate_squads.py`, liczony wg starszych zasad: żar od 100 i koszt ×1,5 na poziom).

### Zasady zastosowane w modelu

- Koszt poziomu n pomieszczenia: żar 240 × n^1,2, pieczątki 6 × n^1,1, dukaty 600 × n^1,2 dk (Izba pamięci 60%). Poziomów nie ma końca. Budowa poziomu n trwa 12 godzin × n.
- Dzienny limit wpłat na osobę to średnia cena dukatowa kolejnych poziomów / 60 (minimum 5 dk): 9 dk na starcie, ok. 79 dk przy wszystkich pomieszczeniach na poziomie 5.
- Oddział rozbudowuje po kolei pomieszczenie o najniższym poziomie. Składniki zbierają się równolegle, więc czas jednej rozbudowy to czas zebrania najwolniejszego z nich.
- Tempo zbierania: żar dziennie (2 osoby: 12, bo ognisko pali się tylko gdy obie są aktywne; 4 osoby: 19; 8 osób: 20; 15 osób: 17; 30 osób: 15), pieczątki tygodniowo (3 / 4 / 4,5 / 4 / 3,8) i liczba osób wpłacających codziennie do limitu (1,2 / 2 / 3 / 4,5 / 9).

### Wyniki: dzień, w którym wszystkie pięć pomieszczeń osiąga poziom

| Oddział | Poziom 1 | Poziom 2 | Poziom 3 | Poziom 4 | Poziom 5 | Wąskie gardło (żar/pieczątki/dukaty) |
| --- | --- | --- | --- | --- | --- | --- |
| 2 osoby | 177 dni (6 mies.) | 399 dni (13 mies.) | 743 dni (2,0 roku) | 1229 dni (3,4 roku) | 1864 dni (5,1 roku) | 18/0/7 |
| 4 osoby | 106 dni (4 mies.) | 244 dni (8 mies.) | 461 dni (15 mies.) | 768 dni (2,1 roku) | 1168 dni (3,2 roku) | 19/0/6 |
| 8 osób | 72 dni (2,4 mies.) | 199 dni (7 mies.) | 405 dni (14 mies.) | 697 dni (23 mies.) | 1078 dni (3,0 roku) | 22/0/3 |
| 15 osób | 66 dni (2,2 mies.) | 215 dni (7 mies.) | 457 dni (15 mies.) | 800 dni (2,2 roku) | 1248 dni (3,4 roku) | 24/0/1 |
| 30 osób | 74 dni (2,5 mies.) | 243 dni (8 mies.) | 518 dni (17 mies.) | 906 dni (2,5 roku) | 1414 dni (3,9 roku) | 25/0/0 |

### Wnioski

- Prawie zawsze wąskim gardłem jest żar, potem pieczątki (zbierane równolegle, więc nigdy nie wyznaczają tempa). Dukaty ograniczają tempo tylko w najmniejszych oddziałach i na pierwszych poziomach.
- Od 4 osób w górę tempo jest zbliżone: żar nie zależy od liczby członków, a duże oddziały nadrabiają wpłatami.
- Oddział 2-osobowy jest wyraźnie wolniejszy (poziom 1 po ok. 6 miesiącach), bo ognisko często nie pali się, a dwie osoby wpłacają niewiele dukatów.
- Do ustalenia: łagodniejszy start dla oddziałów 2–3-osobowych (wyższy limit wpłat albo niższy koszt dukatowy poziomów 1–2) oraz ewentualne spowolnienie wzrostu kosztów (wykładnik 1,1 zamiast 1,2).

## Wiki gry na stronie

Podstrona `/wiki` jest renderowana bezpośrednio z `docs/README.md` (import `?raw` w `frontend/src/pages/WikiPage.tsx`), więc zasady gry opisujemy tylko w jednym miejscu. Dlatego obraz frontendu jest budowany z katalogu głównego repozytorium (`frontend/Dockerfile`, `frontend/Dockerfile.dockerignore`).

## Symulacja umiejętności oddziałowych (etap 4)

Skrypt `scripts/simulate_squad_skills.py` (`python scripts/simulate_squad_skills.py --runs 60 --days 1825 --markdown`) to symulacja Monte Carlo na zasadach z etapów 1-3 (ognisko od 10 żaru, szlaki, schronisko bez limitu poziomów) razem z umiejętnościami oddziałowymi. Starszy `simulate_squads.py` liczy jeszcze wg poprzedniego modelu.

### Zasady zastosowane w modelu

- Umiejętności: Rozpalanie, Przewodnictwo, Kartografia, Zaopatrzenie, Pierwsza pomoc. Osobny slot treningowy, bez maksymalnego poziomu. Poziom n trwa 2 h × n i kosztuje 6,48 dk × n (15% ceny zwykłej umiejętności, 3,24 dk/h).
- Efekt = maksimum × L / (L + 40), z średniej poziomów aktywnych członków (≥ 3 aktywne dni z 14), od 3 aktywnych członków. Maksima: Rozpalanie 30% żaru, Przewodnictwo 15% postępu szlaku, Kartografia 12% pieczątek, Zaopatrzenie 15% zniżki dukatów w koszcie pomieszczeń (podnosi też dzienny limit wpłat).
- Mapownia (do 25% pieczątek) i Kartografia mnożą się, więc łącznie nie więcej niż ×1,4.
- Spiżarnia: zniżka kosztu dukatowego szkoleń oddziałowych, maksimum 15% × L / (L + 10), bez limitu poziomu.
- Izba chorych: poziom 3 daje drugą tarczę ogniska, poziom 15 trzecią; kolejne poziomy skracają odnowienie tarczy (14 dni → min. 7 dni razem z Pierwszą pomocą).
- Nowe trasy: czwarta trudność odblokowuje się przy średnim poziomie Kartografii 25, piąta przy 60.

### Wyniki

#### Średni poziom umiejętności oddziałowych (aktywni członkowie) i efekty

| Oddział | Dzień | Śr. poziom | Rozpalanie (żar) | Przewodnictwo (szlak) | Kartografia (pieczątki) | Zaopatrzenie (dk) |
|---|---|---|---|---|---|---|
| garstka (4, zgrana) | 180 | 26.4 | +11,9% | +6,0% | +4,8% | +5,9% |
| garstka (4, zgrana) | 365 | 36.7 | +14,3% | +7,1% | +5,7% | +7,1% |
| garstka (4, zgrana) | 730 | 49.1 | +16,5% | +8,2% | +6,6% | +8,2% |
| garstka (4, zgrana) | 1825 | 61.6 | +18,0% | +9,0% | +7,2% | +8,9% |
| mały zgrany (8) | 180 | 25.7 | +11,7% | +5,9% | +4,7% | +5,8% |
| mały zgrany (8) | 365 | 35.0 | +14,0% | +6,9% | +5,5% | +6,9% |
| mały zgrany (8) | 730 | 45.7 | +15,9% | +8,0% | +6,4% | +7,9% |
| mały zgrany (8) | 1825 | 62.2 | +18,2% | +9,1% | +7,3% | +9,1% |
| typowy (15) | 180 | 22.5 | +10,8% | +5,4% | +4,3% | +5,3% |
| typowy (15) | 365 | 31.0 | +13,1% | +6,5% | +5,2% | +6,5% |
| typowy (15) | 730 | 39.8 | +14,9% | +7,5% | +5,9% | +7,4% |
| typowy (15) | 1825 | 52.2 | +16,9% | +8,4% | +6,7% | +8,4% |
| duży typowy (30) | 180 | 21.9 | +10,6% | +5,3% | +4,2% | +5,2% |
| duży typowy (30) | 365 | 30.3 | +12,9% | +6,4% | +5,1% | +6,4% |
| duży typowy (30) | 730 | 39.3 | +14,9% | +7,4% | +5,9% | +7,4% |
| duży typowy (30) | 1825 | 52.7 | +17,0% | +8,5% | +6,8% | +8,5% |
| duży senny (30) | 180 | 18.5 | +9,5% | +4,7% | +3,8% | +4,7% |
| duży senny (30) | 365 | 25.2 | +11,6% | +5,8% | +4,6% | +5,7% |
| duży senny (30) | 730 | 32.1 | +13,3% | +6,6% | +5,3% | +6,6% |
| duży senny (30) | 1825 | 42.7 | +15,4% | +7,7% | +6,1% | +7,7% |

#### Schronisko z umiejętnościami: suma poziomów pomieszczeń (5 pomieszczeń) i bonusy

| Oddział | Dzień | Suma poziomów | Poz. Spiżarni | Zniżka szkoleń (Spiżarnia) | Poz. Izby chorych | Ładunki tarczy | Odnowienie tarczy (dni) |
|---|---|---|---|---|---|---|---|
| garstka (4, zgrana) | 365 | 11.2 | 2.3 | 2,8% | 2.0 | 1.0 | 10.0 |
| garstka (4, zgrana) | 1825 | 32.0 | 7.0 | 6,2% | 6.0 | 2.0 | 8.6 |
| mały zgrany (8) | 365 | 13.0 | 3.0 | 3,5% | 3.0 | 2.0 | 9.8 |
| mały zgrany (8) | 1825 | 32.0 | 7.0 | 6,2% | 6.0 | 2.0 | 8.6 |
| typowy (15) | 365 | 12.7 | 3.0 | 3,5% | 2.6 | 1.6 | 10.0 |
| typowy (15) | 1825 | 31.4 | 6.4 | 5,9% | 6.0 | 2.0 | 8.8 |
| duży typowy (30) | 365 | 12.4 | 3.0 | 3,5% | 2.4 | 1.4 | 10.1 |
| duży typowy (30) | 1825 | 31.1 | 6.2 | 5,8% | 6.0 | 2.0 | 8.7 |
| duży senny (30) | 365 | 10.5 | 2.1 | 2,6% | 2.0 | 1.0 | 10.5 |
| duży senny (30) | 1825 | 27.8 | 6.0 | 5,6% | 5.7 | 2.0 | 9.0 |

#### Tempo schroniska: suma poziomów 5 pomieszczeń bez umiejętności i z nimi, oraz odblokowanie nowych trudności (Kartografia, śr. poziom 25 i 60)

| Oddział | Dzień | Suma poziomów bez | Suma poziomów z | Trudność 4 (dzień) | Trudność 5 (dzień) |
|---|---|---|---|---|---|
| garstka (4, zgrana) | 365 | 10.2 | 11.2 | 162 | 1156 |
| garstka (4, zgrana) | 730 | 18.4 | 20.0 | 162 | 1156 |
| garstka (4, zgrana) | 1825 | 30.0 | 32.0 | 162 | 1156 |
| mały zgrany (8) | 365 | 12.7 | 13.0 | 172 | 1303 |
| mały zgrany (8) | 730 | 18.6 | 20.0 | 172 | 1303 |
| mały zgrany (8) | 1825 | 30.0 | 32.0 | 172 | 1303 |
| typowy (15) | 365 | 12.0 | 12.7 | 224 | 1566 |
| typowy (15) | 730 | 17.8 | 19.4 | 224 | 1566 |
| typowy (15) | 1825 | 29.6 | 31.4 | 224 | 1566 |
| duży typowy (30) | 365 | 11.8 | 12.4 | 239 | 1602 |
| duży typowy (30) | 730 | 17.5 | 18.9 | 239 | 1602 |
| duży typowy (30) | 1825 | 29.2 | 31.1 | 239 | 1602 |
| duży senny (30) | 365 | 10.0 | 10.5 | 351 | - |
| duży senny (30) | 730 | 15.4 | 16.2 | 351 | - |
| duży senny (30) | 1825 | 26.0 | 27.8 | 351 | - |

#### Żar i pieczątki w całym okresie (1825 dni): bez umiejętności i z nimi

| Oddział | Żar/dzień bez | Żar/dzień z | Pieczątek/tydz. bez (trasa średnia) | Pieczątek/tydz. z (+Mapownia, +Kartografia) | Uratowane dni tarczą |
|---|---|---|---|---|---|
| garstka (4, zgrana) | 17.6 | 20.4 | 3.68 | 4.53 | 11.9 |
| mały zgrany (8) | 17.8 | 20.5 | 3.71 | 4.54 | 0.1 |
| typowy (15) | 17.0 | 19.5 | 3.89 | 4.50 | 0.5 |
| duży typowy (30) | 16.7 | 19.2 | 3.94 | 4.50 | 0.0 |
| duży senny (30) | 13.6 | 15.4 | 3.41 | 4.12 | 16.4 |

#### Bilans dukatów na członka (dk/dzień, średnia z 1825 dni, przychód gracza ok. 1000 dk/dzień)

| Oddział | Wpłaty (sink) | Szkolenia oddz. (sink) | Szkolenia bez zniżki | Bonus Kuchni (emisja) | Netto sink | Sink / przychód | Emisja / przychód |
|---|---|---|---|---|---|---|---|
| garstka (4, zgrana) | 24.9 | 64.8 | 67.7 | 18.2 | +71.5 | 9,0% | 1,82% |
| mały zgrany (8) | 25.2 | 63.7 | 66.6 | 18.8 | +70.1 | 8,9% | 1,88% |
| typowy (15) | 21.0 | 54.2 | 56.7 | 17.9 | +57.3 | 7,5% | 1,79% |
| duży typowy (30) | 19.9 | 51.7 | 54.0 | 17.2 | +54.4 | 7,2% | 1,72% |
| duży senny (30) | 12.5 | 36.6 | 38.2 | 13.6 | +35.6 | 4,9% | 1,36% |

#### Pojedynczy gracz (osobny slot, rozłożone na 5 umiejętności, bez Spiżarni)

| Regularność | Dzień | Poziom każdej umiejętności | Efekt Rozpalania | Koszt dzienny (dk) | Czas do poziomu 20 / 50 (dni) |
|---|---|---|---|---|---|
| aktywny (95%) | 365 | 40.3 | +15,1% | 74 | 92 / 559 |
| aktywny (95%) | 1825 | 90.7 | +20,8% | 74 | 92 / 559 |
| zwykly (80%) | 365 | 36.9 | +14,4% | 62 | 109 / 664 |
| zwykly (80%) | 1825 | 83.2 | +20,3% | 62 | 109 / 664 |
| okazjonalny (45%) | 365 | 27.6 | +12,2% | 35 | 194 / 1181 |
| okazjonalny (45%) | 1825 | 62.3 | +18,3% | 35 | 194 / 1181 |
| rzadki (12%) | 365 | 14.0 | +7,8% | 9 | 729 / 4427 |
| rzadki (12%) | 1825 | 31.9 | +13,3% | 9 | 729 / 4427 |
