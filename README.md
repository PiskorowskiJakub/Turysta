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

Cel: przez pierwszy miesiac gracz ma codziennie co rozwijac i wystarczajaco duzo dukatów, zeby kolejka (1 aktywne + 2 oczekujace szkolenia) nigdy nie stala pusta z braku pieniedzy.

### Parametry (migracja `011_economy_v3.sql`)

- Koszt poziomu: `floor(3 * 1,16^(n-1))` groszy, czas: `round(5 * 1,16^(n-1))` sekund. Koszt rosnie tak samo szybko jak czas (stosunek ~0,6 grosza na sekunde), wiec pieniadze nigdy nie "uciekaja" przed czasem.
- Powyzej poziomu 50 krzywa splaszcza sie: koszt `* (n/50)^3`, czas `* (n/50)^2`.
- Poziom 50 to ok. 43 dukaty i ok. 2 h. Pierwsze poziomy trwaja kilka sekund, minuty pojawiaja sie okolo dnia 7-10, godziny okolo dnia 10-18 (zaleznie od aktywnosci).
- Awans (rozdzial): wymagania `req(g) = (3+round(2g), 3+round(1,9g), 2+round(1,6g))` poziomow Kondycji/Orientacji/Handlu, koszt `max(50, round(2,5 * koszt(f)))`, czas `max(30, 2 * czas(f))`.
- Start: 200 groszy (2 dukaty) premii na koncie.
- Prace (bazowe wyplaty): targ 8 h (247 dukatów), hostel 12 h (420 dukatów, cooldown 2 h), wyprawa 24 h (900 dukatów, cooldown 4 h).
- **Bonus Handlu** (migracja `012_trade_bonus.sql`): poziom Handlu zwieksza wyplaty ze wszystkich trzech prac, patrz nizej.

### Profile graczy w symulacji

| Profil | Logowania (godziny) | Zalozenia |
|---|---|---|
| aktywny | 8, 11, 14, 17, 20, 22:30 | 6 wejsc dziennie, uzupelnia kolejke i odbiera prace |
| zwykly | 8:30, 13, 20:30 | 3 wejscia dziennie |
| okazjonalny | 19:30 | 1 wejscie dziennie |

Symulacja zaklada ze gracz zawsze uzupelnia kolejke do 3 pozycji (najtansze/najkrotsze wg sensownej kolejnosci), przy kazdej wizycie rozpoczyna dostepna prace i inwestuje w awans, gdy ma wymagane poziomy.

Uruchomienie: `python scripts/simulate_economy.py --profile aktywny --days 30 --markdown` (profile: `aktywny`, `zwykly`, `okazjonalny`; `--days 365` pokazuje caly rok; `--bez-bonusu` wylacza bonus Handlu do porownan). Kolumna "Czas pracy kolejki" to procent doby, w ktorym kolejka cos trenuje; kwoty w dukatówach.

### Wyniki 30 dni (z bonusem Handlu): aktywny

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (dk) | Wydatki (dk) | Saldo (dk) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 5–6 | 1 | 18 | 0% | 8 s | 453 | 1 | 453 |
| 2 | 11–11 | 3 | 18 | 0% | 17 s | 735 | 2 | 1186 |
| 3 | 15–17 | 6 | 18 | 1% | 40 s | 765 | 5 | 1946 |
| 5 | 26–27 | 11 | 18 | 4% | 2 min | 820 | 20 | 3529 |
| 7 | 36–37 | 17 | 18 | 18% | 14 min | 866 | 100 | 5091 |
| 10 | 47–50 | 23 | 11 | 75% | 1.7 h | 922 | 432 | 6770 |
| 14 | 56–59 | 27 | 7 | 83% | 3.1 h | 956 | 572 | 8394 |
| 18 | 64–67 | 31 | 6 | 100% | 4.0 h | 981 | 744 | 9437 |
| 21 | 69–71 | 34 | 5 | 98% | 4.8 h | 995 | 800 | 10236 |
| 25 | 74–77 | 37 | 4 | 100% | 5.8 h | 1010 | 844 | 10963 |
| 30 | 80–83 | 40 | 3 | 100% | 5.3 h | 1025 | 565 | 11583 |

