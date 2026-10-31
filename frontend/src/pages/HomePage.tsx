import { Link, Navigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../auth/AuthContext';
import Dashboard from '../components/Dashboard';

export default function HomePage() {
  const { t } = useTranslation();
  const { status, me } = useAuth();
  if (status === 'loading') return <p className="muted">{t('loading')}</p>;
  if (status === 'authed') {
    if (me && !me.user.tutorial_done) return <Navigate to="/samouczek" replace />;
    return <Dashboard />;
  }
  return (
    <div className="card">
      <h1>{t('home.title')}</h1>
      <p>{t('home.lead')}</p>
      <p className="actions">
        <Link className="button" to="/rejestracja">{t('nav.register')}</Link>
        <Link className="button secondary" to="/logowanie">{t('nav.login')}</Link>
      </p>
    </div>
  );
}
