import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api } from '../api/client';
import type { NotificationItem } from '../api/types';
import { formatCoins } from '../format';

export default function Notifications() {
  const { t } = useTranslation();
  const [items, setItems] = useState<NotificationItem[]>([]);

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
  };

  const text = (n: NotificationItem): string => {
    const p = n.payload;
    return t(`notifications.${n.type}`, {
      defaultValue: n.type,
      skill: p.skill,
      level: p.level,
      job: p.job,
      chapter: p.chapter,
      amount: typeof p.payout_cents === 'number' ? formatCoins(p.payout_cents) : '',
    });
  };

  if (items.length === 0) return null;
  const unread = items.some((n) => !n.read);

  return (
    <>
      <h2>{t('notifications.title')}</h2>
      <ul className="notifications">
        {items.slice(0, 10).map((n) => (
          <li key={n.id} className={n.read ? 'read' : 'unread'}>
            {text(n)}
          </li>
        ))}
      </ul>
      {unread && (
        <button type="button" className="button secondary small" onClick={() => void markRead()}>
          {t('notifications.markRead')}
        </button>
      )}
    </>
  );
}
