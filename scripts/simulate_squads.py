#!/usr/bin/env python3
"""Symulacja ekonomii Oddzialu (ognisko, szlaki, schronisko, umiejetnosci oddzialowe).

Uruchomienie:  python scripts/simulate_squads.py [--runs 300] [--days 540] [--markdown] [--seed 1]

Model Monte Carlo: oddzial skladaja gracze o roznej regularnosci wchodzenia do gry (prawdopodobienstwo aktywnego dnia).
Przychod gracza z prac (ok. 1000 dk/dzien) i nadwyzki dukatow pochodza z scripts/simulate_economy.py (aktywny/zwykly/okazjonalny).
Wszystkie parametry sa w slowniku P (zgodnie z docelowym projektem: docs w artefakcie "projekt mechanik Oddzialu").
1 dk (dukat) = 100 groszy, koszt szkolenia gracza to ok. 0,6 grosza na sekunde = 21,6 dk na godzine.
"""
import argparse
import math
import random
import statistics

P = dict(
    threshold=0.30,            # ognisko: odsetek aktywnych czlonkow, min. 2 osoby
    flame_days=[0, 3, 7, 14, 30],        # serie dni odpowiadajace poziomom plomienia 0..4 (iskra, plomien, ognisko, watra, wielka watra)
    flame_mult=[1.0, 1.05, 1.10, 1.15, 1.20],   # premia za serie
    day_tiers=[(0.30, 1.0), (0.50, 1.25), (0.70, 1.5)],   # premia za dzien: udzial aktywnych >= prog -> mnoznik zaru
    ember_per_day=100,         # zar za dzien palenia (niezalezny od liczby czlonkow)
    pts_per_active_day=10,     # punkty szlaku za aktywny dzien czlonka
    trail_per_active=45,       # punkty bazowe celu na aktywnego czlonka tygodniowo (ok. 4,5 dnia z 7), mnozone przez trudnosc
    trail_levels={'latwa': 0.85, 'srednia': 1.10, 'trudna': 1.35},
    trail_stamps={'latwa': 3, 'srednia': 5, 'trudna': 8},
    deposit_dk=25,             # srednia wplata czlonka w aktywny dzien (dk)
    deposit_prob=0.5,          # szansa, ze aktywny czlonek wplaca danego dnia
    deposit_cap_dk=50,         # dzienny limit wplat na osobe (wzrasta ze Spizarnia)
    income_dk_day=1000,        # przychod gracza z prac (simulate_economy.py)
    # umiejetnosci oddzialowe: czas poziomu n = T1 * n^1.5 godzin (osobny slot szkolenia), koszt = czas * 21,6 dk * K
    skill_t1_h=12.0, skill_pow=1.5, skill_cost_factor=0.25, skill_max=10, skill_l50=5,
    skill_emax=dict(przewodnictwo=0.15, rozpalanie=0.30, kartografia=0.20, zaopatrzenie=0.15),   # zaopatrzenie = znizka kosztu dukatowego schroniska
    cap_base=2, cap_per_level=2,   # sufit poziomu pojedynczej osoby: cap_base + cap_per_level * poziom Spizarni
    # schronisko: 5 pomieszczen po 5 poziomow; koszt n-tego poziomu = baza * wzrost^(n-1)
    rooms=['kuchnia', 'spizarnia', 'izba_chorych', 'mapownia', 'izba_pamieci'],
    room_ember=dict(base=2400, growth=1.5), room_stamps=dict(base=8, growth=1.5), room_dk=dict(base=90, growth=1.5),   # dk: na aktywnego czlonka
    kitchen_job_bonus_per_level=0.006,     # +0,6% wyplat z prac za poziom Kuchni (max +3%)
    mapping_stamps_per_level=0.05,         # Mapownia: +5% piecza tek ze szlakow za poziom
)

PROFILES = {'aktywny': 0.95, 'zwykly': 0.80, 'okazjonalny': 0.45, 'rzadki': 0.12}
INCOME_FACTOR = {'aktywny': 1.0, 'zwykly': 1.0, 'okazjonalny': 0.95, 'rzadki': 0.25}

SQUADS = {
    'garstka (4, zgrana)': (4, dict(aktywny=0.5, zwykly=0.5)),
    'maly zgrany (8)': (8, dict(aktywny=0.5, zwykly=0.4, okazjonalny=0.1)),
    'typowy (15)': (15, dict(aktywny=0.2, zwykly=0.4, okazjonalny=0.3, rzadki=0.1)),
    'duzy typowy (30)': (30, dict(aktywny=0.2, zwykly=0.3, okazjonalny=0.3, rzadki=0.2)),
    'duzy senny (30)': (30, dict(aktywny=0.05, zwykly=0.15, okazjonalny=0.35, rzadki=0.45)),
}

