#!/usr/bin/env python3
"""Symulacja ekonomii Turysty (v5): gra od 1 do 365+ dni, gracz z kilkoma sesjami dziennie.

Uruchomienie:  python scripts/simulate_economy.py [--days 30] [--profile aktywny|zwykly|okazjonalny|mieszany] [--bez-bonusu] [--sql]
--sql wypisuje migracje 017 (wymagania i koszty awansow oraz parametry umiejetnosci swiatow) wyliczona z tego modelu.
Model odwzorowuje reguly gry: kolejka 1+2 wspolna dla szkolen i awansow, prace (rynek 8 h
z wyplata proporcjonalna i bez przerwy po przerwaniu, schronisko 12 h, wyprawa 24 h, bez wyplaty
po przerwaniu), bonus Handlu do wyplat (Economy::tradeBonusPermille), bonus startowy, wspolne wzory z backend/src/Game/Economy.php.
"""
import argparse

# ---- parametry ekonomii (musza zgadzac sie z migracja 011 i Economy.php) ----
K0, H = 3, 1.16           # koszt 1. poziomu (grosze) i wzrost kosztu
T0, G = 5, 1.16           # czas 1. poziomu (s) i wzrost czasu (H = G: koszt jest proporcjonalny do czasu)
BONUS_ON = True
FLAT = 50                 # po tym poziomie wzrost wielomianowy
START_CENTS = 200
SLOTS = 4

# Punkty (ranking i prestiz); zasady jak w backend/src/Game/Economy.php i ChapterService.php
POINTS_TRAIN_SECONDS = 1200     # 1 punkt za kazde rozpoczete 20 minut szkolenia
POINTS_CHAPTER_PER_WORLD = 50   # zwykly awans: 50 x numer swiata
POINTS_TRANSITION = 1000        # wejscie do nowego swiata
POINTS_JOB_CENTS = 5000         # 1 punkt za kazde pelne 50 dk z pracy


