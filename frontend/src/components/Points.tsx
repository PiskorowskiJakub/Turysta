import { formatPoints, formatPointsFull } from '../format';

/** Punkty w skroconej formie; po najechaniu pelna wartosc ze separatorami. */
export default function Points({ value }: { value: number }) {
  return <span className="points-value" title={formatPointsFull(value)}>{formatPoints(value)}</span>;
}