SKILLS = ['przewodnictwo', 'rozpalanie', 'pierwsza_pomoc', 'kartografia', 'zaopatrzenie']


def skill_hours(n):
    return P['skill_t1_h'] * n ** P['skill_pow']


def skill_cost_dk(n):
    return skill_hours(n) * 21.6 * P['skill_cost_factor']


def effect(skill, level):
    e = P['skill_emax'].get(skill)
    return 0.0 if e is None else e * level / (level + P['skill_l50'])


def room_cost(room, n, active=10):
    d = {'kuchnia': 1.0, 'spizarnia': 1.0, 'izba_chorych': 1.0, 'mapownia': 1.0, 'izba_pamieci': 0.6}[room]
    return (round(P['room_ember']['base'] * P['room_ember']['growth'] ** (n - 1) * d),
            max(1, round(P['room_stamps']['base'] * P['room_stamps']['growth'] ** (n - 1) * d)),
            round(P['room_dk']['base'] * P['room_dk']['growth'] ** (n - 1) * d * max(5, active)))


def build_squad(size, mix, rng, focus):
    kinds = rng.choices(list(mix), weights=list(mix.values()), k=size)
    members = []
    for k in kinds:
        order = focus[:] if focus else rng.sample(SKILLS, len(SKILLS))
        members.append(dict(kind=k, p=PROFILES[k], lv={s: 0 for s in SKILLS}, prog=0.0, order=order, last=-99,
                            week_days=0, hist=[]))
    return members


