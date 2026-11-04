export type IconName =
  | 'home' | 'profile' | 'skills' | 'job' | 'logout' | 'login' | 'register'
  | 'coins' | 'ticket' | 'points' | 'fitness' | 'orientation' | 'trade'
  | 'sprout' | 'rank' | 'medal' | 'market' | 'hostel' | 'expedition' | 'lock' | 'team' | 'check' | 'help' | 'gift' | 'admin' | 'eye' | 'eyeOff' | 'book';

const PATHS: Record<IconName, string> = {
  book: 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5v14zM4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5',
  home: 'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10',
  profile: 'M3 6h18v12H3zM8 11a2 2 0 1 0 0.01 0M14 10h4M14 14h4',
  skills: 'M9 3h6M10 3v6L5 19a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-10V3',
  job: 'M3 7h18v13H3zM9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18',
  logout: 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
  login: 'M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3',
  register: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 3a4 4 0 1 0 0.01 0M19 8v6M22 11h-6',
  coins: 'M12 4c4.4 0 8 1.1 8 2.5S16.4 9 12 9 4 7.9 4 6.5 7.6 4 12 4zM4 6.5v5C4 12.9 7.6 14 12 14s8-1.1 8-2.5v-5M4 11.5v5C4 17.9 7.6 19 12 19s8-1.1 8-2.5v-5',
  ticket: 'M3 8a2 2 0 0 0 0 4v4h18v-4a2 2 0 0 1 0-4V6H3zM9 6v12',
  points: 'M5 20V10M12 20V4M19 20v-7',
  fitness: 'M3 20l6-11 4 6 3-4 5 9zM16 4v5l4-1.5z',
  orientation: 'M12 3a9 9 0 1 0 0.01 0M15.5 8.5l-2 5-5 2 2-5z',
  trade: 'M12 3v18M5 21h14M5 7h14M7 7l-3 7a3.5 3.5 0 0 0 6 0zM17 7l-3 7a3.5 3.5 0 0 0 6 0z',
  sprout: 'M12 21v-9M12 13C12 9 9 7 5 7c0 4 3 6 7 6M12 15c0-3 2-5 6-5 0 3-2 5-6 5',
  rank: 'M5 21V4M5 4h12l-2 4 2 4H5',
  medal: 'M8 3h8l-2 6h-4zM12 9a6 6 0 1 0 0.01 0M12 13l1 2 2 .3-1.5 1.4.4 2.1-1.9-1-1.9 1 .4-2.1L9 15.3l2-.3z',
  market: 'M3 9l2-5h14l2 5M3 9h18M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0M5 12v8h14v-8M9 20v-5h6v5',
  hostel: 'M3 11l9-8 9 8M5 10v10h14V10M10 20v-6h4v6M16 6V4h2v4',
  expedition: 'M3 20l6-11 4 6 3-4 5 9zM12 3v4M10 5h4',
  lock: 'M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3',
  check: 'M5 13l4 4L19 7',
  help: 'M12 3a9 9 0 1 0 .01 0M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1 1-1 1.7M12 17h.01',
  gift: 'M3 9h18v4H3zM5 13v8h14v-8M12 9v12M12 9C9 9 8 4 11 4c1.5 0 1 5 1 5zM12 9c3 0 4-5 1-5-1.5 0-1 5-1 5z',
  admin: 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6zM9 12l2 2 4-4',
  eye: 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 9a3 3 0 1 0 .01 0',
  eyeOff: 'M3 3l18 18M10.6 5.1A9.8 9.8 0 0 1 12 5c6 0 10 7 10 7a17 17 0 0 1-3.2 4M6.6 6.6A17 17 0 0 0 2 12s4 7 10 7a9.7 9.7 0 0 0 4-.9M9.9 9.9a3 3 0 0 0 4.2 4.2',
  team: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 3a4 4 0 1 0 0.01 0M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8',
};

export function Icon({ name }: { name: IconName }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d={PATHS[name]} />
    </svg>
  );
}

