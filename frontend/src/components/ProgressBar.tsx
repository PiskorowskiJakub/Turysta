export default function ProgressBar({ value }: { value: number }) {
  const pct = Math.max(0, Math.min(100, Math.round(value * 100)));
  const low = pct < 20;
  return (
    <div className={'bar' + (low ? ' low' : '')} role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}>
      <div className="bar-fill" style={{ width: `${pct}%` }}>
        <span>{pct}%</span>
      </div>
    </div>
  );
}