### Wyniki 30 dni: zwykły

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (dk) | Wydatki (dk) | Saldo (dk) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 2–4 | 0 | 9 | 0% | 6 s | 370 | 0 | 372 |
| 2 | 5–6 | 1 | 9 | 0% | 11 s | 662 | 1 | 1034 |
| 3 | 7–9 | 2 | 9 | 0% | 15 s | 685 | 1 | 1718 |
| 5 | 13–14 | 5 | 9 | 0% | 34 s | 710 | 2 | 3123 |
| 7 | 18–19 | 8 | 9 | 1% | 1 min | 740 | 4 | 4583 |
| 10 | 26–27 | 11 | 9 | 2% | 3 min | 776 | 11 | 6845 |
| 14 | 36–37 | 17 | 9 | 10% | 16 min | 815 | 56 | 9906 |
| 18 | 45–48 | 22 | 9 | 47% | 1.3 h | 856 | 273 | 12601 |
| 21 | 51–55 | 25 | 7 | 73% | 2.6 h | 879 | 435 | 14040 |
| 25 | 58–62 | 29 | 6 | 82% | 3.4 h | 900 | 567 | 15623 |
| 30 | 67–69 | 33 | 5 | 89% | 4.6 h | 925 | 734 | 16862 |

### Wyniki 30 dni: okazjonalny

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (dk) | Wydatki (dk) | Saldo (dk) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 0–2 | 0 | 3 | 0% | 5 s | 0 | 0 | 2 |
| 2 | 1–3 | 0 | 3 | 0% | 6 s | 900 | 0 | 902 |
| 3 | 2–4 | 0 | 3 | 0% | 7 s | 0 | 0 | 902 |
| 5 | 5–5 | 0 | 3 | 0% | 8 s | 0 | 0 | 1810 |
| 7 | 6–7 | 1 | 3 | 0% | 11 s | 0 | 0 | 2736 |
| 10 | 9–9 | 3 | 3 | 0% | 20 s | 960 | 1 | 4637 |
| 14 | 12–13 | 4 | 3 | 0% | 28 s | 991 | 0 | 6601 |
| 18 | 15–17 | 6 | 3 | 0% | 48 s | 1013 | 1 | 8609 |
| 21 | 18–19 | 8 | 3 | 0% | 1 min | 0 | 2 | 9624 |
| 25 | 21–23 | 9 | 3 | 0% | 1 min | 0 | 2 | 11685 |
| 30 | 26–27 | 11 | 3 | 1% | 3 min | 1080 | 3 | 14875 |

Kolumna „Zarobek” to wypłaty rozliczone w danym dniu (dni bez zakończonej pracy pokazują 0).

### Bonus Handlu do wypłat z prac

Poziom umiejętności **Handel** zwiększa wypłatę ze wszystkich trzech prac (targ, hostel, wyprawa). Wzór: `bonus = 120% * L / (L + 120)`, czyli hiperbola z sufitem **+120%** (wypłata nigdy nie przekroczy 2,2 × bazowej).

| Poziom Handlu | 10 | 30 | 50 | 80 | 100 | 150 | 200 | 300 |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Bonus do wypłat | +9% | +24% | +35% | +48% | +55% | +67% | +75% | +86% |

- Pierwsze poziomy dają odczuwalny przyrost (każdy poziom ~+1% do poziomu 30), potem zysk z poziomu maleje. Dzięki temu nie ma hiperinflacji, a wydatki na szkolenia (rosnące szybciej niż bonus) nadal wymagają pracy.
- Wypłata jest zapisywana przy starcie pracy (`job_runs.reward_snapshot_cents`), więc późniejszy awans Handlu nie zmienia trwającej pracy. Wypłata proporcjonalna (targ) liczona jest od zapisanej kwoty.
- Premia dotyczy tylko dukatów z prac; punkty za pracę liczone są od faktycznej wypłaty.

### Symulacja roku (365 dni, z bonusem Handlu)

Przychód roczny: aktywny ok. 418 tys. dukatów, zwykły ok. 388 tys., okazjonalny ok. 254 tys. (bez bonusu: 253 / 238 / 164 tys.). Największe saldo w całym roku: aktywny ok. 12 tys. dukatów, zwykły ok. 18 tys., okazjonalny ok. 38 tys. Dzienne wypłaty ustabilizowane na 1,0-1,5 tys. dukatów, czyli **nigdzie nie ma kwot w milionach**.