def run(size, mix, days, rng, focus=None):
    members = build_squad(size, mix, rng, focus)
    for m in members:
        m['last'] = 0
    streak, flame = 0, 0
    ember = stamps = dk_pool = 0.0
    rooms = {r: 0 for r in P['rooms']}
    log = dict(lit=0, mult=0.0, ember=0.0, deposits=0.0, skill_dk=0.0, bonus_dk=0.0, week_ok={k: 0 for k in P['trail_levels']},
               weeks=0, stamps_total=0.0, levels_day={}, eff_at={}, prog=0.0, shield_used=0, flame_days=[0] * 5)
    week_pts = 0.0
    shields = 0
    for day in range(days):
        n = len(members)
        act = [m for m in members if rng.random() < m['p']]
        actset = set(id(m) for m in act)
        for m in members:
            m['hist'].append(id(m) in actset)
            if len(m['hist']) > 14:
                m['hist'].pop(0)
        for m in act:
            m['last'] = day
            m['week_days'] += 1
        active_n = sum(1 for m in members if sum(m['hist']) >= 3)   # aktywny czlonek: >= 3 aktywne dni w ostatnich 14
        # --- skill levels: capped by Spizarnia, averaged over members active this week (>= 1 day recently)
        cap = min(P['skill_max'], P['cap_base'] + P['cap_per_level'] * rooms['spizarnia'])
        recent = [m for m in members if day - m['last'] <= 6] or members
        avg = {s: statistics.fmean(min(m['lv'][s], cap) for m in recent) for s in SKILLS}
        e_przew, e_rozp, e_zaop = effect('przewodnictwo', avg['przewodnictwo']), effect('rozpalanie', avg['rozpalanie']), effect('zaopatrzenie', avg['zaopatrzenie'])
        # --- ognisko
        need = max(2, math.ceil(P['threshold'] * n))
        if len(act) >= need:
            log['lit'] += 1
            streak += 1
            flame = max(i for i, d in enumerate(P['flame_days']) if d <= streak)
        else:
            if shields > 0:
                shields -= 1
                log['shield_used'] += 1
            else:
                flame = max(0, flame - 1)
                streak = P['flame_days'][flame]
        log['flame_days'][flame] += 1
        if len(act) >= need:
            share = len(act) / n
            tier = max(m for t, m in P['day_tiers'] if share >= t) if share >= P['day_tiers'][0][0] else 1.0
            g = P['ember_per_day'] * tier * P['flame_mult'][flame] * (1 + e_rozp)
            ember += g
            log['ember'] += g
        log['mult'] += (g / (P['ember_per_day'] * (1 + e_rozp))) if len(act) >= need else 0.0
        # --- szlak (punkty)
        week_pts += len(act) * P['pts_per_active_day'] * (1 + e_przew)
        # --- wplaty do schroniska
        dep_cap = P['deposit_cap_dk']
        e_kart = effect('kartografia', avg['kartografia'])
        for m in act:
            if rng.random() < P['deposit_prob']:
                d = min(P['deposit_dk'], dep_cap)
                dk_pool += d
                log['deposits'] += d
        # --- szkolenia oddzialowe (osobny slot): postep zalezy od regularnosci
        for m in members:
            hours = 24 * m['p']
            while hours > 0:
                tgt = next((s for s in m['order'] if m['lv'][s] < P['skill_max'] and s != 'pierwsza_pomoc' or (s == 'pierwsza_pomoc' and m['lv'][s] < P['skill_max'])), None)
                if tgt is None:
                    break
                t = skill_hours(m['lv'][tgt] + 1)
                take = min(hours, t - m['prog'])
                m['prog'] += take
                hours -= take
                if m['prog'] >= t - 1e-9:
                    log['skill_dk'] += skill_cost_dk(m['lv'][tgt] + 1)
                    m['lv'][tgt] += 1
                    m['prog'] = 0.0
        # --- bonus Kuchni (emisja): +x% wyplat z prac
        kb = P['kitchen_job_bonus_per_level'] * rooms['kuchnia']
        if kb:
            log['bonus_dk'] += sum(P['income_dk_day'] * INCOME_FACTOR[m['kind']] * kb for m in members)
        # --- koniec tygodnia: ocena szlaku
        if day % 7 == 6:
            base = P['trail_per_active'] * active_n
            log['weeks'] += 1
            for lvl, f in P['trail_levels'].items():
                if week_pts >= base * f:
                    log['week_ok'][lvl] += 1
            f = P['trail_levels']['srednia']
            ratio = week_pts / (base * f) if base else 0
            if ratio >= 1:
                got = P['trail_stamps']['srednia']
            elif ratio >= 0.5:
                got = int(P['trail_stamps']['srednia'] * ratio)
            else:
                got = 0
            got *= (1 + P['mapping_stamps_per_level'] * rooms['mapownia']) * (1 + e_kart)
            stamps += got
            log['stamps_total'] += got
            week_pts = 0.0
            for m in members:
                m['week_days'] = 0
            # tarcza ogniska (Pierwsza pomoc + Izba chorych)
            fa = statistics.fmean(min(m['lv']['pierwsza_pomoc'], cap) for m in recent)
            shields = (1 if fa >= 5 else 0) + (1 if fa >= 15 else 0) + (1 if rooms['izba_chorych'] >= 3 else 0)
        # --- schronisko: kolejne ulepszenie (najnizszy poziom, potem najtanszy)
        order = sorted(P['rooms'], key=lambda r: (rooms[r], room_cost(r, rooms[r] + 1, active_n)[0]))
        for r in order:
            if rooms[r] >= 5:
                continue
            ze, st, dkc = room_cost(r, rooms[r] + 1, active_n)
            dkc = round(dkc * (1 - e_zaop))
            if ember >= ze and stamps >= st and dk_pool >= dkc:
                ember -= ze
                stamps -= st
                dk_pool -= dkc
                rooms[r] += 1
                for lvl in range(1, 6):
                    if min(rooms.values()) >= lvl and lvl not in log['levels_day']:
                        log['levels_day'][lvl] = day + 1
                log.setdefault('first_day', {}).setdefault(r, day + 1)
            break
        if day + 1 in (90, 180, 365, 540):
            log['eff_at'][day + 1] = dict(
                przew=e_przew, rozp=e_rozp, zaop=e_zaop, avg=statistics.fmean(avg.values()), rooms=sum(rooms.values()),
                skill_avg=avg['rozpalanie'], cap=cap, flame=flame, bonus_pct=P['kitchen_job_bonus_per_level'] * rooms['kuchnia'])
    log['rooms'] = rooms
    log['n'] = len(members)
    return log


def mean(vals):
    vals = [v for v in vals if v is not None]
    return statistics.fmean(vals) if vals else None


def simulate(runs, days, seed, focus):
    rng = random.Random(seed)
    out = {}
    for name, (size, mix) in SQUADS.items():
        logs = [run(size, mix, days, rng, focus) for _ in range(runs)]
        out[name] = logs
    return out


def fmt(v, nd=0, dash='-'):
    return dash if v is None else f'{v:.{nd}f}'


