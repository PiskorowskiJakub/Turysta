#!/usr/bin/env python3
"""Symulacja Etapu 4: umiejetnosci oddzialowe (bez limitu poziomu), Spizarnia, Izba chorych, Zaopatrzenie, Kartografia.

Model Monte Carlo na dziennych krokach (dziala na zasadach z backend/src/Squad/SquadRules.php, Etap 1-3):
* czlonkowie maja regularnosc p (szansa aktywnego dnia), rotacja jak w simulate_squads.py,
* kazdy czlonek trenuje umiejetnosci oddzialowe w osobnym slocie: poziom n trwa 2 h x n i kosztuje 10,8 dk x n
  (15% ceny zwyklej umiejetnosci, 21,6 dk/h), postep rosnie o 24 h x p dziennie, kolejna umiejetnosc to ta o najnizszym poziomie,
* efekt = maksimum x L / (L + 40), z sredniej poziomow aktywnych czlonkow (>= 3 aktywne dni z 14), tylko przy >= MIN_ACTIVE aktywnych,
* schronisko jak w SquadShelter.php (jedna rozbudowa naraz, zawsze pomieszczenie o najnizszym poziomie).

Uruchomienie: python scripts/simulate_squad_skills.py [--runs 40] [--days 1825] [--markdown] [--seed 1]
"""
import argparse
import math
import random
import statistics

# ---- umiejetnosci oddzialowe ----
SKILLS = ['rozpalanie', 'przewodnictwo', 'kartografia', 'zaopatrzenie', 'pierwsza_pomoc']
SKILL_H_PER_LEVEL = 2.0          # czas poziomu n = 2 h x n
SKILL_DK_PER_HOUR = 3.24         # 15% z 21,6 dk/h
SKILL_HALF = 40                  # efekt = max x L / (L + 40)
SKILL_MAX = dict(rozpalanie=0.30, przewodnictwo=0.15, kartografia=0.12, zaopatrzenie=0.15)
MIN_ACTIVE = 3                   # bonusy dzialaja od 3 aktywnych czlonkow

# ---- schronisko ----
ROOMS = ['kuchnia', 'spizarnia', 'izba_chorych', 'mapownia', 'izba_pamieci']
ROOM_FACTOR = dict(kuchnia=1.0, spizarnia=1.0, izba_chorych=1.0, mapownia=1.0, izba_pamieci=0.6)
BASE_EMBER, BASE_STAMPS, BASE_COINS = 240, 6, 600
EXPONENT, STAMPS_EXPONENT = 1.2, 1.1
HOURS_PER_LEVEL = 12
DEPOSIT_DIVISOR, DEPOSIT_MIN = 60, 5
DEPOSIT_PROB = 0.4               # odsetek aktywnych, ktorzy wplacaja pelny dzienny limit
KITCHEN = (0.05, 7)
MAPPING = (0.25, 10)
PANTRY = (0.15, 10)              # Spizarnia: zniznka kosztu dukatowego szkolenia umiejetnosci oddzialowych
INFIRMARY_HALF = 8               # Izba chorych: skrocenie odnowienia tarczy
SHIELD_COOLDOWN_DAYS = 14        # odnowienie jednej tarczy bez ulepszen
SHIELD_COOLDOWN_MIN = 7
INCOME_DK_DAY = 1000

# ---- ognisko i szlak (jak w Etapach 1-2) ----
THRESHOLD = 0.30
FLAME_DAYS = [0, 3, 7, 14, 30]
FLAME_BONUS = [0.0, 0.05, 0.10, 0.15, 0.20]
DAY_TIERS = [(0.30, 1.0), (0.50, 1.25), (0.70, 1.5)]
EMBER_BASE = 10
PTS_PER_ACTIVE_DAY, TRAIL_PER_ACTIVE, TRAIL_MEDIUM = 10, 45, 1.10
TRAIL_THRESHOLDS = [40, 65, 80, 92]   # trudnosc srednia

