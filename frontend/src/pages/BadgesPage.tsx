import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { BadgesResponse, EarnedBadge, SkillInfo, SkillsResponse } from '../api/types';
import { SPECIAL_BADGES, TIER_ORDER, tiersFor } from '../badges';
import { Icon, SkillIcon } from '../components/icons';
import { formatDate } from '../format';
import { usePolling } from '../hooks/usePolling';

interface Highest extends EarnedBadge {
  count: number;
}

export default function BadgesPage() {
  const { t } = useTranslation();
  const [skills, setSkills] = useState<SkillsResponse | null>(null);
  const [earned, setEarned] = useState<EarnedBadge[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const [s, b] = await Promise.all([api<SkillsResponse>('GET', '/skills'), api<BadgesResponse>('GET', '/badges')]);
      setSkills(s);
      setEarned(b.badges);
    } catch (e) {
      setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
    }
  }, [t]);

  useEffect(() => {
    void load();
  }, [load]);
  usePolling(() => void load(), 30000);

  if (!skills || !earned) return <p className="panel page-message">{error ?? t('loading')}</p>;

  // Dla kazdej umiejetnosci / odznaki specjalnej pokazujemy tylko najwyzszy zdobyty stopien.
  const highest = new Map<string, Highest>();
  for (const b of earned) {
    const cur = highest.get(b.group);
    const count = (cur?.count ?? 0) + 1;
    if (!cur || TIER_ORDER.indexOf(b.tier) > TIER_ORDER.indexOf(cur.tier)) highest.set(b.group, { ...b, count });
    else cur.count = count;
  }

  const skillName = (s: SkillInfo) => t(`skills.${s.code}`, { defaultValue: s.name });
  const earnedCount = (groups: string[]) => groups.reduce((n, g) => n + (highest.get(g)?.count ?? 0), 0);
  const specialGroups = SPECIAL_BADGES.map((b) => b.code);
  const specialTotal = SPECIAL_BADGES.reduce((n, b) => n + b.tiers.length, 0);
  const total = skills.skills.reduce((n, s) => n + tiersFor(s.max_level).length, 0) + specialTotal;
  const got = earnedCount(skills.skills.map((s) => s.code)) + earnedCount(specialGroups);

  const square = (group: string, name: string, unit: 'level' | 'days') => {
    const h = highest.get(group);
    if (!h) return null;
    const need = unit === 'days' ? t('badges.tierDays', { n: h.min }) : t('training.tierAt', { level: h.min });
    const tip = `${t(`training.tier.${h.tier}`)}: ${need}`;
    return (
      <div key={group} className="badge-sq" tabIndex={0} data-tip={tip} aria-label={`${name}. ${tip}`}>
        <div className={`badge-sq-icon tier-${h.tier}`}><SkillIcon code={group} /></div>
        <span className="badge-sq-name">{name}</span>
        <span className="badge-sq-date">{formatDate(h.earned_at)}</span>
      </div>
    );
  };

  const header = (title: string, count: number, max: number, locked = false) => (
    <h2>
      {title}
      {locked ? <Icon name="lock" /> : <span className="badge-count">{t('badges.count', { got: count, total: max })}</span>}
    </h2>
  );

  const skillSection = (title: string, list: SkillInfo[], locked = false) => {
    const shown = list.filter((s) => highest.has(s.code));
    const max = list.reduce((n, s) => n + tiersFor(s.max_level).length, 0);
    return (
      <section key={title} className={'panel trail-blue' + (locked ? ' badge-locked' : '')}>
        {header(title, earnedCount(list.map((s) => s.code)), max, locked)}
        {locked ? (
          <p className="page-message">{t('badges.worldLocked')}</p>
        ) : shown.length === 0 ? (
          <p className="page-message">{t('badges.none')}</p>
        ) : (
          <div className="badge-squares">{shown.map((s) => square(s.code, skillName(s), 'level'))}</div>
        )}
      </section>
    );
  };

  const specialShown = SPECIAL_BADGES.filter((b) => highest.has(b.code));

  // Kategorie, ktore dopiero dojda: odznaki sezonowe (eventy cykliczne oraz jednorazowe).
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
      <section className="panel trail-blue">
        {header(t('badges.special'), earnedCount(specialGroups), specialTotal)}
        <p className="page-message">{t('badges.specialInfo')}</p>
        {specialShown.length === 0 ? (
          <p className="page-message">{t('badges.none')}</p>
        ) : (
          <div className="badge-squares">{specialShown.map((b) => square(b.code, t(`badges.${b.code}`), 'days'))}</div>
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