def report(out, days, md):
    sep = '|' if md else ' | '
    names = list(out)

    def table(title, header, rows):
        print(f'\n### {title}\n')
        print('| ' + ' | '.join(header) + ' |')
        print('|' + '---|' * len(header))
        for r in rows:
            print('| ' + ' | '.join(str(c) for c in r) + ' |')

    table('Ognisko', ['Oddzial', 'Dni z ogniskiem', 'Sr. mnoznik zaru (dzien x seria)', 'Zar / dzien', 'Dni na poz. 3-4'],
          [[n,
            f"{100 * mean([l['lit'] / days for l in out[n]]):.0f}%",
            f"{mean([l['mult'] / days for l in out[n]]):.2f}",
            f"{mean([l['ember'] / days for l in out[n]]):.0f}",
            f"{100 * mean([(l['flame_days'][3] + l['flame_days'][4]) / days for l in out[n]]):.0f}%"] for n in names])

    table('Szlaki: odsetek wykonanych tygodni wg trudnosci', ['Oddzial', 'Latwa (0,85)', 'Srednia (1,10)', 'Trudna (1,35)', 'Piecz./tydz.'],
          [[n] + [f"{100 * mean([l['week_ok'][k] / l['weeks'] for l in out[n]]):.0f}%" for k in P['trail_levels']]
           + [f"{mean([l['stamps_total'] / l['weeks'] for l in out[n]]):.1f}"] for n in names])

    table('Schronisko: dzien, w ktorym wszystkie pomieszczenia osiagaja poziom', ['Oddzial', 'Poz. 1', 'Poz. 2', 'Poz. 3', 'Poz. 4', f'Suma poziomow w dniu {days} (z 25)'],
          [[n] + [fmt(mean([l['levels_day'].get(k) for l in out[n]])) + (f" ({100 * sum(1 for l in out[n] if k in l['levels_day']) / len(out[n]):.0f}% oddz.)" if True else '') for k in (1, 2, 3, 4)]
           + [fmt(mean([sum(l['rooms'].values()) for l in out[n]]), 1)] for n in names])

    table('Umiejetnosci oddzialowe i bonusy po N dniach (srednie efekty)', ['Oddzial', 'Dzien', 'Rozpalanie (zar)', 'Przewodnictwo (szlak)', 'Zaopatrzenie (znizka dk)', 'Kuchnia (wyplaty)', 'Sufit poz.'],
          [[n, d, f"+{100 * mean([l['eff_at'][d]['rozp'] for l in out[n] if d in l['eff_at']]):.1f}%",
            f"+{100 * mean([l['eff_at'][d]['przew'] for l in out[n] if d in l['eff_at']]):.1f}%",
            f"+{100 * mean([l['eff_at'][d]['zaop'] for l in out[n] if d in l['eff_at']]):.1f}%",
            f"+{100 * mean([l['eff_at'][d]['bonus_pct'] for l in out[n] if d in l['eff_at']]):.1f}%",
            f"{mean([l['eff_at'][d]['cap'] for l in out[n] if d in l['eff_at']]):.0f}"]
           for n in names for d in (90, 365) if d <= days])

    rows = []
    for n in names:
        size = out[n][0]['n']
        dep = mean([l['deposits'] for l in out[n]]) / size / days
        sk = mean([l['skill_dk'] for l in out[n]]) / size / days
        bo = mean([l['bonus_dk'] for l in out[n]]) / size / days
        inc = P['income_dk_day']
        rows.append([n, f'{dep:.1f}', f'{sk:.1f}', f'{bo:.1f}', f'{dep + sk - bo:+.1f}', f'{100 * (dep + sk) / inc:.1f}%', f'{100 * bo / inc:.2f}%'])
    table(f'Bilans dukatow na czlonka (dk/dzien, srednia z {days} dni; przychod gracza ok. {P["income_dk_day"]} dk/dzien)',
          ['Oddzial', 'Wplaty (sink)', 'Szkolenia (sink)', 'Bonus Kuchni (emisja)', 'Netto sink', 'Sink / przychod', 'Emisja / przychod'], rows)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--runs', type=int, default=200)
    ap.add_argument('--days', type=int, default=540)
    ap.add_argument('--seed', type=int, default=1)
    ap.add_argument('--markdown', action='store_true')
    ap.add_argument('--rozproszone', action='store_true', help='kazdy czlonek trenuje losowa kolejnosc umiejetnosci (domyslnie wspolna: rozpalanie, pierwsza pomoc, przewodnictwo, zaopatrzenie, kartografia)')
    ap.add_argument('--prog', type=float, help='nadpisz prog ogniska (np. 0.2)')
    a = ap.parse_args()
    if a.prog:
        P['threshold'] = a.prog
    focus = None if a.rozproszone else ['rozpalanie', 'pierwsza_pomoc', 'przewodnictwo', 'zaopatrzenie', 'kartografia']
    out = simulate(a.runs, a.days, a.seed, focus)
    print(f'Symulacja oddzialow: {a.runs} przebiegow x {a.days} dni, prog ogniska {int(P["threshold"] * 100)}%, szkolenia: {"rozproszone" if a.rozproszone else "wspolna kolejnosc"}')
    report(out, a.days, a.markdown)


if __name__ == '__main__':
    main()
