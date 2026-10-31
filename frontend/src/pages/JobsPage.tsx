import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { JobInfo, JobsResponse } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import ProgressBar from '../components/ProgressBar';
import { formatCoins, formatDuration } from '../format';
import { usePolling } from '../hooks/usePolling';
import { useServerNow } from '../hooks/useServerNow';
import { Icon, type IconName } from '../components/icons';

const JOB_ICONS: Record<string, IconName> = { market: 'market', hostel: 'hostel', expedition: 'expedition' };

export default function JobsPage() {
  const { t } = useTranslation();
  const { refresh } = useAuth();
  const [data, setData] = useState<JobsResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [info, setInfo] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const settledFor = useRef<string | null>(null);

  const now = useServerNow(data?.server_time ?? null);

  const message = useCallback(
    (e: unknown): string =>
      e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'),
    [t],
  );

  const load = useCallback(async () => {
    try {
      setData(await api<JobsResponse>('GET', '/jobs'));
      void refresh();
    } catch (e) {
      setError(message(e));
    }
  }, [refresh, message]);

  useEffect(() => {
    void load();
  }, [load]);
  usePolling(() => void load(), 20000);

  const active = data?.active ?? null;
  useEffect(() => {
    if (active && Date.parse(active.ends_at) <= now && settledFor.current !== active.ends_at) {
      settledFor.current = active.ends_at;
      void load();
    }
  }, [active, now, load]);

  const act = async (fn: () => Promise<JobsResponse>, done?: (r: JobsResponse) => string | null) => {
    setBusy(true);
    setError(null);
    setInfo(null);
    try {
      const r = await fn();
      setData(r);
      setInfo(done ? done(r) : null);
      void refresh();
    } catch (e) {
      setError(message(e));
    } finally {
      setBusy(false);
    }
  };

  const onStart = (job: JobInfo) => act(() => api<JobsResponse>('POST', `/jobs/${job.id}/start`));

  const onStop = () => {
    if (!active) return;
    const forfeit = active.job.cancel_policy === 'none';
    if (forfeit && !window.confirm(t('jobs.confirmForfeit'))) return;
    void act(
      () => api<JobsResponse>('POST', '/jobs/active/stop', { confirm: forfeit }),
      (r) => t('jobs.stopped', { amount: formatCoins(r.payout_cents ?? 0) }),
    );
  };

  if (!data) return <p className="panel page-message">{error ?? t('loading')}</p>;

  const cooldownLeft = data.available_at ? Math.max(0, (Date.parse(data.available_at) - now) / 1000) : 0;
  const onBreak = !active && cooldownLeft > 0;

  const card = (j: JobInfo) => {
    const isActive = active?.job.id === j.id;
    const jobIcon: IconName = JOB_ICONS[j.code] ?? 'job';
    let timeLabel = formatDuration(j.duration_seconds);
    let earnLabel = formatCoins(j.reward_cents);
    let progress = 0;
    let noStop = false;

    if (isActive && active) {
      const start = Date.parse(active.started_at);
      const end = Date.parse(active.ends_at);
      progress = end > start ? Math.min(1, Math.max(0, (now - start) / (end - start))) : 1;
      timeLabel = formatDuration(Math.max(0, (end - now) / 1000));
      noStop = active.earned_cents === null;
      earnLabel = noStop
        ? formatCoins(j.reward_cents)
        : formatCoins(Math.floor((j.reward_cents * Math.max(0, Math.min(end - start, now - start))) / Math.max(1, end - start)));
    }

    return (
      <article key={j.id} className="tile job-card">
        <h3>{t(`jobs.names.${j.code}`, { defaultValue: j.name })}</h3>
        <div className="job-desc">
          <p>{t(`jobs.stories.${j.code}`, { defaultValue: j.description ?? '' })}</p>
          <p className="job-policy">{j.cancel_policy === 'none' ? t('jobs.policyNone') : t('jobs.policyProportional')}</p>
        </div>
        <div className="job-img"><div className="icon-tile"><Icon name={jobIcon} /></div></div>
        <div className="job-stats">
          <div className="lines">
            <div className="line">{isActive ? t('jobs.timeLeft') : t('jobs.time')}: <strong>{timeLabel}</strong></div>
            <div className="line">{isActive && !noStop ? t('jobs.earnedShort') : t('jobs.reward')}: <strong>{earnLabel}</strong></div>
          </div>
          {isActive ? (
            <button type="button" className="btn" disabled={busy} onClick={onStop}>
              {j.cancel_policy === 'none' ? t('jobs.stopForfeit') : t('jobs.stop')}
            </button>
          ) : (
            <button type="button" className="btn" disabled={busy || !!active || onBreak} onClick={() => void onStart(j)}>
              {t('jobs.start')}
            </button>
          )}
        </div>
        {isActive && <ProgressBar value={progress} />}
      </article>
    );
  };

  return (
    <section className="panel trail-red">
      <h1>{t('jobs.title')}</h1>
      {error && <p className="form-error" role="alert">{error}</p>}
      {info && <p className="form-success" role="status">{info}</p>}
      {onBreak && <p className="form-notice">{t('jobs.cooldown', { time: formatDuration(cooldownLeft) })}</p>}
      {active && active.earned_cents === null && (
        <p className="form-notice">{t('jobs.noStop', { amount: formatCoins(active.job.reward_cents) })}</p>
      )}
      {data.jobs.map(card)}
    </section>
  );
}
