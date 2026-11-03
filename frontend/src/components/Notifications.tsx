import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api } from '../api/client';
import type { NotificationItem } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { formatCoins, formatDate } from '../format';

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
    return t(`notifications.${n.type}`, {
      defaultValue: n.type,
      skill: p.skill,
      level: p.level,
      job: p.job,
      name: p.name,
      chapter: p.chapter,
      count: p.count,
      amount: typeof p.payout_cents === 'number' ? formatCoins(p.payout_cents) : typeof p.cents === 'number' ? formatCoins(p.cents) : '',
    });
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
