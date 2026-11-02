import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api } from '../api/client';
import type { ActivityEvent, ActivityFeedResponse } from '../api/types';
import { formatDate } from '../format';
import { usePolling } from '../hooks/usePolling';

/** Krotka lista tego, co ostatnio zrobili znajomi (nowy swiat, cenna odznaka). Ukryta, gdy gracz wylaczyl aktywnosc w prywatnosci. */
export default function FriendActivity() {
  const { t } = useTranslation();
  const [feed, setFeed] = useState<ActivityFeedResponse | null>(null);

  const load = useCallback(async () => {
    try {
      setFeed(await api<ActivityFeedResponse>('GET', '/friends/activity'));
    } catch {
      setFeed(null);
    }
  }, []);
  useEffect(() => {
    void load();
  }, [load]);
  usePolling(() => void load(), 60000);

  if (!feed || !feed.enabled) return null;

  const text = (e: ActivityEvent): string => {
    const name = e.user.name;
    if (e.type === 'world') return t('feed.world', { name, world: e.payload.world });
    const tier = t(`feed.tier.${e.payload.tier ?? 'gold'}`);
    const group = e.payload.group;
    if (!group) return t('feed.badgeWorld', { name, tier, world: e.payload.world });
    const badge = group.startsWith('special_') ? t(`badges.${group}`) : t(`skills.${group}`, { defaultValue: group });
    return t('feed.badge', { name, tier, badge });
  };

  return (
    <section className="panel">
      <h2>{t('feed.title')}</h2>
      {feed.events.length === 0 ? (
        <p className="muted">{t('feed.empty')}</p>
      ) : (
        <ul className="feed">
          {feed.events.map((e) => (
            <li key={e.id}>
              <Link to={`/gracz/${e.user.id}`} className="feed-text">{text(e)}</Link>
              <time dateTime={e.created_at}>{formatDate(e.created_at)}</time>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
