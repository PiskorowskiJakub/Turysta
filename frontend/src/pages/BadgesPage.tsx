import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { BadgesResponse, EarnedBadge, SkillInfo, SkillsResponse } from '../api/types';
import { SPECIAL_BADGES, TIER_ORDER, tiersFor } from '../badges';
import { Icon, SkillIcon } from '../components/icons';
import InfoHint from '../components/InfoHint';
import { formatDate } from '../format';
import { usePolling } from '../hooks/usePolling';

const MAX_FEATURED = 5;

interface Highest extends EarnedBadge {
  count: number;
}

export default function BadgesPage() {
  const { t } = useTranslation();
  const [skills, setSkills] = useState<SkillsResponse | null>(null);
  const [earned, setEarned] = useState<EarnedBadge[] | null>(null);
  const [featured, setFeatured] = useState<string[]>([]);
  const [picking, setPicking] = useState(false);
  const [selection, setSelection] = useState<string[]>([]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const [s, b] = await Promise.all([api<SkillsResponse>('GET', '/skills'), api<BadgesResponse>('GET', '/badges')]);
      setSkills(s);
      setEarned(b.badges);
      setFeatured(b.featured);
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
  // Ile odznaki zdobyto w kazdym stopniu (liczymy wszystkie zdobyte stopnie, nie tylko najwyzsze).
  const tally: Record<string, number> = {};
  for (const b of earned) tally[b.tier] = (tally[b.tier] ?? 0) + 1;

  const meta = (group: string): { name: string; unit: 'level' | 'days' } => {
    const sk = skills.skills.find((x) => x.code === group);
    return sk ? { name: skillName(sk), unit: 'level' } : { name: t(`badges.${group}`), unit: 'days' };
  };

  const circle = (group: string, onClick?: () => void, selected = false) => {
    const h = highest.get(group);
    if (!h) return null;
    const { name, unit } = meta(group);
    const need = unit === 'days' ? t('badges.tierDays', { n: h.min }) : t('training.tierAt', { level: h.min });
    const tip = `${t(`training.tier.${h.tier}`)}: ${need}`;
    const body = (
      <>
        <div className={`badge-sq-icon tier-${h.tier}`}>
          <SkillIcon code={group} />
          {selected && <span className="badge-sq-check"><Icon name="check" /></span>}
        </div>
        <span className="badge-sq-name">{name}</span>
        <span className="badge-sq-date">{formatDate(h.earned_at)}</span>
      </>
    );
    return onClick ? (
      <button key={group} type="button" className={'badge-sq selectable' + (selected ? ' selected' : '')} data-tip={tip} aria-pressed={selected} aria-label={`${name}. ${tip}`} onClick={onClick}>
        {body}
      </button>
    ) : (
      <div key={group} className="badge-sq" tabIndex={0} data-tip={tip} aria-label={`${name}. ${tip}`}>{body}</div>
    );
  };

  const earnedGroups = [...highest.keys()];
  const startPicking = () => {
    setSelection(featured.filter((g) => highest.has(g)));
    setPicking(true);
  };
  const toggle = (g: string) =>
    setSelection((cur) => (cur.includes(g) ? cur.filter((x) => x !== g) : cur.length >= MAX_FEATURED ? cur : [...cur, g]));
  const saveFeatured = async () => {
    setSaving(true);
    try {
      const r = await api<{ featured: string[] }>('PUT', '/badges/featured', { groups: selection });
      setFeatured(r.featured);
      setPicking(false);
      setError(null);
    } catch (e) {
      setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
    } finally {
      setSaving(false);
    }
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
      <section className="panel">
        <InfoHint text={t('badges.intro')} label={t('badges.moreInfo')} />
        <h1>{t('badges.title')}</h1>
        <p className="badge-tally" aria-label={t('badges.tallyLabel')}>
          {TIER_ORDER.filter((tier) => tier === 'bronze' || tier === 'silver' || tier === 'gold' || (tally[tier] ?? 0) > 0).map((tier) => (
            <span key={tier} className="badge-tally-item" title={t(`training.tier.${tier}`)}>
              <i className={`badge-dot tier-${tier}`} />
              {t(`badges.tally.${tier}`)}: <strong>{tally[tier] ?? 0}</strong>
            </span>
          ))}
        </p>

        <h3 className="badge-sub">{t('badges.featured')}</h3>
        {picking ? (
          <>
            <p className="page-message">{t('badges.pickInfo', { n: selection.length, max: MAX_FEATURED })}</p>
            <div className="badge-squares">{earnedGroups.map((g) => circle(g, () => toggle(g), selection.includes(g)))}</div>
            <div className="badge-actions">
              <button type="button" className="btn" disabled={saving} onClick={() => void saveFeatured()}>{t('badges.save')}</button>
              <button type="button" className="btn" disabled={saving} onClick={() => setPicking(false)}>{t('badges.cancel')}</button>
            </div>
          </>
        ) : (
          <>
            {featured.filter((g) => highest.has(g)).length === 0 ? (
              <p className="page-message">{t('badges.featuredNone')}</p>
            ) : (
              <div className="badge-squares">{featured.filter((g) => highest.has(g)).map((g) => circle(g))}</div>
            )}
            {earnedGroups.length > 0 && (
              <div className="badge-actions">
                <button type="button" className="btn" onClick={startPicking}>{t('badges.pickFeatured')}</button>
              </div>
            )}
          </>
        )}
        {error && <p className="form-error">{error}</p>}
      </section>
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