/** Ikony umiejetnosci swiatow (klucz = kod umiejetnosci). */
const WORLD_SKILL_PATHS: Record<string, string> = {
  special_streak: 'M12 3c1 3 4 4 4 8a4 4 0 0 1-8 0c0-1.5.7-2.5 1.5-3.5.2 1 .8 1.6 1.5 1.8C11 7.5 11.5 5 12 3zM8 21h8',
  special_activity: 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4M9 15l2 2 4-4',
  special_friends: 'M4 20v-1a5 5 0 0 1 5-5h2a5 5 0 0 1 5 5v1M6.5 8a3.5 3.5 0 1 0 7 0a3.5 3.5 0 1 0-7 0M16 4.3a3.5 3.5 0 0 1 0 7.4M20 20v-1a5 5 0 0 0-3-4.6',
  special_referrals: 'M15 20v-1a5 5 0 0 0-5-5H8a5 5 0 0 0-5 5v1M9 11a3.5 3.5 0 1 0 0-7a3.5 3.5 0 0 0 0 7zM19 8v6M16 11h6',
  special_trails: 'M4 20c3-1 4-4 8-4s5-3 8-4M5 8l3-4 3 4M8 4v8M15 6h5l-2 2.5L20 11h-5z',
  w1_packing: 'M9 7V5a3 3 0 0 1 6 0v2M6 10a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3v10a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zM9 15h6v4H9z',
  w1_campfire: 'M12 3c1 3 4 4 4 8a4 4 0 0 1-8 0c0-1.5.7-2.5 1.5-3.5.2 1 .8 1.6 1.5 1.8C11 7.5 11.5 5 12 3zM4 21l16-4M20 21L4 17',
  w1_trail_signs: 'M12 3v18M8 21h8M6 6h10l2 2-2 2H6zM18 13H8l-2 2 2 2h10z',
  w1_first_aid: 'M4 7h16v13H4zM9 7V4h6v3M12 11v5M9.5 13.5h5',
  w1_cooking: 'M5 11h14v7a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3zM3 11h18M9 8c0-1.5 1.5-1.5 1.5-3M14 8c0-1.5 1.5-1.5 1.5-3',
  w1_tent: 'M2 20L12 4l10 16zM9.5 20L12 14l2.5 6',
  w2_mushrooms: 'M4 12a8 7 0 0 1 16 0zM10 12v6a2 2 0 0 0 4 0v-6M8 9h.01M13 8h.01',
  w2_herbs: 'M5 17a3 3 0 1 0 6 0a3 3 0 1 0-6 0M13 17a3 3 0 1 0 6 0a3 3 0 1 0-6 0M9 10a3 3 0 1 0 6 0a3 3 0 1 0-6 0M12 7V4M12 4c2 0 4 1 4 3-2 0-4-1-4-3',
  w2_tracking: 'M12 12c-3 0-5 3-5 5.5 0 2 1.5 2.5 3 2.5 1 0 1.5-.5 2-.5s1 .5 2 .5c1.5 0 3-.5 3-2.5 0-2.5-2-5.5-5-5.5zM4.7 9.5a1.3 1.3 0 1 0 2.6 0a1.3 1.3 0 1 0-2.6 0M8.2 5.5a1.3 1.3 0 1 0 2.6 0a1.3 1.3 0 1 0-2.6 0M13.2 5.5a1.3 1.3 0 1 0 2.6 0a1.3 1.3 0 1 0-2.6 0M16.7 9.5a1.3 1.3 0 1 0 2.6 0a1.3 1.3 0 1 0-2.6 0',
  w2_shelter: 'M3 21h18M5 21L13 4M19 21L11 4M8 14h8',
  w2_fishing: 'M2 12c3-5 9-6 13-3l5-3v12l-5-3c-4 3-10 2-13-3zM7 11h.01',
  w2_night_march: 'M20 14a8 8 0 1 1-9-10 6.5 6.5 0 0 0 9 10zM17 4v3M15.5 5.5h3',
  w3_layers: 'M12 3L3 8l9 5 9-5zM3 12l9 5 9-5M3 16l9 5 9-5',
  w3_snow_shelter: 'M3 20h18M4 20a8 8 0 0 1 16 0M9 20v-3a3 3 0 0 1 6 0v3M5 14h14',
  w3_snowshoes: 'M12 3c3 0 5 3 5 7s-2 8-5 8-5-4-5-8 2-7 5-7zM12 3v15M7.5 8h9M7 12h10M8 15.5h8M12 18v3',
  w3_dog_sled: 'M4 7v7h12V7zM2 18h16a4 4 0 0 0 4-4M6 14v4M14 14v4',
  w3_ice_fishing: 'M4 17a8 3 0 1 0 16 0a8 3 0 1 0-16 0M12 3v9a2.5 2.5 0 1 1-2.5-2.5',
  w3_whiteout: 'M12 2v20M4 7l16 10M20 7L4 17M9 4l3 3 3-3M9 20l3-3 3 3',
  w4_water: 'M12 3C8 8 5 11 5 15a7 7 0 0 0 14 0c0-4-3-7-7-12zM9 15a3 3 0 0 0 3 3',
  w4_camel: 'M3 8l3-1.5 1.5 2.5L8 14h11M8 14c0-4 1-7 3-7 1.5 0 2 3 2.5 3.5C14 9 15 6.5 16.5 6.5 18.5 6.5 19 11 19 14M9 14v7M18 14v7M13 14v5',
  w4_sun_protection: 'M12 8a4 4 0 1 0 .01 0M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4',
  w4_star_navigation: 'M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.5 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z',
  w4_bazaar: 'M4 8h15M15 4l4 4-4 4M20 16H5M9 12l-4 4 4 4',
  w4_sandstorm: 'M3 8h10a3 3 0 1 0-3-3M3 12h15a3 3 0 1 1-3 3M3 16h7a2.5 2.5 0 1 1-2.5 2.5',
  w5_machete: 'M4 20l4-4M7 13l4 4M9.5 14.5L19 5c1.5-1.5 2.5-.5 2 1.5-1 3-4 7-9 10z',
  w5_liana_climbing: 'M12 2c-3 3 3 5 0 8s3 5 0 8M12 18v4M12 6c3-1 5 0 6 2-3 1-5 0-6-2M12 14c-3-1-5 0-6 2 3 1 5 0 6-2',
  w5_edible_plants: 'M12 8c-3-2-7 0-7 5 0 4 3 8 5 8 1 0 1-1 2-1s1 1 2 1c2 0 5-4 5-8 0-5-4-7-7-5zM12 8c0-2 1-4 3-5',
  w5_tropical_health: 'M12 21s-8-5-8-11a4.5 4.5 0 0 1 8-2.5A4.5 4.5 0 0 1 20 10c0 6-8 11-8 11z',
  w5_canoe: 'M2 15h20c-1.5 4-5 5-10 5s-8.5-1-10-5zM8 5l7 10M6.5 3.5l3-1 1.5 2.5-2.5 1.5z',
  w5_hammock: 'M3 4v16M21 4v16M3 9c3 10 15 10 18 0',
};

