const coins = new Intl.NumberFormat('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/** Monety trzymamy w setnych czesciach (int); wyswietlamy z dwoma miejscami po przecinku. */
export function formatCoins(cents: number): string {
  return coins.format(cents / 100);
}
