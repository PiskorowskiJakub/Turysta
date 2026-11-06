import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api } from '../api/client';
import { tierName } from '../badges';
import type { NotificationItem } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { formatCoins, formatDate, formatPercent } from '../format';

/** Widok powiadomien na stronie glownej: ostatnie zdarzenia z gry i oznaczanie ich jako przeczytane. */
export default function Notifications() {
  const { t } = useTranslation();
  const { refresh } = useAuth();
  const [items, setItems] = useState<NotificationItem[] | null>(null);

  const load = useCallback(async () => {
    try {
      const r = await api<{ notifications: NotificationItem[] }>('GET', '/notifications');
      setItems(r.notifications);
    } catch {
      setItems([]);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const markRead = async () => {
    await api('POST', '/notifications/read');
    await load();
    await refresh();
  };

  const text = (n: NotificationItem): string => {
    const p = n.payload;
    const base = t(`notifications.${n.type}`, {
      defaultValue: n.type,
      skill: n.type === 'squad_skill_finished' && typeof p.skill === 'string' ? t(`training.squadSkills.names.${p.skill}`, { defaultValue: p.skill }) : p.skill,
      level: p.level,
      job: p.job,
      name: p.name,
      squad: p.squad,
      role: typeof p.role === 'string' ? t(`squad.roles.${p.role}`, { defaultValue: p.role }) : '',
      chapter: p.chapter,
      referrals: p.count,
      amount: typeof p.payout_cents === 'number' ? formatCoins(p.payout_cents) : typeof p.cents === 'number' ? formatCoins(p.cents) : '',
      room: typeof p.room === 'string' ? t(`squad.shelter.rooms.${p.room}`, { defaultValue: p.room }) : '',
      pct: typeof p.pct === 'number' ? formatPercent(p.pct) : '',
      theme: p.theme_name,
      percent: p.percent,
      stamps: p.stamps,
      days: p.streak,
      badge: typeof p.code === 'string' ? t(`squad.badges.names.${p.code}`, { defaultValue: p.code }) : '',
      tier: typeof p.tier === 'number' ? t(`training.tier.${tierName(p.tier)}`) : '',
      flame: typeof p.flame === 'number' ? t(`squad.flame.${p.flame}`) : '',
    });
    // Premia z Kuchni oddzialu dopisana do wyplaty z pracy.
    if (n.type === 'job_finished' && typeof p.kitchen_cents === 'number' && p.kitchen_cents > 0) {
      return `${base} ${t('notifications.job_kitchen', { kitchen: formatCoins(p.kitchen_cents) })}`;
    }
    return base;
  };

  if (items === null) return <p className="muted">{t('loading')}</p>;
  const unread = items.some((n) => !n.read);

  return (
    <section className="panel">
      <h2>{t('notifications.title')}</h2>
      {unread && (
        <button type="button" className="button secondary small notifications-mark" onClick={() => void markRead()}>
          {t('notifications.markRead')}
        </button>
      )}
      {items.length === 0 ? (
        <p className="muted">{t('notifications.empty')}</p>
      ) : (
        <ul className="notifications">
          {items.map((n) => (
            <li key={n.id} className={n.read ? 'read' : 'unread'}>
              <span>{text(n)}</span>
              <time dateTime={n.created_at}>{formatDate(n.created_at)}</time>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
