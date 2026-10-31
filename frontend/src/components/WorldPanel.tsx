import { useTranslation } from 'react-i18next';
import type { WorldResponse } from '../api/types';
import { formatCoins, formatCoinsFull, formatDuration } from '../format';
import { Icon, Sprout, type IconName } from './icons';

const SKILL_ICONS: Record<string, IconName> = { fitness: 'fitness', orientation: 'orientation', trade: 'trade' };

// Palety krain: niebo, wzgorza (blizsze/dalsze), woda/podloze, drzewa.
const PALETTES = [
  { sky: '#bfe3f5', far: '#9fcf7a', near: '#6ea34f', water: '#37b3c9', tree: '#2f6b3a' }, // 1 Nowy Poczatek
  { sky: '#b9d6c1', far: '#4f8a5c', near: '#2f6b3a', water: '#3a8f9a', tree: '#17482a' }, // 2 Las
  { sky: '#dbeaf6', far: '#bcd3e6', near: '#eef5fb', water: '#8fc4e0', tree: '#5d86a3' }, // 3 Tundra
  { sky: '#f7d9a0', far: '#e2b36b', near: '#c98f45', water: '#7fb5b0', tree: '#9a6a2c' }, // 4 Pustynia
  { sky: '#bfe8d4', far: '#2fa36b', near: '#177a4b', water: '#26b6a5', tree: '#0e5a38' }, // 5 Dzungla
];

/** Prosta ilustracja krainy w SVG - kolory zaleza od swiata. */
function WorldArt({ ordinal }: { ordinal: number }) {
  const c = PALETTES[(((ordinal - 1) % PALETTES.length) + PALETTES.length) % PALETTES.length]!;
  return (
    <svg className="world-art" viewBox="0 0 200 200" role="img" aria-hidden="true" preserveAspectRatio="xMidYMid slice">
      <rect width="200" height="200" fill={c.sky} />
      <circle cx="140" cy="46" r="20" fill="#ffd34d" />
      <ellipse cx="60" cy="52" rx="34" ry="10" fill="#fff" opacity="0.8" />
      <ellipse cx="82" cy="46" rx="22" ry="9" fill="#fff" opacity="0.8" />
      <path d="M0 110 Q50 70 100 100 T200 90 V200 H0Z" fill={c.far} />
      <path d="M0 140 Q60 105 120 130 T200 125 V200 H0Z" fill={c.near} />
      <rect x="0" y="158" width="200" height="22" fill={c.water} />
      <g fill={c.tree}>
        <path d="M36 150 l10 -34 l10 34z" /><path d="M96 152 l9 -30 l9 30z" /><path d="M150 150 l11 -38 l11 38z" />
      </g>
      <rect x="0" y="180" width="200" height="20" fill={c.tree} opacity="0.75" />
    </svg>
  );
}

interface Props {
  world: WorldResponse;
  coins: number;
  queueFull: boolean;
  busy: boolean;
  onAdvance: () => void;
}

export default function WorldPanel({ world, coins, queueFull, busy, onAdvance }: Props) {
  const { t } = useTranslation();
  if (!world.world || !world.current) return null;
  const next = world.next;
  const cantAfford = !!next && next.cost_cents > coins;

  let reason: string | undefined;
  if (next) {
    if (next.in_queue) reason = t('world.inQueue');
    else if (!next.requirements_met) reason = t('world.needSkills');
    else if (queueFull) reason = t('training.queueFull');
    else if (cantAfford) reason = t('training.noFunds');
  }

  return (
    <section className="panel trail-green world-panel">
      <h2>{t('world.title')}</h2>
      <p className="world-name">
        <small>{t('world.worldNo', { n: world.world.ordinal })}</small>
        <span className="world-title">{world.world.name}</span>
        <span className="chapter-line">{t('world.chapterNo', { n: world.current.ordinal })}: {world.current.name}</span>
      </p>

      <div className="world-body">
        <div className="world-main">
          <ol className="stages" aria-label={t('world.stages')}>
            {world.chapters.map((c) => (
              <li key={c.id} className={`stage stage-${c.status}`} title={`${c.ordinal}. ${c.name}`}>
                <Sprout stage={c.ordinal} />
              </li>
            ))}
          </ol>
          <p className="world-story">{world.current.story_text}</p>
        </div>

        <aside className="world-next">
          {next ? (
            <>
              <h3>{next.new_world ? t('world.newWorld', { name: next.world_name }) : t('world.newChapter')}</h3>
              <p className="next-name">{next.name}</p>
              <div className="line">{t('training.time')}: <strong>{formatDuration(next.duration_seconds)}</strong></div>
              <ul className="reqs">
                {next.requirements.map((r) => (
                  <li key={r.skill_id} className={r.met ? 'met' : 'unmet'} title={`${t(`skills.${r.code}`, { defaultValue: r.name })}: ${r.level} / ${r.required}`}>
                    <Icon name={SKILL_ICONS[r.code] ?? 'skills'} />
                    <b>{r.required}</b>
                    {!r.met && <small>{r.level}/{r.required}</small>}
                  </li>
                ))}
              </ul>
              <button type="button" className="btn" disabled={busy || !!reason} title={reason} onClick={onAdvance}>
                <span title={formatCoinsFull(next.cost_cents)}>{t('world.advance', { cost: formatCoins(next.cost_cents) })}</span>
              </button>
              {reason && <small className="world-reason">{reason}</small>}
            </>
          ) : (
            <p className="next-name">{t('world.last')}</p>
          )}
        </aside>

        <WorldArt ordinal={world.world.ordinal} />
      </div>
    </section>
  );
}