PROFILES = {'aktywny': 0.95, 'zwykly': 0.80, 'okazjonalny': 0.45, 'rzadki': 0.12}
CHURN = dict(aktywny=0.0005, zwykly=0.001, okazjonalny=0.002, rzadki=0.004)
REFILL = 0.05
SQUADS = {
    'garstka (4, zgrana)': (4, dict(aktywny=0.5, zwykly=0.5)),
    'mały zgrany (8)': (8, dict(aktywny=0.5, zwykly=0.4, okazjonalny=0.1)),
    'typowy (15)': (15, dict(aktywny=0.2, zwykly=0.4, okazjonalny=0.3, rzadki=0.1)),
    'duży typowy (30)': (30, dict(aktywny=0.2, zwykly=0.3, okazjonalny=0.3, rzadki=0.2)),
    'duży senny (30)': (30, dict(aktywny=0.05, zwykly=0.15, okazjonalny=0.35, rzadki=0.45)),
}
CHECK = (90, 180, 365, 730, 1095, 1825)


def eff(mx, half, level):
    return mx * level / (level + half) if level > 0 else 0.0


def room_cost(room, n):
    f = ROOM_FACTOR[room]
    return (round(BASE_EMBER * n ** EXPONENT * f), max(1, math.ceil(BASE_STAMPS * n ** STAMPS_EXPONENT * f)), round(BASE_COINS * n ** EXPONENT * f))


def deposit_limit(levels):
    avg = sum(room_cost(r, levels[r] + 1)[2] for r in ROOMS) / len(ROOMS)
    return max(DEPOSIT_MIN, round(avg / DEPOSIT_DIVISOR))


def new_member(mix, rng):
    kind = rng.choices(list(mix), weights=list(mix.values()), k=1)[0]
    return dict(kind=kind, p=PROFILES[kind], lv={s: 0 for s in SKILLS}, prog=0.0, hist=[])


def stamps_for(pct):
    return sum(1 for t in TRAIL_THRESHOLDS if pct >= t)


