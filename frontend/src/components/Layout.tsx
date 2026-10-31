import { useEffect, useState } from 'react';
import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api } from '../api/client';
import type { Avatar } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { formatCoins } from '../format';
import { Icon, type IconName } from './icons';

function MenuLink({ to, icon, label, end }: { to: string; icon: IconName; label: string; end?: boolean }) {
  return (
    <li>
      <NavLink to={to} end={end} className={({ isActive }) => 'menu-item' + (isActive ? ' active' : '')}>
        <Icon name={icon} />
        <span>{label}</span>
      </NavLink>
    </li>
  );
}

export default function Layout() {
  const { t } = useTranslation();
  const { status, me, logout } = useAuth();
  const navigate = useNavigate();
  const [fact, setFact] = useState<string | null>(null);
  const [avatars, setAvatars] = useState<Avatar[]>([]);

  useEffect(() => {
    api<{ text: string | null }>('GET', '/facts/today')
      .then((r) => setFact(r.text))
      .catch(() => setFact(null));
  }, []);

  useEffect(() => {
    if (status !== 'authed') return;
    api<{ avatars: Avatar[] }>('GET', '/avatars')
      .then((r) => setAvatars(r.avatars))
      .catch(() => setAvatars([]));
  }, [status]);

  const onLogout = async () => {
    await logout();
    navigate('/');
  };

  const authed = status === 'authed' && me;
  const avatar = authed ? avatars.find((a) => a.id === me.user.avatar_id) : undefined;

  return (
    <div className="shell">
      <nav className="menu" aria-label={t('nav.menu')}>
        <div className="menu-title" role="img" aria-label={t('nav.menu')} />
        <ul>
          <MenuLink to="/" end icon="home" label={t('nav.home')} />
          {authed ? (
            <>
              <MenuLink to="/profil" icon="profile" label={t('nav.profile')} />
              <MenuLink to="/umiejetnosci" icon="skills" label={t('nav.skills')} />
              <MenuLink to="/praca" icon="job" label={t('nav.jobs')} />
              <li>
                <button type="button" className="menu-item" onClick={() => void onLogout()}>
                  <Icon name="logout" />
                  <span>{t('nav.logout')}</span>
                </button>
              </li>
            </>
          ) : status === 'anon' ? (
            <>
              <MenuLink to="/logowanie" icon="login" label={t('nav.login')} />
              <MenuLink to="/rejestracja" icon="register" label={t('nav.register')} />
            </>
          ) : null}
        </ul>
      </nav>

      <div className="top">
        <div className="info-board">
          <div className="info-text">{fact ?? t('tagline')}</div>
        </div>
        {authed && (
          <div className="resource-board">
            <ul className="res-list">
              <li title={t('wallet.coins')}><Icon name="coins" />{formatCoins(me.wallet.coins_cents)}</li>
              <li title={t('wallet.tickets')}><Icon name="ticket" />{me.wallet.tickets}</li>
              <li title={t('wallet.points')}><Icon name="points" />{me.wallet.points.toLocaleString('pl-PL')}</li>
            </ul>
            {avatar && (
              <div className="res-avatar">
                <img src={avatar.image_url} alt={me.user.character_name} />
              </div>
            )}
          </div>
        )}
      </div>

      <main className="board">
        <div className="board-top" />
        <div className="board-mid">
          <Outlet />
        </div>
        <div className="board-bottom" />
      </main>

      <footer className="site-footer">{t('footer')}</footer>
    </div>
  );
}
