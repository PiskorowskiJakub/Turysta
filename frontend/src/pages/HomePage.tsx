import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../auth/AuthContext';

export default function HomePage() {
  const { t } = useTranslation();
  const { status } = useAuth();
  if (status === 'loading') return <p className="muted">{t('loading')}</p>;
  return (
    <div className="card">
      <h1>{t('home.title')}</h1>
      <p>{status === 'authed' ? t('home.leadAuthed') : t('home.lead')}</p>
      {status === 'authed' ? (
        <Link className="button" to="/profil">{t('home.toProfile')}</Link>
      ) : (
        <p className="actions">
          <Link className="button" to="/rejestracja">{t('nav.register')}</Link>
          <Link className="button secondary" to="/logowanie">{t('nav.login')}</Link>
        </p>
      )}
    </div>
  );
}
