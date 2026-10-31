import { useCallback, useEffect, useRef, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { SkillInfo, SkillsResponse, TrainingItem, TrainingState, WorldResponse } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { Icon, type IconName } from '../components/icons';
import ProgressBar from '../components/ProgressBar';
import Tabs, { type TabItem } from '../components/Tabs';
import WorldPanel from '../components/WorldPanel';
import Coins from '../components/Coins';
import { formatCoins, formatCoinsFull, formatDuration, formatEndTime } from '../format';
import { usePolling } from '../hooks/usePolling';
import { useServerNow } from '../hooks/useServerNow';

// Odznaki umiejetnosci bez konca: na start 3 z wysokimi progami. Slot 4 i 5 sa zarezerwowane
// (kolor w CSS jest gotowy) - wlaczamy je, wpisujac 'min' (null = jeszcze nieaktywna). Maksymalnie 5.
const TIERS: { name: string; min: number | null }[] = [
  { name: 'bronze', min: 50 },
  { name: 'silver', min: 100 },
  { name: 'gold', min: 200 },
  { name: 'diamond', min: null },
  { name: 'ruby', min: null },
];
const ACTIVE_TIERS = TIERS.filter((t): t is { name: string; min: number } => t.min !== null);

// Kolejne kategorie dodajemy tutaj; podzakladki przez pole children.
const SKILL_TABS: TabItem[] = [
  { path: 'podstawowe', label: 'Podstawowe' },
  { path: 'swiat', label: 'Świat' },
];

const SKILL_ICONS: Record<string, IconName> = { fitness: 'fitness', orientation: 'orientation', trade: 'trade' };

function SkillIcon({ code }: { code: string }) {
  return <Icon name={SKILL_ICONS[code] ?? 'skills'} />;
}

export default function TrainingPage() {
  const { t } = useTranslation();
  const { me, refresh } = useAuth();
  const [skills, setSkills] = useState<SkillsResponse | null>(null);
  const [training, setTraining] = useState<TrainingState | null>(null);
  const [world, setWorld] = useState<WorldResponse | null>(null);
  const { pathname } = useLocation();
  const tab = pathname.endsWith('/swiat') ? 'swiat' : 'podstawowe';
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
      const [s, q, w] = await Promise.all([
        api<SkillsResponse>('GET', '/skills'),
        api<TrainingState>('GET', '/training'),
        api<WorldResponse>('GET', '/world'),
      ]);
      setSkills(s);
      setTraining(q);
      setWorld(w);
      void refresh();
    } catch (e) {
      setError(message(e));
    }
  }, [refresh, message]);

  useEffect(() => {
    void load();
  }, [load]);
  usePolling(() => void load(), 20000);

  // Po wejsciu w zakladke Swiat odswiezamy dane od razu.
  useEffect(() => {
    if (tab === 'swiat') void load();
  }, [tab, load]);

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
      setWorld(await api<WorldResponse>('GET', '/world'));
      void refresh();
    } catch (e) {
      setError(message(e));
    } finally {
      setBusy(false);
    }
  };

  const onAdvance = () => run(() => api<TrainingState>('POST', '/world/advance'));

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

  // Przewidywane zakonczenie: aktywne konczy sie o ends_at, kolejne startuja jedno po drugim.
  const endTimes = new Map<number, number>();
  let cursor = now;
  if (training.active) {
    cursor = training.active.ends_at ? Date.parse(training.active.ends_at) : now;
    endTimes.set(training.active.id, cursor);
  }
  for (const q of training.queue) {
    cursor += q.duration_seconds * 1000;
    endTimes.set(q.id, cursor);
  }

  const trainCard = (item: TrainingItem, isActive: boolean) => {
    const end = item.ends_at ? Date.parse(item.ends_at) : 0;
    const start = item.started_at ? Date.parse(item.started_at) : 0;
    const remaining = isActive ? Math.max(0, (end - now) / 1000) : item.duration_seconds;
    const progress = isActive && end > start ? (now - start) / (end - start) : 0;
    return (
      <article key={item.id} className="tile train-card">
        <h3>{item.chapter ? item.chapter.name : item.skill ? skillName(item.skill.code, item.skill.name) : ''}</h3>
        <div className="train-meta">
          <span>{t('training.cost')}: <strong><Coins cents={item.cost_cents} /> {t('wallet.coinsShort')}</strong></span>
          <span>{isActive ? t('training.remaining') : t('training.duration')}: <strong>{formatDuration(remaining)}</strong></span>
          <span>{item.chapter ? t('training.rank') : t('training.level')}: <strong>{item.target_level}</strong></span>
          <span>{t('training.endsAt')}: <strong>{formatEndTime(endTimes.get(item.id) ?? now, now)}</strong></span>
        </div>
        <div className="icon-tile">{item.skill ? <SkillIcon code={item.skill.code} /> : <Icon name="rank" />}</div>
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
    // Cena zawsze widoczna; przy pelnej kolejce przycisk jest tylko nieaktywny.
    const label = s.next_level === null
      ? t('training.maxed')
      : `${t('training.train')}: ${formatCoins(cost)} ${t('wallet.coinsShort')}`;
    return (
      <div key={s.id} className="skill-col">
        <article className="tile skill-card" tabIndex={0}>
          <h3>{skillName(s.code, s.name)}</h3>
          <div className="skill-art">
            <div className="icon-tile"><SkillIcon code={s.code} /></div>
            <p className="skill-story">{t(`skills.stories.${s.code}`, { defaultValue: s.description ?? '' })}</p>
          </div>
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
            title={
              s.next_level === null
                ? undefined
                : `${full ? t('training.queueFull') : cantAfford ? t('training.noFunds') : t('training.cost')}: ${formatCoinsFull(cost)} ${t('wallet.coinsShort')}`
            }
            onClick={() => void onAdd(s.id)}
          >
            {label}
          </button>
        </article>
        <div className="tier-row">
          {ACTIVE_TIERS.map((tier) => {
            const reached = s.level >= tier.min;
            const tip = `${t(`training.tier.${tier.name}`)}: ${t('training.tierAt', { level: tier.min })}`;
            return (
              <span
                key={tier.name}
                tabIndex={0}
                className={`tier tier-${tier.name}` + (reached ? ' reached' : '')}
                data-tip={tip}
                aria-label={tip}
              >
                {reached ? <SkillIcon code={s.code} /> : <Icon name="medal" />}
              </span>
            );
          })}
        </div>
      </div>
    );
  };

  return (
    <>
      <Tabs base="/umiejetnosci" items={SKILL_TABS} />
      {error && <p className="panel form-error" role="alert">{error}</p>}

      <section className="panel trail-blue">
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

      {tab === 'swiat' ? (
        world && <WorldPanel world={world} coins={coins} queueFull={full} busy={busy} onAdvance={onAdvance} />
      ) : (
        <section className="panel trail-blue">
          <h2>{t('training.basic')}</h2>
          <div className="skill-grid">{skills.skills.map(skillCard)}</div>
        </section>
      )}
    </>
  );
}
