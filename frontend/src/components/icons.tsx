export type IconName =
  | 'home' | 'profile' | 'skills' | 'job' | 'logout' | 'login' | 'register'
  | 'coins' | 'ticket' | 'points' | 'fitness' | 'orientation' | 'trade'
  | 'medal' | 'market' | 'hostel' | 'expedition';

const PATHS: Record<IconName, string> = {
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
  medal: 'M8 3h8l-2 6h-4zM12 9a6 6 0 1 0 0.01 0M12 13l1 2 2 .3-1.5 1.4.4 2.1-1.9-1-1.9 1 .4-2.1L9 15.3l2-.3z',
  market: 'M3 9l2-5h14l2 5M3 9h18M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0M5 12v8h14v-8M9 20v-5h6v5',
  hostel: 'M3 11l9-8 9 8M5 10v10h14V10M10 20v-6h4v6M16 6V4h2v4',
  expedition: 'M3 20l6-11 4 6 3-4 5 9zM12 3v4M10 5h4',
};

export function Icon({ name }: { name: IconName }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d={PATHS[name]} />
    </svg>
  );
}