def train_points(seconds):
    return max(1, -(-int(seconds) // POINTS_TRAIN_SECONDS))


def chapter_points(g):
    """Punkty za awans numer g (1-49; 10, 20, 30, 40 to wejscie do nowego swiata)."""
    return POINTS_TRANSITION if g % 10 == 0 else POINTS_CHAPTER_PER_WORLD * (1 + g // 10)
JOBS = {  # kod: (czas s, wyplata grosze, przerwa s, proporcjonalna)
    'market': (8 * 3600, 24700, 3600, True),
    'hostel': (12 * 3600, 42000, 7200, False),
    'expedition': (24 * 3600, 90000, 14400, False),
}
BONUS_MAX, BONUS_HALF = 1.2, 120   # bonus handlu: +BONUS_MAX * L / (L + BONUS_HALF)  (asymptota +120%)


def trade_bonus(level):
    """Mnoznik wyplat za prace zalezny od poziomu Handlu (zgodny z Economy::tradeMultiplier)."""
    return 1 + BONUS_MAX * level / (level + BONUS_HALF)


TRANSITION_COST, TRANSITION_TIME = 20, 6   # awans do nowego swiata: x20 koszt, x6 czas
WORLD_SHARE_EVERY = 4   # co N-ty element kolejki to umiejetnosc swiata (0 = gracz ich nie szkoli)
WORLD_SKILLS = 6        # umiejetnosci na swiat
WORLD_LEVELS = 33       # maksymalny poziom umiejetnosci swiata
ORIG_SKILLS, ORIG_LEVELS = 10, 20   # model v5 (10 x 20 poziomow) - lacznie tyle samo pracy
WORLD_END = 0.25        # czas ostatniego poziomu umiejetnosci swiata = 25% czasu szkolenia podstawowej na koncu swiata
WORLD_FRESH = 0.15      # poziom 1 umiejetnosci swiata = 15% czasu szkolenia podstawowej na poziomie wejscia do swiata
WORLD_START_LEVEL = [1, 41, 80, 118, 157]  # nadpisywane po ustawieniu REQ_SLOPE (patrz world_params)


def _geo(t0, g, levels):
    return sum(t0 * g ** (n - 1) for n in range(1, levels + 1))


def world_params(w):
    """(k0, h, t0, g) umiejetnosci swiata w (1..5).

    Wzrost g dobrany tak, aby caly zestaw (WORLD_SKILLS x WORLD_LEVELS) wymagal tyle samo czasu (i kosztu), co model v5
    (ORIG_SKILLS x ORIG_LEVELS, ostatni poziom = WORLD_END czasu podstawowej na koncu swiata).
    """
    f_in = 1 if w == 1 else req(10 * (w - 1))[0]
    f_out = req(10 * w)[0] if w < 5 else req(49)[0]
    t0 = max(5, round(WORLD_FRESH * secs(f_in)))
    k0 = max(3, round(0.6 * t0))
    g_orig = min(1.30, max(1.03, (max(t0 * 1.5, WORLD_END * secs(f_out)) / t0) ** (1 / (ORIG_LEVELS - 1))))
    target = ORIG_SKILLS * _geo(t0, g_orig, ORIG_LEVELS)
    lo, hi = 1.0001, 1.5
    for _ in range(80):
        mid = (lo + hi) / 2
        if WORLD_SKILLS * _geo(t0, mid, WORLD_LEVELS) < target:
            lo = mid
        else:
            hi = mid
    g = round((lo + hi) / 2, 4)
    return k0, g, t0, g


PROFILES = {
    'mieszany': None,  # 60 dni aktywny, potem zwykly
  # godziny sesji w ciagu doby
    'aktywny': [8, 11, 14, 17, 20, 22.5],
    'zwykly': [8.5, 13, 20.5],
    'okazjonalny': [19.5],
}


def cost(n):
    return int(K0 * H ** (n - 1)) if n <= FLAT else int(int(K0 * H ** (FLAT - 1)) * (n / FLAT) ** 2)


def secs(n):
    return max(1, round(T0 * G ** (n - 1))) if n <= FLAT else round(T0 * G ** (FLAT - 1) * (n / FLAT) ** 2)


MAX_CHAPTERS = 49  # 5 swiatow po 10 rozdzialow, pierwszy jest startowy


REQ_SLOPE = 2.0   # przyrost wymaganego poziomu Kondycji na jeden awans (Orientacja 0.95x, Handel 0.8x)


REQ_F, REQ_P = 180, 0.55   # wymagany poziom Kondycji na ostatnim awansie (49) i wykladnik krzywej


def req(g):
    f = REQ_F_BASE + (REQ_F - REQ_F_BASE) * (g / 49) ** REQ_P
    return 3 + round(f - 3), 3 + round(0.95 * (f - 3)), 2 + round(0.8 * (f - 3))


REQ_F_BASE = 3


def chapter(g):
    """Wymagania, koszt i czas awansu numer g (g=1 to pierwszy awans)."""
    f, o, t = req(g)
    c, d = max(50, round(2.5 * cost(f))), max(30, 2 * secs(f))
    if g % 10 == 0 and g < 50:      # przejscie do nowego swiata: wydarzenie
        c, d = c * TRANSITION_COST, d * TRANSITION_TIME
    return (f, o, t), c, d


def world_req(g):
    """Wymaganie umiejetnosci swiata dla awansu g: (indeks swiata od 0, wymagany poziom kazdej z umiejetnosci swiata)."""
    wt = 1 + g // 10
    ct = g - 10 * (wt - 1) + 1
    if ct == 1:
        return wt - 2, WORLD_LEVELS
    return wt - 1, -(-WORLD_LEVELS * (ct - 1) // 10)


def fmt(s):
    s = int(s)
    if s < 60: return f'{s} s'
    if s < 3600: return f'{s // 60} min'
    if s < 86400: return f'{s / 3600:.1f} h'
    return f'{s / 86400:.1f} d'


def simulate(days, profile):
    mixed = profile == 'mieszany'
    sessions = PROFILES['aktywny' if mixed else profile]
    wplanned = [[0] * WORLD_SKILLS for _ in range(5)]
    wp = [world_params(w) for w in range(1, 6)]
    counter = [0]
    milestones = {}
    coins = START_CENTS
    lv = [0, 0, 0]
    planned = [0, 0, 0]
    chap = 0           # ukonczone awanse
    chap_q = False
    queue = []         # (typ, idx, sekundy, koszt)
    active = None      # [typ, idx, koniec]
    job = None         # [kod, start, koniec, wyplata]
    cd_until = 0.0
    rows = []
    stat = dict(started=0, done=0, active_s=0.0, earned=0, spent=0, empty=0, durs=[], broke=0)
    points = [0]
    t = 0.0

    wlv = [[0] * WORLD_SKILLS for _ in range(5)]

    def need(i):
        return req(min(chap + 1, MAX_CHAPTERS))[i]

    def world_ok():
        w, lvl = world_req(min(chap + 1, MAX_CHAPTERS))
        return all(wlv[w][i] >= lvl for i in range(WORLD_SKILLS))

    def advance(to):
        """Przesuwa czas do `to`, rozliczajac konczace sie szkolenia i prace."""
        nonlocal t, active, job, coins, chap, chap_q, cd_until
        while True:
            events = []
            if active: events.append((active[2], 'train'))
            if job: events.append((job[2], 'job'))
            events = [e for e in events if e[0] <= to]
            if not events:
                if active: stat['active_s'] += to - t
                t = to
                return
            when, kind = min(events)
            if active: stat['active_s'] += when - t
            t = when
            if kind == 'train':
                typ, idx, _, dur = active
                if typ == 'skill': lv[idx] += 1; points[0] += train_points(dur)
                elif typ == 'wskill': wlv[idx[0]][idx[1]] += 1; points[0] += train_points(dur)
                elif typ == 'chapter':
                    chap += 1; chap_q = False; points[0] += chapter_points(chap)
                    milestones.setdefault(chap, int(t // 86400) + 1)
                stat['done'] += 1
                active = None
                if queue:
                    q = queue.pop(0)
                    active = [q[0], q[1], t + q[2], q[2]]
            else:
                code, st, en, rew = job
                coins += rew; stat['earned'] += rew; points[0] += rew // POINTS_JOB_CENTS
                cd_until = en + JOBS[code][2]
                job = None

    def push(q):
        nonlocal active
        if active is None:
            active = [q[0], q[1], t + q[2], q[2]]
        else:
            queue.append(q)

    def wcost(w, n):
        k0, h, t0_, g_ = wp[w]
        return int(k0 * h ** (n - 1)), round(t0_ * g_ ** (n - 1))

    def fill():
        nonlocal coins, chap_q
        while (1 if active else 0) + len(queue) < SLOTS:
            g = min(chap + 1, MAX_CHAPTERS)
            if not chap_q and chap < MAX_CHAPTERS and all(lv[i] >= need(i) for i in range(3)) and world_ok():
                (_, c, d) = chapter(chap + 1)
                if coins >= c:
                    coins -= c; stat['spent'] += c; chap_q = True; stat['started'] += 1; stat['durs'].append(d)
                    push(('chapter', 0, d, c)); continue
            # kandydaci: (czas, koszt, typ, indeks, czy wymagany)
            ww, wl = world_req(g)
            cands = []
            for i in range(3):
                n = planned[i] + 1
                cands.append((secs(n), cost(n), 'skill', i, planned[i] < need(i)))
            cur_world = min(5, 1 + chap // 10)
            for w in range(cur_world):
                for i in range(WORLD_SKILLS):
                    n = wplanned[w][i] + 1
                    if n <= WORLD_LEVELS:
                        d_, c_ = wcost(w, n)[1], wcost(w, n)[0]
                        cands.append((d_, c_, 'wskill', (w, i), w == ww and wplanned[w][i] < wl))
            required = [c for c in cands if c[4]]
            pool = required if required else cands
            d_, c_, typ, idx, _ = min(pool, key=lambda c: (c[0], c[1]))
            if coins < c_:
                stat['broke'] += 1
                break
            coins -= c_; stat['spent'] += c_; stat['started'] += 1; stat['durs'].append(d_)
            if typ == 'skill': planned[idx] += 1
            else: wplanned[idx[0]][idx[1]] += 1
            push((typ, idx, d_, c_))

    def job_step(next_gap):
        nonlocal job, coins
        if job and JOBS[job[0]][3]:                       # rynek: wyplata proporcjonalna, brak przerwy
            code, st, en, rew = job
            pay = int(rew * (t - st) / (en - st))
            coins += pay; stat['earned'] += pay; points[0] += pay // POINTS_JOB_CENTS; job = None
        if job is None and t >= cd_until:
            code = 'expedition' if next_gap >= 24 * 3600 - 1 else 'hostel' if next_gap >= 12 * 3600 - 1 else 'market'
            job = [code, t, t + JOBS[code][0], int(JOBS[code][1] * (trade_bonus(lv[2]) if BONUS_ON else 1))]

    for day in range(days):
        if mixed:
            sessions = PROFILES['aktywny' if day < 60 else 'zwykly']
        stat.update(started=0, done=0, active_s=0.0, earned=0, spent=0, empty=0, durs=[], broke=0)
        for k, h in enumerate(sessions):
            target = day * 86400 + h * 3600
            advance(target)
            if active is None and not queue and (day > 0 or k > 0):
                stat['empty'] += 1
            nxt = (day * 86400 + sessions[k + 1] * 3600 if k + 1 < len(sessions) else (day + 1) * 86400 + sessions[0] * 3600) - t
            job_step(nxt)
            fill()
        advance((day + 1) * 86400)
        d = stat['durs']
        rows.append((day + 1, min(lv), max(lv), chap, stat['started'], stat['done'], stat['active_s'] / 86400,
                     (sum(d) / len(d)) if d else 0, stat['earned'], stat['spent'], coins, stat['empty'], stat['broke'], points[0]))
    simulate.milestones = milestones
    return rows


def markdown(rows, days_shown):
    # wyplata za prace jest zapisywana przy starcie; kolumna 'Zarobek' to wyplaty rozliczone w danym dniu
    head = ('| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |\n'
            '|---:|---:|---:|---:|---:|---:|---:|---:|---:|\n')
    out = head
    for r in rows:
        if r[0] in days_shown:
            out += (f'| {r[0]} | {r[1]}–{r[2]} | {r[3]} | {r[4]} | {r[6] * 100:.0f}% | {fmt(r[7])} | {r[8] / 100:.0f} | {r[9] / 100:.0f} | {r[10] / 100:.0f} |\n')
    return out


def milestones_text(rows_days, ms):
    return ', '.join(f'swiat {w} od dnia {ms.get(10 * (w - 1))}' for w in range(2, 6)) + f', ukonczenie awansow w dniu {ms.get(49)}'


REMOVED_WORLD_SKILLS = [  # zestaw 10 -> 6: usuwane umiejetnosci swiatow
    'w1_boots', 'w1_weather', 'w1_photo', 'w1_etiquette',
    'w2_woodcutting', 'w2_stream_crossing', 'w2_insects', 'w2_silence',
    'w3_skiing', 'w3_ice_reading', 'w3_snow_melting', 'w3_frostbite',
    'w4_oasis', 'w4_dunes', 'w4_cold_nights', 'w4_dried_food',
    'w5_rope_bridge', 'w5_rainwater', 'w5_jungle_listening', 'w5_local_speech',
]


def migration_sql(chapters=False):
    """Domyslnie migracja 017 (umiejetnosci swiatow); z chapters=True migracja awansow (jak 016)."""
    out = []
    if not chapters:
        names = ', '.join(f"'{c}'" for c in REMOVED_WORLD_SKILLS)
        out += [f'-- Umiejetnosci swiatow: {WORLD_SKILLS} na swiat po {WORLD_LEVELS} poziomow (lacznie tyle samo pracy i kosztu co 10 x 20 w migracji 016).',
                '-- Wygenerowane przez: python scripts/simulate_economy.py --sql', '',
                f'DELETE FROM training_queue WHERE skill_id IN (SELECT id FROM skill_definitions WHERE code IN ({names}));', '',
                f'DELETE FROM user_skills WHERE skill_id IN (SELECT id FROM skill_definitions WHERE code IN ({names}));', '',
                f'DELETE FROM skill_definitions WHERE code IN ({names});', '',
                f'UPDATE skill_definitions SET max_level = {WORLD_LEVELS} WHERE world_ordinal IS NOT NULL;', '']
        for w in range(1, 6):
            k0, h, t0, g = world_params(w)
            out += [f'UPDATE skill_definitions SET base_cost_cents = {k0}, cost_growth = {h:.4f}, base_seconds = {t0}, time_growth = {g:.4f} WHERE world_ordinal = {w};', '']
        return '\n'.join(out)
    out += ['-- Awanse: wymagania i koszty (wygenerowane przez: python scripts/simulate_economy.py --sql-chapters)', '']
    for gi in range(1, MAX_CHAPTERS + 1):
        w = 1 + gi // 10
        c = gi - 10 * (w - 1) + 1
        (f, o, t), cst, dur = chapter(gi)
        out.append(f'UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = {cst}, c.duration_seconds = {dur} WHERE w.ordinal = {w} AND c.ordinal = {c};')
        out.append('')
        for code, lvl in (('fitness', f), ('orientation', o), ('trade', t)):
            out.append(f"UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = {lvl} WHERE s.code = '{code}' AND w.ordinal = {w} AND c.ordinal = {c};")
            out.append('')
    return '\n'.join(out)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--days', type=int, default=30)
    ap.add_argument('--profile', default='zwykly', choices=list(PROFILES))
    ap.add_argument('--markdown', action='store_true', help='wypisz tabelę w formacie Markdown (wybrane dni)')
    ap.add_argument('--sql', action='store_true', help='wypisz migracje 017 (umiejetnosci swiatow) wyliczona z modelu')
    ap.add_argument('--sql-chapters', action='store_true', help='wypisz UPDATE-y awansow (wymagania, koszty)')
    ap.add_argument('--punkty', action='store_true', help='wypisz lacznie zdobyte punkty w wybranych dniach')
    ap.add_argument('--bez-bonusu', action='store_true', help='wylacz bonus Handlu (porownanie)')
    a = ap.parse_args()
    global BONUS_ON
    BONUS_ON = not a.bez_bonusu
    if a.sql or a.sql_chapters:
        print(migration_sql(chapters=a.sql_chapters))
        return
    if a.punkty:
        rows = simulate(a.days, a.profile)
        print(f'Profil: {a.profile}')
        for r in rows:
            if r[0] in (1, 7, 14, 30, 60, 90, 120, 180, 270, 365, a.days):
                print(f'dzien {r[0]:>4}: {r[13]:>7} pkt')
        return
    if a.markdown:
        shown = {1, 2, 3, 5, 7, 10, 14, 18, 21, 25, 30} if a.days <= 30 else {1, 7, 14, 30, 60, 90, 120, 180, 270, 365}
        print(markdown(simulate(a.days, a.profile), shown))
        return
    print(f'Profil: {a.profile}, sesje o godz. {PROFILES[a.profile]}')
    print('dzien | poziomy | awans | start. | ukonczone | czas trening. | sr. czas | zarobek | wydatki | saldo | puste kolejki')
    for r in simulate(a.days, a.profile):
        print(f'{r[0]:>5} | {r[1]:>3}-{r[2]:<3} | {r[3]:>5} | {r[4]:>6} | {r[5]:>9} | {r[6] * 100:>10.0f}% | {fmt(r[7]):>8} | {r[8] / 100:>7.0f} | {r[9] / 100:>7.0f} | {r[10] / 100:>5.0f} | {r[11]:>5}')


if __name__ == '__main__':
    main()
