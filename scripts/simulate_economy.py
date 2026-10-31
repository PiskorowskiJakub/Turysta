#!/usr/bin/env python3
"""Symulacja ekonomii Turysty: 30 dni gry gracza z kilkoma sesjami dziennie.

Uruchomienie:  python scripts/simulate_economy.py [--days 30] [--profile aktywny|zwykly|okazjonalny]
Model odwzorowuje reguly gry: kolejka 1+2 wspolna dla szkolen i awansow, prace (rynek 8 h
z wyplata proporcjonalna i bez przerwy po przerwaniu, schronisko 12 h, wyprawa 24 h, bez wyplaty
po przerwaniu), bonus startowy, wspolne wzory z backend/src/Game/Economy.php.
"""
import argparse

# ---- parametry ekonomii (musza zgadzac sie z migracja 011 i Economy.php) ----
K0, H = 3, 1.16           # koszt 1. poziomu (grosze) i wzrost kosztu
T0, G = 5, 1.16           # czas 1. poziomu (s) i wzrost czasu (H = G: koszt jest proporcjonalny do czasu)
FLAT = 50                 # po tym poziomie wzrost wielomianowy
START_CENTS = 200
SLOTS = 3
JOBS = {  # kod: (czas s, wyplata grosze, przerwa s, proporcjonalna)
    'market': (8 * 3600, 24700, 3600, True),
    'hostel': (12 * 3600, 42000, 7200, False),
    'expedition': (24 * 3600, 90000, 14400, False),
}
PROFILES = {  # godziny sesji w ciagu doby
    'aktywny': [8, 11, 14, 17, 20, 22.5],
    'zwykly': [8.5, 13, 20.5],
    'okazjonalny': [19.5],
}


def cost(n):
    return int(K0 * H ** (n - 1)) if n <= FLAT else int(int(K0 * H ** (FLAT - 1)) * (n / FLAT) ** 3)


def secs(n):
    return max(1, round(T0 * G ** (n - 1))) if n <= FLAT else round(T0 * G ** (FLAT - 1) * (n / FLAT) ** 2)


MAX_CHAPTERS = 49  # 5 swiatow po 10 rozdzialow, pierwszy jest startowy


def req(g):
    return 3 + round(2.0 * g), 3 + round(1.9 * g), 2 + round(1.6 * g)


def chapter(g):
    """Wymagania, koszt i czas awansu numer g (g=1 to pierwszy awans)."""
    f, o, t = req(g)
    return (f, o, t), max(50, round(2.5 * cost(f))), max(30, 2 * secs(f))


def fmt(s):
    s = int(s)
    if s < 60: return f'{s} s'
    if s < 3600: return f'{s // 60} min'
    if s < 86400: return f'{s / 3600:.1f} h'
    return f'{s / 86400:.1f} d'


def simulate(days, profile):
    sessions = PROFILES[profile]
    coins = START_CENTS
    lv = [0, 0, 0]
    planned = [0, 0, 0]
    chap = 0           # ukonczone awanse
    chap_q = False
    queue = []         # (typ, idx, sekundy, koszt)
    active = None      # [typ, idx, koniec]
    job = None         # [kod, start, koniec]
    cd_until = 0.0
    rows = []
    stat = dict(started=0, done=0, active_s=0.0, earned=0, spent=0, empty=0, durs=[], broke=0)
    t = 0.0

    def need(i):
        return req(min(chap + 1, MAX_CHAPTERS))[i]

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
                typ, idx, _ = active
                if typ == 'skill': lv[idx] += 1
                else: chap += 1; chap_q = False
                stat['done'] += 1
                active = None
                if queue:
                    q = queue.pop(0)
                    active = [q[0], q[1], t + q[2]]
            else:
                code, st, en = job
                coins += JOBS[code][1]; stat['earned'] += JOBS[code][1]
                cd_until = en + JOBS[code][2]
                job = None

    def push(q):
        nonlocal active
        if active is None:
            active = [q[0], q[1], t + q[2]]
        else:
            queue.append(q)

    def fill():
        nonlocal coins, chap_q
        while (1 if active else 0) + len(queue) < SLOTS:
            if not chap_q and chap < MAX_CHAPTERS and all(lv[i] >= need(i) for i in range(3)):
                (_, c, d) = chapter(chap + 1)
                if coins >= c:
                    coins -= c; stat['spent'] += c; chap_q = True; stat['started'] += 1; stat['durs'].append(d)
                    push(('chapter', 0, d, c)); continue
            s = min(range(3), key=lambda i: (planned[i] - need(i) if planned[i] < need(i) else 1000 + planned[i]))
            n = planned[s] + 1
            c = cost(n)
            if coins < c:
                stat['broke'] += 1
                break
            coins -= c; stat['spent'] += c; planned[s] += 1; stat['started'] += 1; stat['durs'].append(secs(n))
            push(('skill', s, secs(n), c))

    def job_step(next_gap):
        nonlocal job, coins
        if job and JOBS[job[0]][3]:                       # rynek: wyplata proporcjonalna, brak przerwy
            code, st, en = job
            pay = int(JOBS[code][1] * (t - st) / (en - st))
            coins += pay; stat['earned'] += pay; job = None
        if job is None and t >= cd_until:
            code = 'expedition' if next_gap >= 24 * 3600 - 1 else 'hostel' if next_gap >= 12 * 3600 - 1 else 'market'
            job = [code, t, t + JOBS[code][0]]

    for day in range(days):
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
                     (sum(d) / len(d)) if d else 0, stat['earned'], stat['spent'], coins, stat['empty'], stat['broke']))
    return rows


def markdown(rows, days_shown):
    head = ('| Dzień | Poziomy umiej. | Awans (rozdz.) | Szkolenia/dzień | Czas pracy kolejki | Śr. czas szkolenia | Zarobek (mon.) | Wydatki (mon.) | Saldo (mon.) |\n'
            '|---:|---:|---:|---:|---:|---:|---:|---:|---:|\n')
    out = head
    for r in rows:
        if r[0] in days_shown:
            out += (f'| {r[0]} | {r[1]}–{r[2]} | {r[3]} | {r[4]} | {r[6] * 100:.0f}% | {fmt(r[7])} | {r[8] / 100:.0f} | {r[9] / 100:.0f} | {r[10] / 100:.0f} |\n')
    return out


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--days', type=int, default=30)
    ap.add_argument('--profile', default='zwykly', choices=PROFILES)
    ap.add_argument('--markdown', action='store_true', help='wypisz tabelę w formacie Markdown (wybrane dni)')
    a = ap.parse_args()
    if a.markdown:
        print(markdown(simulate(a.days, a.profile), {1, 2, 3, 5, 7, 10, 14, 18, 21, 25, 30}))
        return
    print(f'Profil: {a.profile}, sesje o godz. {PROFILES[a.profile]}')
    print('dzien | poziomy | awans | start. | ukonczone | czas trening. | sr. czas | zarobek | wydatki | saldo | puste kolejki')
    for r in simulate(a.days, a.profile):
        print(f'{r[0]:>5} | {r[1]:>3}-{r[2]:<3} | {r[3]:>5} | {r[4]:>6} | {r[5]:>9} | {r[6] * 100:>10.0f}% | {fmt(r[7]):>8} | {r[8] / 100:>7.0f} | {r[9] / 100:>7.0f} | {r[10] / 100:>5.0f} | {r[11]:>5}')


if __name__ == '__main__':
    main()
