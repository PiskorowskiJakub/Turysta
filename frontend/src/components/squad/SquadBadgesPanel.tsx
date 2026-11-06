import { useTranslation } from 'react-i18next';
import type { SquadBadge } from '../../api/types';
import { formatDate } from '../../format';
import { Icon } from '../icons';

interface Pick {
  selected: string[];
  onToggle: (code: string) => void;
  full: boolean;
}

/**
 * Odznaki oddzialu: znaczki w stopniach brazu..rubinu; z postepem do nastepnego stopnia, gdy `progress`.
 * `featured` to odznaki z gabloty (pokazywane wyzej i wiekszym ukladem), `pick` zamienia znaczki w pola wyboru.
 */
export function SquadBadgeSquares({ badges, progress = false, featured = [], pick }: { badges: SquadBadge[]; progress?: boolean; featured?: string[]; pick?: Pick }) {
  const { t } = useTranslation();
  const shown = progress ? badges : badges.filter((b) => b.tier > 0);

  const one = (b: SquadBadge) => {
    const name = t(`squad.badges.names.${b.code}`);
    const tip = b.tier_name
      ? `${t(`training.tier.${b.tier_name}`)}: ${t(`squad.badges.need.${b.code}`, { count: b.tiers[b.tier - 1] })}`
      : t(`squad.badges.need.${b.code}`, { count: b.tiers[0] });
    const chosen = pick?.selected.includes(b.code) ?? false;
    const inner = (
      <>
        {chosen && <span className="badge-sq-check"><Icon name="check" /></span>}
        <div className={`badge-sq-icon${b.tier_name ? ` tier-${b.tier_name}` : ''}`}><Icon name="medal" /></div>
        <span className="badge-sq-name">{name}</span>
        <span className="badge-sq-date">{b.earned_at ? formatDate(b.earned_at) : t('squad.badges.notYet')}</span>
        {progress && (
          <span className="badge-sq-date">
            {t(`squad.badges.value.${b.code}`, { count: b.value })}
            {b.next_min !== null && ` · ${t('squad.badges.next', { n: b.next_min })}`}
          </span>
        )}
      </>
    );
    const cls = 'badge-sq' + (b.tier === 0 ? ' locked' : '') + (featured.includes(b.code) ? ' featured' : '');
    if (pick) {
      return (
        <button key={b.code} type="button" className={cls + (chosen ? ' selected' : '')} disabled={!chosen && pick.full} aria-pressed={chosen} data-tip={tip} onClick={() => pick.onToggle(b.code)}>
          {inner}
        </button>
      );
    }
    return <div key={b.code} className={cls} tabIndex={0} data-tip={tip} aria-label={`${name}. ${tip}`}>{inner}</div>;
  };

  const top = featured.map((c) => shown.find((b) => b.code === c)).filter((b): b is SquadBadge => !!b);
  const rest = shown.filter((b) => !featured.includes(b.code) || pick);
  return (
    <>
      {!pick && top.length > 0 && (
        <>
          <div className="badge-squares badge-showcase">{top.map(one)}</div>
          {rest.length > 0 && <div className="badge-squares badge-rest">{rest.map(one)}</div>}
        </>
      )}
      {(pick || top.length === 0) && <div className="badge-squares">{(pick ? shown : rest).map(one)}</div>}
    </>
  );
}

/** Karta Odznaki w Moim oddziale. */
export default function SquadBadgesPanel({ badges, featured = [] }: { badges: SquadBadge[]; featured?: string[] }) {
  const { t } = useTranslation();
  return (
    <section className="panel">
      <h2>{t('squad.badges.title')}</h2>
      <p className="muted">{t('squad.badges.intro')}</p>
      <SquadBadgeSquares badges={badges} progress featured={featured} />
    </section>
  );
}
