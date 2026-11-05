#!/usr/bin/env python3
"""Szybka symulacja tempa rozbudowy schroniska oddzialu (Etap 3).

Model: oddzial rozbudowuje pomieszczenia po kolei, zawsze to o najnizszym poziomie. Skladniki zbieraja sie rownolegle,
wiec czas jednej rozbudowy to czas zebrania najwolniejszego skladnika (żar, pieczatki albo dukaty); czas budowy (12 h x poziom)
zwykle sie w nim miesci. Koszty i limity wplat sa takie same jak w backend/src/Squad/SquadRules.php i SquadShelter.php.

Uruchomienie: python scripts/simulate_shelter.py [--markdown]
"""
import argparse
import math

BASE_EMBER, BASE_STAMPS, BASE_COINS = 240, 6, 600
EXPONENT, STAMPS_EXPONENT = 1.2, 1.1
HOURS_PER_LEVEL = 12
DEPOSIT_DIVISOR, DEPOSIT_MIN = 60, 5
ROOM_FACTORS = [1.0, 1.0, 1.0, 1.0, 0.6]  # Kuchnia, Spizarnia, Izba chorych, Mapownia, Izba pamieci

# nazwa: (żar na dzien, pieczatki na tydzien, liczba wplacajacych dziennie do limitu)
PROFILES = {
    '2 osoby': (12, 3.0, 1.2),
    '4 osoby': (19, 4.0, 2.0),
    '8 osób': (20, 4.5, 3.0),
    '15 osób': (17, 4.0, 4.5),
    '30 osób': (15, 3.8, 9.0),
}


def cost(level, factor=1.0):
    return (
        round(BASE_EMBER * level ** EXPONENT * factor),
        max(1, math.ceil(BASE_STAMPS * level ** STAMPS_EXPONENT * factor)),
        round(BASE_COINS * level ** EXPONENT * factor),
    )


def deposit_limit(levels):
    avg = sum(cost(l + 1, f)[2] for l, f in zip(levels, ROOM_FACTORS)) / len(levels)
    return max(DEPOSIT_MIN, round(avg / DEPOSIT_DIVISOR))


def simulate(ember_day, stamps_week, depositors):
    """Zwraca dzien, w ktorym wszystkie pomieszczenia osiagaja poziomy 1-5, i liczbe rozbudow ograniczonych zarem / pieczatkami / dukatami."""
    levels = [0] * 5
    day = 0.0
    marks = {}
    bottlenecks = {'żar': 0, 'pieczatki': 0, 'dukaty': 0}
    for step in range(1, 26):
        i = min(range(5), key=lambda k: (levels[k], k))
        n = levels[i] + 1
        e, s, d = cost(n, ROOM_FACTORS[i])
        times = {'żar': e / ember_day, 'pieczatki': s / (stamps_week / 7), 'dukaty': d / (depositors * deposit_limit(levels))}
        key = max(times, key=times.get)
        bottlenecks[key] += 1
        day += max(times[key], n * HOURS_PER_LEVEL / 24)
        levels[i] = n
        if step % 5 == 0:
            marks[step // 5] = round(day)
    return marks, bottlenecks


def human(days):
    if days < 90:
        return f"{days / 30:.1f}".replace('.', ',') + ' mies.'
    if days < 730:
        return f'{days / 30:.0f} mies.'
    return f"{days / 365:.1f}".replace('.', ',') + ' roku'


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--markdown', action='store_true')
    args = ap.parse_args()
    head = ['Oddział'] + [f'Poziom {n}' for n in range(1, 6)] + ['Wąskie gardło (żar/pieczątki/dukaty)']
    rows = []
    for name, (emb, stw, dep) in PROFILES.items():
        marks, neck = simulate(emb, stw, dep)
        rows.append([name] + [f'{marks[n]} dni ({human(marks[n])})' for n in range(1, 6)] + [f"{neck['żar']}/{neck['pieczatki']}/{neck['dukaty']}"])
    if args.markdown:
        print('| ' + ' | '.join(head) + ' |')
        print('| ' + ' | '.join(['---'] * len(head)) + ' |')
        for r in rows:
            print('| ' + ' | '.join(r) + ' |')
    else:
        for r in rows:
            print(r[0].ljust(10), ' | '.join(r[1:]))


if __name__ == '__main__':
    main()
