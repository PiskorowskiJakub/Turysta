const STOPS = [0, 25, 50, 75, 100];

/** Pasek postepu w formie szlaku: kropkowana trasa, punkty etapow i znacznik z procentem. */
export default function ProgressBar({ value }: { value: number }) {
  const pct = Math.max(0, Math.min(100, Math.round(value * 100)));
  return (
    <div className="bar" role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}>
      <div className="bar-fill" style={{ width: `${pct}%` }} />
      {STOPS.map((s) => (
        <i key={s} className={'bar-stop' + (pct >= s ? ' done' : '')} style={{ left: `${s}%` }} />
      ))}
      <span className="bar-pin" style={{ left: `${pct}%` }}>{pct}%</span>
    </div>
  );
}
