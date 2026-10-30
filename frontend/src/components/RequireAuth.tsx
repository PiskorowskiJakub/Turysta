import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../auth/AuthContext';

export default function RequireAuth() {
  const { t } = useTranslation();
  const { status } = useAuth();
  const location = useLocation();

  if (status === 'loading') return <p className="muted">{t('loading')}</p>;
  if (status === 'anon') return <Navigate to="/logowanie" replace state={{ from: location.pathname }} />;
  return <Outlet />;
}
