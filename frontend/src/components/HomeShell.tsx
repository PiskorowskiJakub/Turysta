import { Navigate, Route, Routes } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api } from '../api/client';
import type { Announcement } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { usePolling } from '../hooks/usePolling';
import { useEffect, useState } from 'react';
import AnnouncementList from './AnnouncementList';
import Dashboard from './Dashboard';
import Notifications from './Notifications';
import Tabs, { type TabItem } from './Tabs';

function AnnouncementArchive() {
  const { t } = useTranslation();
  const [items, setItems] = useState<Announcement[] | null>(null);
  useEffect(() => {
    api<{ announcements: Announcement[] }>('GET', '/announcements')
      .then((r) => setItems(r.announcements))
      .catch(() => setItems([]));
  }, []);
  return (
    <section className="panel">
      <h2>{t('home.board.title')}</h2>
      {items === null ? <p className="muted">{t('loading')}</p> : <AnnouncementList items={items} />}
    </section>
  );
}

/** Strona glowna zalogowanego gracza z zakladkami: przeglad ("Plecak"), powiadomienia i archiwum ogloszen. */
export default function HomeShell() {
  const { t } = useTranslation();
  const { me, refresh } = useAuth();
  usePolling(() => void refresh(), 30000);

  if (!me) return null;
  if (!me.user.tutorial_done) return <Navigate to="/samouczek" replace />;

  const unread = me.notifications_unread;
  const tabs: TabItem[] = [
    { path: 'przeglad', label: t('home.tabs.overview') },
    { path: 'powiadomienia', label: unread > 0 ? `${t('home.tabs.notifications')} (${unread})` : t('home.tabs.notifications') },
    { path: 'ogloszenia', label: t('home.tabs.announcements') },
  ];

  return (
    <>
      <Tabs base="/start" items={tabs} />
      <Routes>
        <Route path="przeglad" element={<Dashboard />} />
        <Route path="powiadomienia" element={<Notifications />} />
        <Route path="ogloszenia" element={<AnnouncementArchive />} />
        <Route path="*" element={<Navigate to="/start/przeglad" replace />} />
      </Routes>
    </>
  );
}
