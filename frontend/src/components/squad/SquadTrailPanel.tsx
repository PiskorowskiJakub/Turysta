import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import type { TFunction } from 'i18next';
import { api, ApiError } from '../../api/client';
import type { SquadTrail, SquadTrailHistoryItem, TrailDifficulty } from '../../api/types';
import { formatCoins, formatDate } from '../../format';
import InfoHint from '../InfoHint';

export type TrailSection = 'wyprawa' | 'historia';

const DIFFICULTIES: TrailDifficulty[] = ['easy', 'medium', 'hard'];

function errText(t: TFunction, e: unknown): string {
  return e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network');
}

function useTrail(squadId: number) {
  const { t } = useTranslation();
  const [trail, setTrail] = useState<SquadTrail | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      setTrail((await api<{ trail: SquadTrail }>('GET', `/squads/${squadId}/trail`)).trail);
      setError(null);
    } catch (e) {
      setError(errText(t, e));
    }
  }, [squadId, t]);

  useEffect(() => {
    void load();
  }, [load]);

  return { trail, setTrail, error, setError, load };
}

/** Wyprawa tygodnia (zakladka Szlaki) albo historia wypraw. */
export default function SquadTrailPanel({ squadId, section }: { squadId: number; section: TrailSection }) {
  return section === 'wyprawa' ? <CurrentTrail squadId={squadId} /> : <TrailHistory squadId={squadId} />;
}

function CurrentTrail({ squadId }: { squadId: number }) {
  const { t } = useTranslation();
  const { trail, setTrail, error, setError } = useTrail(squadId);
  const [busy, setBusy] = useState(false);

  if (!trail) return <p className="panel page-message">{error ?? t('loading')}</p>;

  const act = async (method: 'POST' | 'PUT', path: string, body?: unknown) => {
    setBusy(true);
    setError(null);
    try {
      setTrail((await api<{ trail: SquadTrail }>(method, `/squads/${squadId}/trail/${path}`, body)).trail);
    } catch (e) {
      setError(errText(t, e));
    } finally {
      setBusy(false);
    }
  };

  const fee = formatCoins(trail.fee_cents);
  const perStamp = formatCoins(trail.reward_per_stamp_cents);
  const bar = Math.min(100, trail.percent);

  return (
    <>
      <section className="panel trail-card">
        <InfoHint
          wide
          label={t('badges.moreInfo')}
          text={t('squad.trail.info', { fee, days: trail.min_active_days, min: trail.min_eligible, perStamp })}
        />
        <p className="trail-kicker">{t('squad.trail.week', { from: formatDate(trail.week_start), to: formatDate(trail.week_end) })}</p>
        <h2>{trail.theme.name}</h2>
        <p className="trail-desc">{trail.theme.description}</p>

        <p className="trail-diff">
          <span className={`trail-chip diff-${trail.difficulty}`}>{t(`squad.trail.difficulty.${trail.difficulty}`)}</span>
          <span className="muted"> · {t('squad.trail.length', { km: trail.length_km })}</span>
        </p>
        {trail.can_plan && (
          <div className="trail-plan">
            <div className="segmented" role="group" aria-label={t('squad.trail.planLabel')}>
              {DIFFICULTIES.map((d) => (
                <button key={d} type="button" className={'seg' + (d === trail.difficulty ? ' active' : '')} aria-pressed={d === trail.difficulty} disabled={busy} onClick={() => d !== trail.difficulty && void act('PUT', 'difficulty', { difficulty: d })}>
                  {t(`squad.trail.difficulty.${d}`)}
                </button>
              ))}
            </div>
            <p className="field-hint">{t('squad.trail.planHint', { date: formatDate(trail.plan_until) })}</p>
          </div>
        )}

        <div className="trail-bar" role="progressbar" aria-valuemin={0} aria-valuemax={100} aria-valuenow={Math.round(bar)} aria-label={t('squad.trail.progress')}>
          <div className="trail-bar-fill" style={{ width: `${bar}%` }} />
          {trail.thresholds.map((p, i) => (
            <span key={p} className={'trail-mark' + (trail.percent >= p ? ' reached' : '')} style={{ left: `${p}%` }} title={t('squad.trail.stampAt', { n: i + 1, percent: p })}>
              <i aria-hidden="true">{i + 1}</i>
            </span>
          ))}
        </div>
        <ul className="squad-stats">
          <li><b>{trail.km} / {trail.goal_km}</b><small>{t('squad.trail.km')}</small></li>
          <li><b>{trail.percent}%</b><small>{t('squad.trail.percent')}</small></li>
          <li><b>{trail.stamps_now} / {trail.max_stamps}</b><small>{t('squad.trail.stampsNow')}</small></li>
          <li><b>{trail.squad_stamps}</b><small>{t('squad.trail.pool')}</small></li>
        </ul>
        <p className="muted">{t('squad.trail.perPerson', { km: trail.length_km })}{trail.joined && ` ${t('squad.trail.mine', { done: trail.my_km, goal: trail.length_km, left: Math.max(0, Math.round((trail.length_km - trail.my_km) * 10) / 10) })}`}</p>
        <p className="muted">{trail.stamps_now > 0 ? t('squad.trail.rewardNow', { count: trail.stamps_now, reward: formatCoins(trail.stamps_now * trail.reward_per_stamp_cents) }) : t('squad.trail.rewardNone', { percent: trail.thresholds[0] })}</p>

        {error && <p className="form-error" role="alert">{error}</p>}
        <div className="trail-actions">
          {!trail.joined && trail.can_join && (
            <button type="button" className="btn" disabled={busy} onClick={() => void act('POST', 'join')}>{t('squad.trail.join', { fee })}</button>
          )}
          {!trail.joined && !trail.can_join && <p className="muted">{t('squad.trail.closed', { date: formatDate(trail.join_until) })}</p>}
          {trail.joined && (
            <button type="button" className="btn" disabled={busy || !trail.can_march} onClick={() => void act('POST', 'march')}>
              {trail.marched_today ? t('squad.trail.marched') : t('squad.trail.march')}
            </button>
          )}
        </div>
        {!trail.joined && trail.can_join && <p className="field-hint">{t('squad.trail.joinHint', { date: formatDate(trail.join_until) })}</p>}
        {trail.joined && !trail.marched_today && <p className="field-hint">{t('squad.trail.marchHint')}</p>}
        <p className="field-hint">{t('squad.trail.nextTrail', { date: dateWithWeekday(trail.next_week_start) })}</p>
      </section>

      <section className="panel">
        <h2>{t('squad.trail.participants', { n: trail.participants.length })}</h2>
        {trail.participants.length === 0 ? (
          <p className="muted">{t('squad.trail.noParticipants')}</p>
        ) : (
          <ul className="trail-people">
            {trail.participants.map((p) => (
              <li key={p.user_id} className={p.is_me ? 'me' : undefined}>
                <Link to={`/gracz/${p.user_id}`}>{p.name}</Link>
                <span className="muted">{t('squad.trail.personLine', { active: p.active_days, march: p.march_days, km: p.km })}</span>
                {p.eligible && <span className="trail-chip ok">{t('squad.trail.counted')}</span>}
              </li>
            ))}
          </ul>
        )}
      </section>
    </>
  );
}

