export interface BadgeTier {
  name: string;
  min: number;
}

// Odznaki umiejetnosci bez konca: na start 3 z wysokimi progami. Slot 4 i 5 sa zarezerwowane
// (kolor w CSS jest gotowy) - wlaczamy je, wpisujac 'min' (null = jeszcze nieaktywna). Maksymalnie 5.
const TIERS: { name: string; min: number | null }[] = [
  { name: 'bronze', min: 50 },
  { name: 'silver', min: 100 },
  { name: 'gold', min: 200 },
  { name: 'diamond', min: null },
  { name: 'ruby', min: null },
];
const ACTIVE_TIERS = TIERS.filter((t): t is BadgeTier => t.min !== null);

/**
 * Odznaki umiejetnosci. Bez limitu poziomow (bazowe): progi z listy wyzej.
 * Z limitem (swiaty): 3 odznaki - brazowa, srebrna i zlota - na 1/3, 2/3 i 100% limitu.
 */
export function tiersFor(maxLevel: number | null): BadgeTier[] {
  if (maxLevel === null) return ACTIVE_TIERS;
  return [
    { name: 'bronze', min: Math.ceil(maxLevel / 3) },
    { name: 'silver', min: Math.ceil((maxLevel * 2) / 3) },
    { name: 'gold', min: maxLevel },
  ];
}

/** Odznaki specjalne (zalezne od statystyk gracza): stopnie i progi w dniach. */
export interface SpecialBadge {
  code: string;
  tiers: BadgeTier[];
}

export const SPECIAL_BADGES: SpecialBadge[] = [
  {
    code: 'special_streak',
    tiers: [
      { name: 'bronze', min: 7 },
      { name: 'silver', min: 14 },
      { name: 'gold', min: 30 },
      { name: 'diamond', min: 60 },
      { name: 'ruby', min: 90 },
    ],
  },
  {
    code: 'special_activity',
    tiers: [
      { name: 'bronze', min: 30 },
      { name: 'silver', min: 100 },
      { name: 'gold', min: 365 },
    ],
  },
  {
    code: 'special_friends',
    tiers: [
      { name: 'bronze', min: 1 },
      { name: 'silver', min: 5 },
      { name: 'gold', min: 10 },
      { name: 'diamond', min: 25 },
      { name: 'ruby', min: 50 },
    ],
  },
  {
    code: 'special_referrals',
    tiers: [
      { name: 'bronze', min: 1 },
      { name: 'silver', min: 3 },
      { name: 'gold', min: 5 },
      { name: 'diamond', min: 10 },
      { name: 'ruby', min: 25 },
    ],
  },
  {
    code: 'special_trails',
    tiers: [
      { name: 'bronze', min: 4 },
      { name: 'silver', min: 12 },
      { name: 'gold', min: 26 },
      { name: 'diamond', min: 52 },
      { name: 'ruby', min: 104 },
    ],
  },
];

/** Kolejnosc stopni od najnizszego; sluzy do wyboru najwyzszej zdobytej odznaki. */
export const TIER_ORDER = ['bronze', 'silver', 'gold', 'diamond', 'ruby', 'amethyst', 'obsidian', 'aurora'];

/** Nazwa stopnia (klucz tlumaczen i klasa CSS) dla numeru stopnia 1..n; poza zakresem zwraca najnizszy. */
export function tierName(n: number): string {
  return TIER_ORDER[n - 1] ?? TIER_ORDER[0];
}
