import { Navigate, Route, Routes } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../auth/AuthContext';
import Tabs, { type TabItem } from '../components/Tabs';
import AdminUpdatesPage from './admin/AdminUpdatesPage';
import AdminBugReportsPage from './admin/AdminBugReportsPage';
import AdminAnnouncementsPage from './admin/AdminAnnouncementsPage';
import AdminFactsPage from './admin/AdminFactsPage';
import { AdminAuditPage, AdminLoginsPage } from './admin/AdminLogsPages';
import { AdminBannedWordsPage, AdminSquadListPage, AdminSquadReportsPage } from './admin/AdminSquadsPage';
import AdminStatsPage from './admin/AdminStatsPage';
import AdminUserPage from './admin/AdminUserPage';
import AdminUsersPage from './admin/AdminUsersPage';

/** Panel administratora: karty i podkarty jak w umiejetnosciach. Dostep tylko dla roli admin (serwer sprawdza to przy kazdym zadaniu). */
export default function AdminPage() {
  const { t } = useTranslation();
  const { me } = useAuth();
  if (!me) return null;
  if (me.user.role !== 'admin') return <Navigate to="/" replace />;

  const tabs: TabItem[] = [
    { path: 'przeglad', label: t('admin.tabs.overview') },
    { path: 'gracze', label: t('admin.tabs.users') },
    {
      path: 'tresci',
      label: t('admin.tabs.content'),
      children: [
        { path: 'ogloszenia', label: t('admin.tabs.announcements') },
        { path: 'aktualizacje', label: t('admin.tabs.updates') },
        { path: 'ciekawostki', label: t('admin.tabs.facts') },
      ],
    },
    {
      path: 'oddzialy',
      label: t('admin.tabs.squads'),
      children: [
        { path: 'lista', label: t('admin.tabs.squadList') },
        { path: 'zgloszenia', label: t('admin.tabs.squadReports') },
      ],
    },
    {
      path: 'zgloszenia',
      label: t('admin.tabs.bugs'),
      children: [
        { path: 'lista', label: t('admin.tabs.bugList') },
        { path: 'slowa', label: t('admin.tabs.bannedWords') },
      ],
    },
    {
      path: 'logi',
      label: t('admin.tabs.logs'),
      children: [
        { path: 'logowania', label: t('admin.tabs.logins') },
        { path: 'dzialania', label: t('admin.tabs.audit') },
      ],
    },
  ];

  return (
    <>
      <h1>{t('admin.title')}</h1>
      <Tabs base="/admin" items={tabs} />
      <Routes>
        <Route index element={<Navigate to="przeglad" replace />} />
        <Route path="przeglad" element={<AdminStatsPage />} />
        <Route path="gracze" element={<AdminUsersPage />} />
        <Route path="gracze/:id/*" element={<AdminUserPage />} />
        <Route path="tresci" element={<Navigate to="ogloszenia" replace />} />
        <Route path="tresci/ogloszenia" element={<AdminAnnouncementsPage />} />
        <Route path="tresci/aktualizacje" element={<AdminUpdatesPage />} />
        <Route path="tresci/ciekawostki" element={<AdminFactsPage />} />
        <Route path="oddzialy" element={<Navigate to="lista" replace />} />
        <Route path="oddzialy/lista" element={<AdminSquadListPage />} />
        <Route path="oddzialy/zgloszenia" element={<AdminSquadReportsPage />} />
        <Route path="zgloszenia" element={<Navigate to="lista" replace />} />
        <Route path="zgloszenia/lista" element={<AdminBugReportsPage />} />
        <Route path="zgloszenia/slowa" element={<AdminBannedWordsPage />} />
        <Route path="oddzialy/slowa" element={<Navigate to="/admin/zgloszenia/slowa" replace />} />
        <Route path="logi" element={<Navigate to="logowania" replace />} />
        <Route path="logi/logowania" element={<AdminLoginsPage />} />
        <Route path="logi/dzialania" element={<AdminAuditPage />} />
        <Route path="*" element={<Navigate to="/admin/przeglad" replace />} />
      </Routes>
    </>
  );
}