def run(size, mix, days, rng, with_skills=True):
    members = [new_member(mix, rng) for _ in range(size)]
    rooms = {r: 0 for r in ROOMS}
    ember = stamps = pool = 0.0
    build = None            # (room, finish_day)
    streak = flame = 0
    charges, timer = 0, 0.0
    week_pts = 0.0
    log = dict(ember=0.0, ember_base=0.0, stamps=0.0, stamps_base=0.0, weeks=0, skill_dk=0.0, skill_dk_nodisc=0.0,
               deposits=0.0, bonus_dk=0.0, saves=0, lit=0, snap={}, difficulty_unlock={})
    for day in range(days):
        for m in members[:]:
            if len(members) > 1 and rng.random() < CHURN[m['kind']]:
                members.remove(m)
        for _ in range(max(0, size - len(members))):
            if rng.random() < REFILL:
                members.append(new_member(mix, rng))
        n = len(members)
        act = [m for m in members if rng.random() < m['p']]
        aset = {id(m) for m in act}
        for m in members:
            m['hist'].append(id(m) in aset)
            if len(m['hist']) > 14:
                m['hist'].pop(0)
        actives = [m for m in members if sum(m['hist']) >= 3]
        active_n = len(actives)
        ok = with_skills and active_n >= MIN_ACTIVE
        avg = {s: (statistics.fmean(m['lv'][s] for m in actives) if ok else 0.0) for s in SKILLS}
        e_roz = eff(SKILL_MAX['rozpalanie'], SKILL_HALF, avg['rozpalanie'])
        e_prz = eff(SKILL_MAX['przewodnictwo'], SKILL_HALF, avg['przewodnictwo'])
        e_kar = eff(SKILL_MAX['kartografia'], SKILL_HALF, avg['kartografia'])
        e_zap = eff(SKILL_MAX['zaopatrzenie'], SKILL_HALF, avg['zaopatrzenie'])
        for thr in (25, 60):
            if avg['kartografia'] >= thr and thr not in log['difficulty_unlock']:
                log['difficulty_unlock'][thr] = day + 1
        e_pf = avg['pierwsza_pomoc'] / (avg['pierwsza_pomoc'] + SKILL_HALF) if ok else 0.0
        e_inf = rooms['izba_chorych'] / (rooms['izba_chorych'] + INFIRMARY_HALF)
        disc = eff(*PANTRY, rooms['spizarnia'])
        # --- tarcza ogniska
        cap = (1 if (ok and avg['pierwsza_pomoc'] >= 1) else 0) + (1 if rooms['izba_chorych'] >= 3 else 0) + (1 if rooms['izba_chorych'] >= 15 else 0)
        cap = cap if ok else 0
        cooldown = max(SHIELD_COOLDOWN_MIN, SHIELD_COOLDOWN_DAYS * (1 - 0.5 * (1 - (1 - e_pf) * (1 - e_inf))))
        if charges < cap:
            timer -= 1
            if timer <= 0:
                charges += 1
                timer = cooldown
        else:
            timer = cooldown
        charges = min(charges, cap)
        # --- ognisko
        need = max(2, math.ceil(THRESHOLD * n))
        if len(act) >= need:
            log['lit'] += 1
            streak += 1
            flame = max(i for i, d in enumerate(FLAME_DAYS) if d <= streak)
            share = len(act) / n
            tier = max(mult for t, mult in DAY_TIERS if share >= t) if share >= DAY_TIERS[0][0] else 1.0
            g0 = EMBER_BASE * tier * (1 + FLAME_BONUS[flame])
            g = g0 * (1 + e_roz)
            ember += g
            log['ember'] += g
            log['ember_base'] += g0
        elif charges > 0:
            charges -= 1
            log['saves'] += 1
        else:
            flame = max(0, flame - 1)
            streak = FLAME_DAYS[flame]
        # --- szlak
        week_pts += len(act) * PTS_PER_ACTIVE_DAY * (1 + e_prz)
        if day % 7 == 6:
            base = TRAIL_PER_ACTIVE * active_n * TRAIL_MEDIUM
            pct = min(100.0, 100 * week_pts / base) if base else 0.0
            pct0 = min(100.0, pct / (1 + e_prz)) if base else 0.0
            sc = stamps_for(pct) if active_n >= 2 else 0
            raw = sc * (1 + eff(*MAPPING, rooms['mapownia'])) * (1 + e_kar)
            stamps += raw
            log['stamps'] += raw
            log['stamps_base'] += stamps_for(pct0) if active_n >= 2 else 0
            log['weeks'] += 1
            week_pts = 0.0
        # --- wplaty
        lim = deposit_limit(rooms) * (1 + e_zap)
        for m in act:
            if rng.random() < DEPOSIT_PROB:
                pool += lim
                log['deposits'] += lim
        # --- szkolenia oddzialowe
        for m in members:
            hours = 24.0 * m['p']
            while hours > 0:
                s = min(SKILLS, key=lambda k: (m['lv'][k], SKILLS.index(k)))
                lvl = m['lv'][s] + 1
                t = SKILL_H_PER_LEVEL * lvl
                take = min(hours, t - m['prog'])
                m['prog'] += take
                hours -= take
                if m['prog'] >= t - 1e-9:
                    cost = SKILL_H_PER_LEVEL * lvl * SKILL_DK_PER_HOUR
                    log['skill_dk_nodisc'] += cost
                    log['skill_dk'] += cost * (1 - disc)
                    m['lv'][s] = lvl
                    m['prog'] = 0.0
        # --- Kuchnia (emisja)
        kb = eff(*KITCHEN, rooms['kuchnia'])
        log['bonus_dk'] += sum(INCOME_DK_DAY * (0.25 if m['kind'] == 'rzadki' else 1.0) * kb for m in members)
        # --- schronisko
        if build and day >= build[1]:
            rooms[build[0]] += 1
            build = None
        if not build:
            r = min(ROOMS, key=lambda k: (rooms[k], ROOMS.index(k)))
            ne, ns, nd = room_cost(r, rooms[r] + 1)
            nd = round(nd * (1 - e_zap))
            if ember >= ne and stamps >= ns and pool >= nd:
                ember -= ne
                stamps -= ns
                pool -= nd
                build = (r, day + HOURS_PER_LEVEL * (rooms[r] + 1) / 24)
        if (day + 1) in CHECK:
            log['snap'][day + 1] = dict(
                avg={s: avg[s] for s in SKILLS}, roz=e_roz, prz=e_prz, kar=e_kar, zap=e_zap, pf=e_pf, inf=e_inf, disc=disc,
                rooms=dict(rooms), cap=cap, cooldown=cooldown, n=n, active=active_n, flame=flame,
                lvl_max=max((m['lv'][s] for m in members for s in SKILLS), default=0))
    log['member_days'] = days * size
    return log