function TrailHistory({ squadId }: { squadId: number }) {
  const { t } = useTranslation();
  const [items, setItems] = useState<SquadTrailHistoryItem[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api<{ items: SquadTrailHistoryItem[] }>('GET', `/squads/${squadId}/trail/history`)
      .then((r) => setItems(r.items))
      .catch((e) => setError(errText(t, e)));
  }, [squadId, t]);

  if (!items) return <p className="panel page-message">{error ?? t('loading')}</p>;
  return (
    <section className="panel">
      <h2>{t('squad.trail.historyTitle')}</h2>
      {items.length === 0 ? (
        <p className="muted">{t('squad.trail.historyEmpty')}</p>
      ) : (
        <ul className="trail-history">
          {items.map((h) => (
            <li key={h.id}>
              <div>
                <b>{h.theme.name}</b>
                <span className="muted"> · {formatDate(h.week_start)} · {t(`squad.trail.difficulty.${h.difficulty}`)}</span>
              </div>
              <div className="muted">
                {t('squad.trail.historyLine', { percent: h.percent, count: h.stamps, people: h.eligible, total: h.participants })}
                {h.joined && ` · ${h.my_eligible ? t('squad.trail.historyMine', { reward: formatCoins(h.my_reward_cents) }) : t('squad.trail.historyMissed')}`}
              </div>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}

/** Data z dniem tygodnia: DD.MM.RRRR (poniedzialek). Data bez godziny, wiec liczona w UTC. */
function dateWithWeekday(iso: string): string {
  const weekday = new Date(iso).toLocaleDateString('pl-PL', { weekday: 'long', timeZone: 'UTC' });
  return `${formatDate(iso)} (${weekday})`;
}

/** Kompaktowa karta wyprawy na Przegladzie oddzialu: stan i link do zakladki. */
export function SquadTrailBanner({ squadId }: { squadId: number }) {
  const { t } = useTranslation();
  const [trail, setTrail] = useState<SquadTrail | null>(null);

  useEffect(() => {
    api<{ trail: SquadTrail }>('GET', `/squads/${squadId}/trail`).then((r) => setTrail(r.trail)).catch(() => setTrail(null));
  }, [squadId]);

  if (!trail) return null;
  let line: string;
  if (trail.joined) {
    line = trail.can_march ? t('squad.trail.bannerMarch', { name: trail.theme.name }) : t('squad.trail.bannerOn', { name: trail.theme.name, percent: trail.percent });
  } else if (trail.can_join) {
    line = t('squad.trail.bannerJoin', { name: trail.theme.name, fee: formatCoins(trail.fee_cents), date: formatDate(trail.join_until) });
  } else {
    line = t('squad.trail.bannerNext', { date: dateWithWeekday(trail.next_week_start) });
  }
  return (
    <section className="panel shelter-tile trail-banner">
      <div className="shelter-tile-head">
        <h2>{t('squad.trail.bannerTitle')}</h2>
        <Link to="/oddzial/szlaki/wyprawa">{t('squad.trail.bannerGo')}</Link>
      </div>
      <p className="shelter-tile-build">{line}</p>
    </section>
  );
}
