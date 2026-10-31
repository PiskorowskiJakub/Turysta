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

  // Pokazujemy tylko zdobyte odznaki; umiejetnosc bez zadnej odznaki jest ukryta.
  const badgeChip = (s: SkillInfo) => (
    <div key={s.id} className="badge-chip">
      <span className="badge-chip-tiers">
        {tiersFor(s.max_level).filter((tier) => s.level >= tier.min).map((tier) => {
          const tip = `${t(`training.tier.${tier.name}`)}: ${t('training.tierAt', { level: tier.min })}`;
          return (
            <span key={tier.name} tabIndex={0} className={`tier tier-${tier.name} reached`} data-tip={tip} aria-label={tip}>
              <SkillIcon code={s.code} />
            </span>
          );
        })}
      </span>
      <span className="badge-chip-name">{skillName(s)}</span>
    </div>
  );

  const section = (title: string, list: SkillInfo[], locked = false) => {
    const shown = list.filter((s) => earned(s) > 0);
    const count = list.reduce((n, s) => n + earned(s), 0);
    const max = list.reduce((n, s) => n + tiersFor(s.max_level).length, 0);
    return (
      <section key={title} className={'panel trail-blue' + (locked ? ' badge-locked' : '')}>
        <h2>
          {title}
          {locked ? <Icon name="lock" /> : <span className="badge-count">{t('badges.count', { got: count, total: max })}</span>}
        </h2>
        {locked ? (
          <p className="page-message">{t('badges.worldLocked')}</p>
        ) : shown.length === 0 ? (
          <p className="page-message">{t('badges.none')}</p>
        ) : (
          <div className="badge-list">{shown.map(badgeChip)}</div>
        )}
      </section>
    );
  };

  // Kategorie, ktore dopiero dojda: odznaki specjalne i sezonowe (eventy cykliczne oraz jednorazowe).
  const futureSection = (title: string, info: string) => (
    <section key={title} className="panel trail-blue">
      <h2>
        {title}
        <span className="badge-count">{t('badges.count', { got: 0, total: 0 })}</span>
      </h2>
      <p className="page-message">{info}</p>
      <p className="page-message badge-soon">{t('badges.soon')}</p>
    </section>
  );

  return (
    <>
      <section className="panel">
        <h1>{t('badges.title')}</h1>
        <p className="page-message">{t('badges.intro')}</p>
        <p className="badge-total">{t('badges.total', { got, total })}</p>
      </section>
      {futureSection(t('badges.special'), t('badges.specialInfo'))}
      {futureSection(t('badges.seasonal'), t('badges.seasonalInfo'))}
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
