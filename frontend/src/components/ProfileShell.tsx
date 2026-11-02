import { Navigate, Route, Routes } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../auth/AuthContext';
import FriendsPage from '../pages/FriendsPage';
import ComparePage from '../pages/ComparePage';
import ProfilePage from '../pages/ProfilePage';
import Tabs, { type TabItem } from './Tabs';

/** Profil gracza z zakladkami: przeglad (karta, odznaki, statystyki) i znajomi. */
export default function ProfileShell() {
  const { t } = useTranslation();
  const { me } = useAuth();
  if (!me) return null;
  if (!me.user.tutorial_done) return <Navigate to="/samouczek" replace />;

  const tabs: TabItem[] = [
    { path: 'przeglad', label: t('profile.tabs.overview') },
    { path: 'znajomi', label: t('profile.tabs.friends') },
  ];

  return (
    <>
      <Tabs base="/profil" items={tabs} />
      <Routes>
        <Route path="przeglad" element={<ProfilePage />} />
        <Route path="znajomi" element={<FriendsPage />} />
        <Route path="znajomi/porownaj/:id" element={<ComparePage />} />
        <Route path="*" element={<Navigate to="/profil/przeglad" replace />} />
      </Routes>
    </>
  );
}