def mean(v):
    v = [x for x in v if x is not None]
    return statistics.fmean(v) if v else 0.0


def pct(x, nd=1):
    return f'{100 * x:.{nd}f}%'.replace('.', ',')


def table(title, head, rows, md):
    print(f'\n### {title}\n')
    print('| ' + ' | '.join(head) + ' |')
    print('|' + '---|' * len(head))
    for r in rows:
        print('| ' + ' | '.join(str(c) for c in r) + ' |')


def main():
    global MIN_ACTIVE
    ap = argparse.ArgumentParser()
    ap.add_argument('--runs', type=int, default=40)
    ap.add_argument('--days', type=int, default=1825)
    ap.add_argument('--seed', type=int, default=1)
    ap.add_argument('--markdown', action='store_true')
    ap.add_argument('--min-aktywnych', type=int, default=MIN_ACTIVE)
    a = ap.parse_args()
    MIN_ACTIVE = a.min_aktywnych
    rng = random.Random(a.seed)
    out = {}
    for name, (size, mix) in SQUADS.items():
        out[name] = [run(size, mix, a.days, random.Random(rng.random()), True) for _ in range(a.runs)]
    base = {n: [run(SQUADS[n][0], SQUADS[n][1], a.days, random.Random(i), False) for i in range(a.runs)] for n in out}
    names = list(out)
    print(f'Symulacja etapu 4: {a.runs} przebiegów x {a.days} dni, model: poziom n = {SKILL_H_PER_LEVEL:g} h x n, {SKILL_DK_PER_HOUR} dk/h, '
          f'efekt L/(L+{SKILL_HALF}), maksima {SKILL_MAX}')
    days_show = [d for d in CHECK if d <= a.days]

    table('Średni poziom umiejętności oddziałowych (aktywni członkowie) i efekty',
          ['Oddział', 'Dzień', 'Śr. poziom', 'Rozpalanie (żar)', 'Przewodnictwo (szlak)', 'Kartografia (pieczątki)', 'Zaopatrzenie (dk)'],
          [[n, d,
            f"{mean([l['snap'][d]['avg']['rozpalanie'] for l in out[n]]):.1f}",
            '+' + pct(mean([l['snap'][d]['roz'] for l in out[n]])),
            '+' + pct(mean([l['snap'][d]['prz'] for l in out[n]])),
            '+' + pct(mean([l['snap'][d]['kar'] for l in out[n]])),
            '+' + pct(mean([l['snap'][d]['zap'] for l in out[n]]))]
           for n in names for d in days_show if d in (180, 365, 730, 1825)], a.markdown)

    table('Schronisko z umiejętnościami: suma poziomów pomieszczeń (5 pomieszczeń) i bonusy',
          ['Oddział', 'Dzień', 'Suma poziomów', 'Poz. Spiżarni', 'Zniżka szkoleń (Spiżarnia)', 'Poz. Izby chorych', 'Ładunki tarczy', 'Odnowienie tarczy (dni)'],
          [[n, d,
            f"{mean([sum(l['snap'][d]['rooms'].values()) for l in out[n]]):.1f}",
            f"{mean([l['snap'][d]['rooms']['spizarnia'] for l in out[n]]):.1f}",
            pct(mean([l['snap'][d]['disc'] for l in out[n]])),
            f"{mean([l['snap'][d]['rooms']['izba_chorych'] for l in out[n]]):.1f}",
            f"{mean([l['snap'][d]['cap'] for l in out[n]]):.1f}",
            f"{mean([l['snap'][d]['cooldown'] for l in out[n]]):.1f}"]
           for n in names for d in days_show if d in (365, 1825)], a.markdown)

    table('Tempo schroniska: suma poziomów 5 pomieszczeń bez umiejętności i z nimi, oraz odblokowanie nowych trudności (Kartografia, śr. poziom 25 i 60)',
          ['Oddział', 'Dzień', 'Suma poziomów bez', 'Suma poziomów z', 'Trudność 4 (dzień)', 'Trudność 5 (dzień)'],
          [[n, d,
            f"{mean([sum(l['snap'][d]['rooms'].values()) for l in base[n]]):.1f}",
            f"{mean([sum(l['snap'][d]['rooms'].values()) for l in out[n]]):.1f}",
            (f"{mean([l['difficulty_unlock'].get(25) for l in out[n]]):.0f}" if any(25 in l['difficulty_unlock'] for l in out[n]) else '-'),
            (f"{mean([l['difficulty_unlock'].get(60) for l in out[n]]):.0f}" if any(60 in l['difficulty_unlock'] for l in out[n]) else '-')]
           for n in names for d in days_show if d in (365, 730, 1825)], a.markdown)

    rows = []
    for n in names:
        ls = out[n]
        weeks = mean([l['weeks'] for l in ls])
        rows.append([n,
                     f"{mean([l['ember_base'] / a.days for l in ls]):.1f}",
                     f"{mean([l['ember'] / a.days for l in ls]):.1f}",
                     f"{mean([l['stamps_base'] / l['weeks'] for l in ls]):.2f}",
                     f"{mean([l['stamps'] / l['weeks'] for l in ls]):.2f}",
                     f"{mean([l['saves'] for l in ls]):.1f}"])
    table(f'Żar i pieczątki w całym okresie ({a.days} dni): bez umiejętności i z nimi',
          ['Oddział', 'Żar/dzień bez', 'Żar/dzień z', 'Pieczątek/tydz. bez (trasa średnia)', 'Pieczątek/tydz. z (+Mapownia, +Kartografia)', 'Uratowane dni tarczą'], rows, a.markdown)

    rows = []
    for n in names:
        ls = out[n]
        md = mean([l['member_days'] for l in ls])
        sk = mean([l['skill_dk'] / l['member_days'] for l in ls])
        sk0 = mean([l['skill_dk_nodisc'] / l['member_days'] for l in ls])
        dep = mean([l['deposits'] / l['member_days'] for l in ls])
        bo = mean([l['bonus_dk'] / l['member_days'] for l in ls])
        rows.append([n, f'{dep:.1f}', f'{sk:.1f}', f'{sk0:.1f}', f'{bo:.1f}', f'{dep + sk - bo:+.1f}', pct((dep + sk) / INCOME_DK_DAY), pct(bo / INCOME_DK_DAY, 2)])
    table(f'Bilans dukatów na członka (dk/dzień, średnia z {a.days} dni, przychód gracza ok. {INCOME_DK_DAY} dk/dzień)',
          ['Oddział', 'Wpłaty (sink)', 'Szkolenia oddz. (sink)', 'Szkolenia bez zniżki', 'Bonus Kuchni (emisja)', 'Netto sink', 'Sink / przychód', 'Emisja / przychód'], rows, a.markdown)

    # pojedynczy gracz: poziomy w czasie przy stalej regularnosci
    print('\n### Pojedynczy gracz (osobny slot, rozłożone na 5 umiejętności, bez Spiżarni)\n')
    print('| Regularność | Dzień | Poziom każdej umiejętności | Efekt Rozpalania | Koszt dzienny (dk) | Czas do poziomu 20 / 50 (dni) |')
    print('|---|---|---|---|---|---|')
    for name, p in PROFILES.items():
        hours_per_day = 24 * p
        def lvl_at(d):
            total = hours_per_day * d / 5
            return (-1 + math.sqrt(1 + 4 * total / SKILL_H_PER_LEVEL * 1.0)) / 2 * 1.0
        def days_to(L):
            return 5 * SKILL_H_PER_LEVEL * L * (L + 1) / 2 / hours_per_day
        for d in (365, 1825):
            L = (-1 + math.sqrt(1 + 4 * (hours_per_day * d / 5))) / 2
            print(f'| {name} ({int(p * 100)}%) | {d} | {L:.1f} | +{pct(eff(SKILL_MAX["rozpalanie"], SKILL_HALF, L))} | {hours_per_day * SKILL_DK_PER_HOUR:.0f} | {days_to(20):.0f} / {days_to(50):.0f} |')


if __name__ == '__main__':
    main()
