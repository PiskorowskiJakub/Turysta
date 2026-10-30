import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../auth/AuthContext';
import GameHeader from './GameHeader';

const linkClass = ({ isActive }: { isActive: boolean }) => (isActive ? 'nav-active' : undefined);

export default function Layout() {
  const { t } = useTranslation();
  const { status, logout } = useAuth();
  const navigate = useNavigate();

  const onLogout = async () => {
    await logout();
    navigate('/');
  };

  return (
    <>
      <nav>
        <NavLink to="/" end className={linkClass}>{t('nav.home')}</NavLink>
        {status === 'authed' ? (
          <>
            <NavLink to="/profil" className={linkClass}>{t('nav.profile')}</NavLink>
            <button type="button" className="nav-button" onClick={onLogout}>{t('nav.logout')}</button>
          </>
        ) : status === 'anon' ? (
          <>
            <NavLink to="/logowanie" className={linkClass}>{t('nav.login')}</NavLink>
            <NavLink to="/rejestracja" className={linkClass}>{t('nav.register')}</NavLink>
          </>
        ) : null}
      </nav>

      <div className="Page">
        <header>
          <GameHeader />
        </header>
        <section>
          <Outlet />
        </section>
      </div>

      <footer>
        <h3>{t('footer')}</h3>
      </footer>
    </>
  );
}
