import { Navigate, Route, Routes } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../auth/AuthContext';
import Tabs, { type TabItem } from '../components/Tabs';
import AdminAnnouncementsPage from './admin/AdminAnnouncementsPage';
import AdminFactsPage from './admin/AdminFactsPage';
import { AdminAuditPage, AdminLoginsPage } from './admin/AdminLogsPages';
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
        { path: 'ciekawostki', label: t('admin.tabs.facts') },
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
        <Route path="tresci/ciekawostki" element={<AdminFactsPage />} />
        <Route path="logi" element={<Navigate to="logowania" replace />} />
        <Route path="logi/logowania" element={<AdminLoginsPage />} />
        <Route path="logi/dzialania" element={<AdminAuditPage />} />
        <Route path="*" element={<Navigate to="/admin/przeglad" replace />} />
      </Routes>
    </>
  );
}
