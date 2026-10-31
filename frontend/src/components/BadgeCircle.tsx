import { useTranslation } from 'react-i18next';
import { formatDate } from '../format';
import type { Highest } from '../hooks/useBadges';
import { Icon, SkillIcon } from './icons';

interface Props {
  group: string;
  badge: Highest;
  name: string;
  unit: 'level' | 'days';
  onClick?: () => void;
  selected?: boolean;
}

/** Okragla odznaka (najwyzszy zdobyty stopien), pod nia nazwa i data zdobycia. Z onClick dziala jak przycisk wyboru. */
export default function BadgeCircle({ group, badge, name, unit, onClick, selected = false }: Props) {
  const { t } = useTranslation();
  const need = unit === 'days' ? t('badges.tierDays', { n: badge.min }) : t('training.tierAt', { level: badge.min });
  const tip = `${t(`training.tier.${badge.tier}`)}: ${need}`;
  const body = (
    <>
      <div className={`badge-sq-icon tier-${badge.tier}`}>
        <SkillIcon code={group} />
        {selected && <span className="badge-sq-check"><Icon name="check" /></span>}
      </div>
      <span className="badge-sq-name">{name}</span>
      <span className="badge-sq-date">{formatDate(badge.earned_at)}</span>
    </>
  );
  return onClick ? (
    <button type="button" className={'badge-sq selectable' + (selected ? ' selected' : '')} data-tip={tip} aria-pressed={selected} aria-label={`${name}. ${tip}`} onClick={onClick}>
      {body}
    </button>
  ) : (
    <div className="badge-sq" tabIndex={0} data-tip={tip} aria-label={`${name}. ${tip}`}>{body}</div>
  );
}
