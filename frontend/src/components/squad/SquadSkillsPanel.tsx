import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import type { TFunction } from 'i18next';
import { api, ApiError } from '../../api/client';
import type { SquadSkillItem, SquadSkillTraining, SquadSkillsState } from '../../api/types';
import { useAuth } from '../../auth/AuthContext';
import { formatCoins, formatCoinsFull, formatDuration, formatEndTime, formatPercent } from '../../format';
import { usePolling } from '../../hooks/usePolling';
import { useServerNow } from '../../hooks/useServerNow';
import Coins from '../Coins';
import { Icon } from '../icons';
import InfoHint from '../InfoHint';
import ProgressBar from '../ProgressBar';

function errText(t: TFunction, e: unknown): string {
  return e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network');
}

/** Karta Oddzial w umiejetnosciach: osobny slot szkolen, ktorych korzysc trafia do oddzialu. */
export default function SquadSkillsPanel() {
  const { t } = useTranslation();
  const { me, refresh } = useAuth();
  const [state, setState] = useState<SquadSkillsState | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const settledFor = useRef<string | null>(null);
  const now = useServerNow(state?.server_time ?? null);

  const load = useCallback(async () => {
    try {
      setState((await api<{ squad_skills: SquadSkillsState }>('GET', '/squad-skills')).squad_skills);
      setError(null);
      void refresh();
    } catch (e) {
      setError(errText(t, e));
    }
  }, [refresh, t]);

  useEffect(() => {
    void load();
  }, [load]);
  usePolling(() => void load(), 20000);

  const active = state?.active ?? null;
  useEffect(() => {
    if (active?.ends_at && Date.parse(active.ends_at) <= now && settledFor.current !== active.ends_at) {
      settledFor.current = active.ends_at;
      void load();
    }
  }, [active, now, load]);

  const run = async (fn: () => Promise<{ squad_skills: SquadSkillsState }>) => {
    setBusy(true);
    setError(null);
    try {
      setState((await fn()).squad_skills);
      void refresh();
    } catch (e) {
      setError(errText(t, e));
    } finally {
      setBusy(false);
    }
  };

  if (!state) return <p className="panel page-message">{error ?? t('loading')}</p>;

  const coins = me?.wallet.coins_cents ?? 0;
  const used = (state.active ? 1 : 0) + state.queue.length;
  const full = used >= state.max_slots;
  const name = (code: string) => t(`training.squadSkills.names.${code}`);

  // Przewidywane zakonczenie: aktywne konczy sie o ends_at, kolejne startuja jedno po drugim.
  const endTimes = new Map<number, number>();
  let cursor = now;
  if (state.active) {
    cursor = state.active.ends_at ? Date.parse(state.active.ends_at) : now;
    endTimes.set(state.active.id, cursor);
  }
  for (const q of state.queue) {
    cursor += q.duration_seconds * 1000;
    endTimes.set(q.id, cursor);
  }

  const onCancel = (item: SquadSkillTraining, isActive: boolean) => {
    if (isActive && !window.confirm(t('training.confirmCancel', { refund: formatCoins(item.refund_cents) }))) return;
    void run(() => api<{ squad_skills: SquadSkillsState }>('DELETE', `/squad-skills/${item.id}`));
  };

  const trainCard = (item: SquadSkillTraining, isActive: boolean) => {
    const end = item.ends_at ? Date.parse(item.ends_at) : 0;
    const start = item.started_at ? Date.parse(item.started_at) : 0;
    const remaining = isActive ? Math.max(0, (end - now) / 1000) : item.duration_seconds;
    const progress = isActive && end > start ? (now - start) / (end - start) : 0;
    return (
      <article key={item.id} className="tile train-card">
        <h3>{name(item.skill)}</h3>
        <div className="train-meta">
          <span>{t('training.cost')}: <strong><Coins cents={item.cost_cents} /> {t('wallet.coinsShort')}</strong></span>
          <span>{isActive ? t('training.remaining') : t('training.duration')}: <strong>{formatDuration(remaining)}</strong></span>
          <span>{t('training.level')}: <strong>{item.target_level}</strong></span>
          <span>{t('training.endsAt')}: <strong>{formatEndTime(endTimes.get(item.id) ?? now, now)}</strong></span>
        </div>
        <div className="icon-tile"><Icon name="medal" /></div>
        {isActive ? <ProgressBar value={progress} /> : <span className="queued-note">{t('training.queued')}</span>}
        <button type="button" className="btn" disabled={busy} onClick={() => onCancel(item, isActive)}>
          {isActive
            ? t('training.cancelActive', { refund: formatCoins(item.refund_cents) })
            : t('training.removeQueued', { refund: formatCoins(item.refund_cents) })}
        </button>
      </article>
    );
  };

  const effectLine = (s: SquadSkillItem): string | null => {
    if (!state.squad) return null;
    const pct = formatPercent(s.squad_effect_percent ?? 0);
    return t(`training.squadSkills.effect.${s.code}`, { pct, avg: (s.squad_avg ?? 0).toLocaleString('pl-PL', { maximumFractionDigits: 1 }) });
  };

  const skillCard = (s: SquadSkillItem) => {
    const cost = s.next_cost_cents;
    const cantAfford = cost > coins;
    return (
      <div key={s.code} className="skill-col">
        <article className="tile skill-card" tabIndex={0}>
          <h3>{name(s.code)}</h3>
          <div className="skill-art">
            <div className="icon-tile"><Icon name="medal" /></div>
            <p className="skill-story">{t(`training.squadSkills.story.${s.code}`)}</p>
          </div>
          <div>
            <div className="line">{t('training.time')}: <strong>{formatDuration(s.next_duration_seconds)}</strong></div>
            <div className="line">{t('training.level')}: <strong>{s.level + s.in_queue} / ∞</strong></div>
            {effectLine(s) && <div className="line muted">{effectLine(s)}</div>}
            {s.max_percent !== null && <div className="line muted">{t('training.squadSkills.max', { pct: formatPercent(s.max_percent) })}</div>}
          </div>
          <button
            type="button"
            className="btn"
            disabled={busy || full || cantAfford}
            title={`${full ? t('training.queueFull') : cantAfford ? t('training.noFunds') : t('training.cost')}: ${formatCoinsFull(cost)} ${t('wallet.coinsShort')}`}
            onClick={() => void run(() => api<{ squad_skills: SquadSkillsState }>('POST', '/squad-skills', { skill: s.code }))}
          >
            {formatCoins(cost)} {t('wallet.coinsShort')}
          </button>
        </article>
      </div>
    );
  };

  return (
    <>
      {error && <p className="panel form-error" role="alert">{error}</p>}
      <section className="panel trail-blue">
        <InfoHint wide text={`${t('training.squadSkills.info', { half: state.half, min: state.squad?.min_active ?? 3 })}\n\n${t('training.squadSkills.strategy', { half: state.half })}`} label={t('badges.moreInfo')} />
        <h2>{t('training.squadSkills.slot', { used, max: state.max_slots })}</h2>
        {used === 0 ? (
          <p className="page-message">{t('training.squadSkills.empty')}</p>
        ) : (
          <>
            {state.active && trainCard(state.active, true)}
            {state.queue.map((q) => trainCard(q, false))}
          </>
        )}
      </section>
      <section className="panel trail-blue">
        <h2>{t('training.squadSkills.title')}</h2>
        {!state.squad ? (
          <p className="form-notice">{t('training.squadSkills.noSquad')}</p>
        ) : (
          <p className={state.squad.enough ? 'muted' : 'form-notice'}>
            {state.squad.enough
              ? t('training.squadSkills.squadOk', { n: state.squad.active_members })
              : t('training.squadSkills.squadNotEnough', { n: state.squad.active_members, min: state.squad.min_active })}
            {state.squad.discount_percent > 0 && ` ${t('training.squadSkills.discount', { pct: formatPercent(state.squad.discount_percent) })}`}
          </p>
        )}
        <div className="skill-grid">{state.skills.map(skillCard)}</div>
      </section>
    </>
  );
}
