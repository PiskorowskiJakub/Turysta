const NBSP = '\u00a0';

/** Liczba calkowita z grupowaniem tysiecy (twarda spacja): 1234567 -> "1 234 567". */
function group(n: number): string {
  return String(Math.floor(n)).replace(/\B(?=(\d{3})+(?!\d))/g, NBSP);
}

/** Pelna kwota ze separatorami: 123456789 (setnych) -> "1 234 567,89". Pokazywana w dymku po najechaniu. */
export function formatCoinsFull(cents: number): string {
  const c = Math.max(0, Math.floor(cents));
  return `${group(Math.floor(c / 100))},${String(c % 100).padStart(2, '0')}`;
}

/** Liczba calkowita w skrocie, zawsze w dol: do 100 tys. ze spacjami, wyzej "123,4 tys.", "1,2 mln", "3,5 mld". */
function abbreviate(n: number): string {
  if (n < 100_000) return group(n);
  const units: [number, string][] = [[1_000_000_000, 'mld'], [1_000_000, 'mln'], [1_000, 'tys.']];
  for (const [size, name] of units) {
    if (n >= size) {
      const tenths = Math.floor((n * 10) / size);
      const whole = Math.floor(tenths / 10);
      const frac = tenths % 10;
      return `${frac === 0 ? group(whole) : `${group(whole)},${frac}`}${NBSP}${name}`;
    }
  }
  return group(n);
}

/**
 * Dukaty trzymamy w setnych czesciach (int). Wyswietlanie skrocone, zawsze w dol (nie pokazujemy wiecej, niz gracz ma):
 * do 100 dk dwa miejsca po przecinku, do 100 tys. dk calosci ze spacjami, wyzej "123,4 tys.", "1,2 mln", "3,5 mld".
 */
export function formatCoins(cents: number): string {
  const c = Math.max(0, Math.floor(cents));
  const dk = Math.floor(c / 100);
  if (dk < 100) return `${dk},${String(c % 100).padStart(2, '0')}`;
  return abbreviate(dk);
}

/** Punkty (liczby calkowite): do 100 tys. ze spacjami, wyzej "123,4 tys.", "1,2 mln", "3,5 mld". */
export function formatPoints(points: number): string {
  return abbreviate(Math.max(0, Math.floor(points)));
}

/** Pelna liczba punktow ze spacjami, pokazywana w dymku po najechaniu: 1234567 -> "1 234 567". */
export function formatPointsFull(points: number): string {
  return group(Math.max(0, Math.floor(points)));
}

/** Czas trwania jako g:mm:ss; dni (Nd) dopiero powyzej 24 godzin. */
export function formatDuration(totalSeconds: number): string {
  const s = Math.max(0, Math.floor(totalSeconds));
  const d = s > 86400 ? Math.floor(s / 86400) : 0;
  const h = Math.floor((s - d * 86400) / 3600);
  const m = Math.floor((s % 3600) / 60);
  const sec = s % 60;
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d > 0 ? `${d} d ` : ''}${h}:${pad(m)}:${pad(sec)}`;
}

const MONTHS = ['sty', 'lut', 'mar', 'kwi', 'maj', 'cze', 'lip', 'sie', 'wrz', 'paz', 'lis', 'gru'];

/** Godzina zakonczenia w czasie lokalnym: "dziś 14:32", "jutro 03:10" albo "2 lis 14:32". */
export function formatEndTime(endMs: number, nowMs: number): string {
  const end = new Date(endMs);
  const startOfDay = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
  const dayDiff = Math.round((startOfDay(end) - startOfDay(new Date(nowMs))) / 86400000);
  const hm = `${String(end.getHours()).padStart(2, '0')}:${String(end.getMinutes()).padStart(2, '0')}`;
  if (dayDiff === 0) return `dziś ${hm}`;
  if (dayDiff === 1) return `jutro ${hm}`;
  return `${end.getDate()} ${MONTHS[end.getMonth()]} ${hm}`;
}

/** "1 dzień", "5 dni". */
/** Numer dnia gry liczony od dnia zalozenia konta, np. "4. dzień gry". */
export function formatGameDay(n: number): string {
  return `${n}. dzień gry`;
}

export function formatDays(n: number): string {
  return `${n} ${n === 1 ? 'dzień' : 'dni'}`;
}

/** Data w formacie dd.mm.rrrr w czasie lokalnym. */
export function formatDate(iso: string): string {
  const d = new Date(iso);
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
}

/** Data i godzina w strefie przegladarki: DD.MM.RRRR GG:MM. */
export function formatDateTime(iso: string): string {
  const d = new Date(iso);
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${formatDate(iso)} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/** Procent z przecinkiem dziesietnym, bez zbednych zer (np. 0,63 albo 12,5). */
export function formatPercent(value: number): string {
  return value.toLocaleString('pl-PL', { maximumFractionDigits: 2 });
}
