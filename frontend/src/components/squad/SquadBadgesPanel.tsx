import { useTranslation } from 'react-i18next';
import type { SquadBadge } from '../../api/types';
import { formatDate } from '../../format';
import { Icon } from '../icons';

/** Odznaki oddzialu: okragle znaczki w stopniach brazu..rubinu; z postepem do nastepnego stopnia, gdy `progress`. */
export function SquadBadgeSquares({ badges, progress = false }: { badges: SquadBadge[]; progress?: boolean }) {
  const { t } = useTranslation();
  const shown = progress ? badges : badges.filter((b) => b.tier > 0);
  return (
    <div className="badge-squares">
      {shown.map((b) => {
        const name = t(`squad.badges.names.${b.code}`);
        const tip = b.tier_name
          ? `${t(`training.tier.${b.tier_name}`)}: ${t(`squad.badges.need.${b.code}`, { count: b.tiers[b.tier - 1] })}`
          : t(`squad.badges.need.${b.code}`, { count: b.tiers[0] });
        return (
          <div key={b.code} className={'badge-sq' + (b.tier === 0 ? ' locked' : '')} tabIndex={0} data-tip={tip} aria-label={`${name}. ${tip}`}>
            <div className={`badge-sq-icon${b.tier_name ? ` tier-${b.tier_name}` : ''}`}><Icon name="medal" /></div>
            <span className="badge-sq-name">{name}</span>
            <span className="badge-sq-date">
              {b.earned_at ? formatDate(b.earned_at) : t('squad.badges.notYet')}
            </span>
            {progress && (
              <span className="badge-sq-date">
                {t(`squad.badges.value.${b.code}`, { count: b.value })}
                {b.next_min !== null && ` · ${t('squad.badges.next', { n: b.next_min })}`}
              </span>
            )}
          </div>
        );
      })}
    </div>
  );
}

/** Karta Odznaki w Moim oddziale. */
export default function SquadBadgesPanel({ badges }: { badges: SquadBadge[] }) {
  const { t } = useTranslation();
  return (
    <section className="panel">
      <h2>{t('squad.badges.title')}</h2>
      <p className="muted">{t('squad.badges.intro')}</p>
      <SquadBadgeSquares badges={badges} progress />
    </section>
  );
}
