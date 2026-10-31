import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import { TIER_ORDER } from '../badges';
import type { BadgesData } from '../hooks/useBadges';
import BadgeCircle from './BadgeCircle';
import InfoHint from './InfoHint';

const MAX_FEATURED = 5;

interface Props {
  data: BadgesData;
  /** Na stronie odznak mozna wybierac wyroznione; w profilu jest tylko podglad i link do calej ksiazeczki. */
  editable?: boolean;
  as?: 'h1' | 'h2';
  /** Link do wlasnej, pelnej ksiazeczki (w cudzym profilu go nie ma). */
  showLink?: boolean;
}

/** Ksiazeczka odznak: podsumowanie wg stopni i wyroznione odznaki. */
export default function BadgeBook({ data, editable = false, as: Heading = 'h2', showLink = true }: Props) {
  const { t } = useTranslation();
  const { highest, tally, featured, setFeatured, setError, error } = data;
  const [picking, setPicking] = useState(false);
  const [selection, setSelection] = useState<string[]>([]);
  const [saving, setSaving] = useState(false);

  const earnedGroups = [...highest.keys()];
  const shownFeatured = featured.filter((g) => highest.has(g));

  const circle = (group: string, onClick?: () => void, selected = false) => {
    const badge = highest.get(group);
    if (!badge) return null;
    const { name, unit } = data.meta(group);
    return <BadgeCircle key={group} group={group} badge={badge} name={name} unit={unit} onClick={onClick} selected={selected} />;
  };

  const startPicking = () => {
    setSelection(shownFeatured);
    setPicking(true);
  };
  const toggle = (g: string) =>
    setSelection((cur) => (cur.includes(g) ? cur.filter((x) => x !== g) : cur.length >= MAX_FEATURED ? cur : [...cur, g]));
  const save = async () => {
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

  return (
    <section className="panel">
      <InfoHint text={t('badges.intro')} label={t('badges.moreInfo')} />
      <Heading>{t('badges.title')}</Heading>
      <p className="badge-tally" aria-label={t('badges.tallyLabel')}>
        {TIER_ORDER.filter((tier) => (tally[tier] ?? 0) > 0).map((tier) => (
          <span key={tier} className="badge-tally-item" title={t(`training.tier.${tier}`)}>
            <i className={`badge-dot tier-${tier}`} />
            {t(`badges.tally.${tier}`)}: <strong>{tally[tier]}</strong>
          </span>
        ))}
      </p>

      <h3 className="badge-sub">{t('badges.featured')}</h3>
      {editable && picking ? (
        <>
          <p className="page-message">{t('badges.pickInfo', { n: selection.length, max: MAX_FEATURED })}</p>
          <div className="badge-squares">{earnedGroups.map((g) => circle(g, () => toggle(g), selection.includes(g)))}</div>
          <div className="badge-actions">
            <button type="button" className="btn" disabled={saving} onClick={() => void save()}>{t('badges.save')}</button>
            <button type="button" className="btn" disabled={saving} onClick={() => setPicking(false)}>{t('badges.cancel')}</button>
          </div>
        </>
      ) : (
        <>
          {shownFeatured.length === 0 ? (
            <p className="page-message">{t('badges.featuredNone')}</p>
          ) : (
            <div className="badge-squares">{shownFeatured.map((g) => circle(g))}</div>
          )}
          <div className="badge-actions">
            {editable && earnedGroups.length > 0 && (
              <button type="button" className="btn" onClick={startPicking}>{t('badges.pickFeatured')}</button>
            )}
            {!editable && showLink && <Link to="/odznaki" className="btn">{t('badges.seeAll')}</Link>}
          </div>
        </>
      )}
      {error && <p className="form-error">{error}</p>}
    </section>
  );
}
