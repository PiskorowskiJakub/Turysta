import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { SkillInfo, SkillsResponse, TrainingItem, TrainingState } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { Icon, type IconName } from '../components/icons';
import ProgressBar from '../components/ProgressBar';
import Tabs, { type TabItem } from '../components/Tabs';
import { formatCoins, formatDuration } from '../format';
import { usePolling } from '../hooks/usePolling';
import { useServerNow } from '../hooks/useServerNow';

// Progi poziomow dla kolejnych odznak (do potwierdzenia projektowo).
const TIERS = [
  { min: 10, name: 'bronze' },
  { min: 25, name: 'silver' },
  { min: 50, name: 'gold' },
  { min: 100, name: 'diamond' },
];

// Kolejne kategorie dodajemy tutaj; podzakladki przez pole children.
const SKILL_TABS: TabItem[] = [{ path: 'podstawowe', label: 'Podstawowe' }];

const SKILL_ICONS: Record<string, IconName> = { fitness: 'fitness', orientation: 'orientation', trade: 'trade' };

function SkillIcon({ code }: { code: string }) {
  return <Icon name={SKILL_ICONS[code] ?? 'skills'} />;
}

export default function TrainingPage() {
  const { t } = useTranslation();
  const { me, refresh } = useAuth();
  const [skills, setSkills] = useState<SkillsResponse | null>(null);
  const [training, setTraining] = useState<TrainingState | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const settledFor = useRef<string | null>(null);

  const now = useServerNow(training?.server_time ?? null);

  const message = useCallback(
    (e: unknown): string =>
      e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'),
    [t],
  );

  const load = useCallback(async () => {
    try {
      const [s, q] = await Promise.all([
        api<SkillsResponse>('GET', '/skills'),
        api<TrainingState>('GET', '/training'),
      ]);
      setSkills(s);
      setTraining(q);
      void refresh();
    } catch (e) {
      setError(message(e));
    }
  }, [refresh, message]);

  useEffect(() => {
    void load();
  }, [load]);
  usePolling(() => void load(), 20000);

  // Gdy odliczanie dojdzie do zera, dociagamy stan (serwer rozlicza przy odczycie).
  const active = training?.active ?? null;
  useEffect(() => {
    if (active?.ends_at && Date.parse(active.ends_at) <= now && settledFor.current !== active.ends_at) {
      settledFor.current = active.ends_at;
      void load();
    }
  }, [active, now, load]);

  const run = async (fn: () => Promise<TrainingState>) => {
    setBusy(true);
    setError(null);
    try {
      setTraining(await fn());
      setSkills(await api<SkillsResponse>('GET', '/skills'));
      void refresh();
    } catch (e) {
      setError(message(e));
    } finally {
      setBusy(false);
    }
  };

  const onAdd = (skillId: number) => run(() => api<TrainingState>('POST', '/training', { skill_id: skillId }));

  const onRemove = (item: TrainingItem, isActive: boolean) => {
    if (isActive && !window.confirm(t('training.confirmCancel', { refund: formatCoins(item.refund_cents) }))) return;
    void run(() => api<TrainingState>('DELETE', `/training/${item.id}`));
  };

  if (!skills || !training) return <p className="panel page-message">{error ?? t('loading')}</p>;

  const slotsUsed = (training.active ? 1 : 0) + training.queue.length;
  const full = slotsUsed >= training.max_slots;
  const coins = me?.wallet.coins_cents ?? 0;
  const skillName = (code: string, name: string) => t(`skills.${code}`, { defaultValue: name });

  const trainCard = (item: TrainingItem, isActive: boolean) => {
    const end = item.ends_at ? Date.parse(item.ends_at) : 0;
    const start = item.started_at ? Date.parse(item.started_at) : 0;
    const remaining = isActive ? Math.max(0, (end - now) / 1000) : item.duration_seconds;
    const progress = isActive && end > start ? (now - start) / (end - start) : 0;
    return (
      <article key={item.id} className="tile train-card">
        <h3>{skillName(item.skill.code, item.skill.name)}</h3>
        <div className="train-meta">
          <span>{t('training.cost')}: <strong>{formatCoins(item.cost_cents)} {t('wallet.coinsShort')}</strong></span>
          <span>{isActive ? t('training.remaining') : t('training.duration')}: <strong>{formatDuration(remaining)}</strong></span>
          <span>{t('training.level')}: <strong>{item.target_level}</strong></span>
        </div>
        <div className="icon-tile"><SkillIcon code={item.skill.code} /></div>
        {isActive ? <ProgressBar value={progress} /> : <span className="queued-note">{t('training.queued')}</span>}
        {item.cancellable && (
          <button type="button" className="btn" disabled={busy} onClick={() => onRemove(item, isActive)}>
            {isActive
              ? t('training.cancelActive', { refund: formatCoins(item.refund_cents) })
              : t('training.removeQueued', { refund: formatCoins(item.refund_cents) })}
          </button>
        )}
      </article>
    );
  };

  const skillCard = (s: SkillInfo) => {
    const cost = s.next_cost_cents ?? 0;
    const cantAfford = s.next_level !== null && cost > coins;
    const label = s.next_level === null
      ? t('training.maxed')
      : full
        ? t('training.queueFull')
        : `${t('training.train')}: ${formatCoins(cost)} ${t('wallet.coinsShort')}`;
    return (
      <div key={s.id} className="skill-col">
        <article className="tile skill-card">
          <h3>{skillName(s.code, s.name)}</h3>
          <div className="icon-tile"><SkillIcon code={s.code} /></div>
          <div>
            <div className="line">
              {t('training.time')}: <strong>{s.next_duration_seconds !== null ? formatDuration(s.next_duration_seconds) : '–'}</strong>
            </div>
            <div className="line">
              {t('training.level')}: <strong>{s.level} / {s.max_level ?? '∞'}</strong>
            </div>
          </div>
          <button
            type="button"
            className="btn"
            disabled={busy || full || cantAfford || s.next_level === null}
            title={cantAfford ? t('training.noFunds') : undefined}
            onClick={() => void onAdd(s.id)}
          >
            {label}
          </button>
        </article>
        <div className="tier-row">
          {TIERS.map((tier) => (
            <span key={tier.min} className={`tier tier-${tier.name}` + (s.level >= tier.min ? ' reached' : '')} title={t('training.tierAt', { level: tier.min })}>
              <Icon name="medal" />
            </span>
          ))}
        </div>
      </div>
    );
  };

  return (
    <>
      <Tabs base="/umiejetnosci" items={SKILL_TABS} />
      {error && <p className="panel form-error" role="alert">{error}</p>}

      <section className="panel">
        <h2>{t('training.practiced', { used: slotsUsed, max: training.max_slots })}</h2>
        {slotsUsed === 0 ? (
          <p className="page-message">{t('training.empty')}</p>
        ) : (
          <>
            {training.active && trainCard(training.active, true)}
            {training.queue.map((q) => trainCard(q, false))}
          </>
        )}
      </section>

      <section className="panel">
        <h2>{t('training.basic')}</h2>
        <div className="skill-grid">{skills.skills.map(skillCard)}</div>
      </section>
    </>
  );
}
