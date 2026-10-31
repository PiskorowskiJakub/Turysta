import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { SkillInfo, SkillsResponse } from '../api/types';
import { tiersFor } from '../badges';
import { Icon, SkillIcon } from '../components/icons';
import { usePolling } from '../hooks/usePolling';

export default function BadgesPage() {
  const { t } = useTranslation();
  const [skills, setSkills] = useState<SkillsResponse | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      setSkills(await api<SkillsResponse>('GET', '/skills'));
    } catch (e) {
      setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
    }
  }, [t]);

  useEffect(() => {
    void load();
  }, [load]);
  usePolling(() => void load(), 30000);

  if (!skills) return <p className="panel page-message">{error ?? t('loading')}</p>;

  const skillName = (s: SkillInfo) => t(`skills.${s.code}`, { defaultValue: s.name });
  const earned = (s: SkillInfo) => tiersFor(s.max_level).filter((tier) => s.level >= tier.min).length;
  const total = skills.skills.reduce((sum, s) => sum + tiersFor(s.max_level).length, 0);
  const got = skills.skills.reduce((sum, s) => sum + earned(s), 0);

  const badgeCard = (s: SkillInfo) => (
    <article key={s.id} className="tile badge-card">
      <div className="icon-tile"><SkillIcon code={s.code} /></div>
      <div className="badge-info">
        <h3>{skillName(s)}</h3>
        <span className="badge-level">{t('training.level')}: <strong>{s.level}{s.max_level !== null ? ` / ${s.max_level}` : ''}</strong></span>
      </div>
      <div className="tier-row">
        {tiersFor(s.max_level).map((tier) => {
          const reached = s.level >= tier.min;
          const tip = `${t(`training.tier.${tier.name}`)}: ${t('training.tierAt', { level: tier.min })}`;
          return (
            <span key={tier.name} tabIndex={0} className={`tier tier-${tier.name}` + (reached ? ' reached' : '')} data-tip={tip} aria-label={tip}>
              {reached ? <SkillIcon code={s.code} /> : <Icon name="medal" />}
            </span>
          );
        })}
      </div>
    </article>
  );

  const section = (title: string, list: SkillInfo[], locked = false) => (
    <section key={title} className={'panel trail-blue' + (locked ? ' badge-locked' : '')}>
      <h2>{title}{locked && <Icon name="lock" />}</h2>
      {locked ? (
        <p className="page-message">{t('badges.worldLocked')}</p>
      ) : (
        <>
          <p className="page-message">{t('badges.sectionCount', { got: list.reduce((n, s) => n + earned(s), 0), total: list.reduce((n, s) => n + tiersFor(s.max_level).length, 0) })}</p>
          <div className="badge-grid">{list.map(badgeCard)}</div>
        </>
      )}
    </section>
  );

  return (
    <>
      <section className="panel">
        <h1>{t('badges.title')}</h1>
        <p className="page-message">{t('badges.intro')}</p>
        <p className="badge-total">{t('badges.total', { got, total })}</p>
      </section>
      {section(t('training.basic'), skills.skills.filter((s) => s.world_ordinal === null))}
      {skills.worlds.map((w) =>
        section(
          t('training.worldSkills', { n: w.ordinal, name: w.name }),
          skills.skills.filter((s) => s.world_ordinal === w.ordinal),
          w.ordinal > skills.current_world,
        ),
      )}
    </>
  );
}
