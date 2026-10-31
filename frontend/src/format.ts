const coins = new Intl.NumberFormat('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/** Dukaty (monety) trzymamy w setnych czesciach (int); wyswietlamy z dwoma miejscami po przecinku. */
export function formatCoins(cents: number): string {
  return coins.format(cents / 100);
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
export function formatDays(n: number): string {
  return `${n} ${n === 1 ? 'dzień' : 'dni'}`;
}

/** Data w formacie dd.mm.rrrr w czasie lokalnym. */
export function formatDate(iso: string): string {
  const d = new Date(iso);
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
}
