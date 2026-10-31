const coins = new Intl.NumberFormat('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/** Monety trzymamy w setnych czesciach (int); wyswietlamy z dwoma miejscami po przecinku. */
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
