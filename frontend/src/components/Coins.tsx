import { formatCoins, formatCoinsFull } from '../format';

/** Kwota w skroconej formie; po najechaniu pelna wartosc ze separatorami. */
export default function Coins({ cents }: { cents: number }) {
  return <span className="coins" title={formatCoinsFull(cents)}>{formatCoins(cents)}</span>;
}
