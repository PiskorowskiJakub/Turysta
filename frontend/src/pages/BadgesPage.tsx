import { useTranslation } from 'react-i18next';
import type { SkillInfo } from '../api/types';
import { SPECIAL_BADGES, tiersFor } from '../badges';
import BadgeBook from '../components/BadgeBook';
import BadgeCircle from '../components/BadgeCircle';
import { Icon } from '../components/icons';
import InfoHint from '../components/InfoHint';
import { useBadges } from '../hooks/useBadges';

export default function BadgesPage() {
  const { t } = useTranslation();
  const data = useBadges();
  const { skills, highest, meta } = data;

  if (!data.ready || !skills) return <p className="panel page-message">{data.error ?? t('loading')}</p>;

  const circle = (group: string) => {
    const badge = highest.get(group);
    if (!badge) return null;
    const { name, unit } = meta(group);
    return <BadgeCircle key={group} group={group} badge={badge} name={name} unit={unit} />;
  };
  const earnedCount = (groups: string[]) => groups.reduce((n, g) => n + (highest.get(g)?.count ?? 0), 0);

  const skillSection = (title: string, list: SkillInfo[], locked = false) => {
    const shown = list.filter((s) => highest.has(s.code));
    const max = list.reduce((n, s) => n + tiersFor(s.max_level).length, 0);
    return (
      <section key={title} className={'panel trail-blue' + (locked ? ' badge-locked' : '')}>
        <h2>
          {title}
          {locked ? <Icon name="lock" /> : <span className="badge-count">{t('badges.count', { got: earnedCount(list.map((s) => s.code)), total: max })}</span>}
        </h2>
        {locked ? (
          <p className="page-message">{t('badges.worldLocked')}</p>
        ) : shown.length === 0 ? (
          <p className="page-message">{t('badges.none')}</p>
        ) : (
          <div className="badge-squares">{shown.map((s) => circle(s.code))}</div>
        )}
      </section>
    );
  };

  const specialShown = SPECIAL_BADGES.filter((b) => highest.has(b.code));

  // Kategorie, ktore dopiero dojda: odznaki sezonowe (eventy cykliczne oraz jednorazowe).
  const futureSection = (title: string, info: string) => (
    <section key={title} className="panel trail-blue">
      <InfoHint text={info} label={t('badges.moreInfo')} />
      <h2>{title}</h2>
      <p className="page-message badge-soon">{t('badges.soon')}</p>
    </section>
  );

  return (
    <>
      <BadgeBook data={data} editable as="h1" />
      <section className="panel trail-blue">
        <InfoHint text={t('badges.specialInfo')} label={t('badges.moreInfo')} />
        <h2>{t('badges.special')}</h2>
        {specialShown.length === 0 ? (
          <p className="page-message">{t('badges.none')}</p>
        ) : (
          <div className="badge-squares">{specialShown.map((b) => circle(b.code))}</div>
        )}
      </section>
      {futureSection(t('badges.seasonal'), t('badges.seasonalInfo'))}
      {skillSection(t('training.basic'), skills.skills.filter((s) => s.world_ordinal === null))}
      {skills.worlds.map((w) =>
        skillSection(
          t('training.worldSkills', { n: w.ordinal, name: w.name }),
          skills.skills.filter((s) => s.world_ordinal === w.ordinal),
          w.ordinal > skills.current_world,
        ),
      )}
    </>
  );
}