const BASE_SKILL_ICONS: Record<string, IconName> = { fitness: 'fitness', orientation: 'orientation', trade: 'trade' };

/** Ikona umiejetnosci: wlasna dla kazdej umiejetnosci swiata, a dla pozostalych ogolna. */
export function SkillIcon({ code }: { code: string }) {
  const own = WORLD_SKILL_PATHS[code];
  if (!own) return <Icon name={BASE_SKILL_ICONS[code] ?? 'skills'} />;
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d={own} />
    </svg>
  );
}

/**
 * Roslinka z 10 stopniami rozwoju: ziarno -> kielek -> lodyga z lisciami -> pak -> kwiat.
 * Stopien odpowiada numerowi rozdzialu w swiecie (1-10).
 */
export function Sprout({ stage }: { stage: number }) {
  const s = Math.max(1, Math.min(10, Math.round(stage)));
  const base = 21;
  const h = s === 1 ? 0 : 2.5 + (s - 1) * 1.9; // wysokosc lodygi
  const top = base - h;
  const pairs = s <= 2 ? 0 : Math.min(4, Math.floor((s - 1) / 2));
  const leaves = [];
  for (let i = 0; i < pairs; i += 1) {
    const y = base - h * (0.3 + i * 0.22);
    const size = 3.2 + i * 0.5;
    leaves.push(
      <path key={`l${i}`} d={`M12 ${y} q${-size - 1} ${-0.5} ${-size - 1.5} ${-size} q${size} 0 ${size + 1.5} ${size}z`} fill="currentColor" fillOpacity="0.85" />,
      <path key={`r${i}`} d={`M12 ${y - 1} q${size + 1} ${-0.5} ${size + 1.5} ${-size} q${-size} 0 ${-size - 1.5} ${size}z`} fill="currentColor" fillOpacity="0.85" />,
    );
  }
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M5 21h14" />
      {s === 1 && <circle cx="12" cy="19" r="1.6" fill="currentColor" />}
      {s >= 2 && <path d={`M12 ${base}V${top}`} />}
      {s === 2 && <path d={`M12 ${top} q-3 0 -3.5 -3 q3 0 3.5 3z`} fill="currentColor" />}
      {leaves}
      {s >= 3 && s <= 8 && <path d={`M12 ${top} q-2.6 -1 -2.6 -3.8 q2.8 0.2 2.6 3.8z`} fill="currentColor" />}
      {s === 9 && <ellipse cx="12" cy={top - 2} rx="2" ry="2.8" fill="currentColor" />}
      {s === 10 && (
        <g fill="currentColor">
          <circle cx="12" cy={top - 3} r="1.6" />
          <circle cx="9.6" cy={top - 1.2} r="1.6" /><circle cx="14.4" cy={top - 1.2} r="1.6" />
          <circle cx="10.4" cy={top - 4.6} r="1.6" /><circle cx="13.6" cy={top - 4.6} r="1.6" />
        </g>
      )}
    </svg>
  );
}