Pierwszy dzień z brakiem dukatów w kolejce: aktywny 112 (bez bonusu: 45), zwykły 126 (66), okazjonalny 179 (143). W pierwszych 30 dniach (i 60) bonus nie powoduje żadnych niedoborów.

Aktywny, dalsze dni:

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (dk) | Wydatki (dk) | Saldo (dk) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 60 | 108–108 | 49 | 2 | 100% | 9.5 h | 1088 | 895 | 11635 |
| 90 | 129–129 | 49 | 2 | 100% | 13.5 h | 1126 | 1519 | 6997 |
| 120 | 144–145 | 49 | 1 | 90% | 16.8 h | 1149 | 1054 | 169 |
| 180 | 162–163 | 49 | 1 | 67% | 21.3 h | 1174 | 1497 | 879 |
| 270 | 182–183 | 49 | 1 | 67% | 1.1 d | 1197 | 2118 | 1124 |
| 365 | 198–199 | 49 | 0 | 65% | 0 s | 1214 | 0 | 2306 |

Zwykły, dalsze dni:

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (dk) | Wydatki (dk) | Saldo (dk) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 60 | 97–101 | 49 | 2 | 100% | 7.7 h | 999 | 651 | 17274 |
| 90 | 122–123 | 49 | 2 | 100% | 12.2 h | 1045 | 1302 | 12534 |
| 120 | 139–140 | 49 | 2 | 100% | 15.9 h | 1071 | 1938 | 2333 |
| 180 | 159–160 | 49 | 0 | 71% | 0 s | 1097 | 0 | 1256 |
| 270 | 179–179 | 49 | 0 | 92% | 0 s | 1119 | 0 | 1135 |
| 365 | 194–195 | 49 | 0 | 100% | 0 s | 1135 | 0 | 1243 |

Okazjonalny, dalsze dni:

| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (dk) | Wydatki (dk) | Saldo (dk) |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 60 | 49–53 | 25 | 3 | 27% | 2.9 h | 1213 | 218 | 30421 |
| 90 | 75–79 | 38 | 3 | 79% | 5.0 h | 1308 | 512 | 38280 |
| 120 | 100–101 | 49 | 3 | 99% | 8.2 h | 1386 | 1079 | 33006 |
| 180 | 140–141 | 49 | 2 | 96% | 15.9 h | 1480 | 1938 | 254 |
| 270 | 159–160 | 49 | 1 | 19% | 20.5 h | 1515 | 1416 | 1089 |
| 365 | 174–175 | 49 | 0 | 83% | 0 s | 0 | 0 | 1312 |

### Wnioski

- **Pierwsze 30 dni:** w żadnym profilu kolejka nie stoi z braku dukatów; saldo cały czas rośnie.
- **Aktywny** ma co rozwijać codziennie: dni 1-7 to kilkanaście szybkich szkoleń dziennie, od ok. dnia 10 szkolenia trwają godziny i kolejka pracuje niemal cały czas. Do dnia 30 osiąga ok. 80 poziomów i 40. rozdział.
- **Zwykły** (3 wejścia) ma od pierwszego dnia 9 szkoleń, godzinowe szkolenia pojawiają się około dnia 18, a po 30 dniach jest na ok. 33. rozdziale.
- **Okazjonalny** (1 wejście) jest ograniczony liczbą miejsc w kolejce (3 na wizytę), więc postęp jest wolniejszy. Dźwignia na przyszłość: dodatkowe miejsca w kolejce za rangi.
- **Po ok. 4 miesiącach** (po 49 rozdziałach, szkolenia po 10-20 h) gracz jest ograniczony pieniędzmi: koszt poziomu rośnie szybciej niż wypłaty. To celowy hamulec, a bonus Handlu przesuwa ten moment o 2-3 miesiące. Dalsze dźwignie: wyższe prace odblokowywane rangami, sprzedaż przedmiotów, nowe źródła dukatów.
- **Nadwyżka dukatów** w pierwszych miesiącach (saldo 12-38 tys.) jest zarezerwowana na przyszłe wydatki (sklep, wyposażenie).
- Wartości można zmieniać w `skill_definitions` (migracja) oraz w `Economy::TRADE_BONUS_*` i sprawdzać ponownie symulatorem.
