import { useEffect, useState } from 'react';
import { Link, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { Avatar } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import Coins from './Coins';
import Points from './Points';
import PlayerAvatar from './PlayerAvatar';
import { formatDate } from '../format';
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

function BrandMark() {
  // Znak szlaku: bialy-czerwony-bialy w gorskim trojkacie.
  return (
    <svg className="brand-mark" viewBox="0 0 40 40" aria-hidden="true">
      <path d="M2 34 14 10l7 13 5-8 12 19z" fill="#34493b" stroke="#d6c48c" strokeWidth="2" strokeLinejoin="round" />
      <rect x="15" y="25" width="10" height="9" rx="1.5" fill="#f3ecd8" />
      <rect x="15" y="28" width="10" height="3" fill="#d8473a" />
    </svg>
  );
}

/** Pasek dla gracza, ktory nie potwierdzil adresu e-mail; pozwala poprosic o nowy link. */
function VerifyBanner({ email, deadline }: { email: string; deadline: string | null }) {
  const { t } = useTranslation();
  const { resendVerification, refresh } = useAuth();
  const [busy, setBusy] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const onResend = async () => {
    setBusy(true);
    setNotice(null);
    setError(null);
    try {
      await resendVerification();
      setNotice(t('verify.sent', { email }));
    } catch (err) {
      if (err instanceof ApiError && err.code === 'already_verified') {
        await refresh();
        return;
      }
      setError(err instanceof ApiError ? t(`errors.${err.code}`, { defaultValue: err.message }) : t('errors.network'));
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="verify-banner" role="status">
      <div className="verify-text">
        <b>{t('verify.title')}</b>
        <span>{t('verify.text', { email })}</span>
        {deadline && <span className="verify-deadline">{t('verify.deadline', { date: formatDate(deadline) })}</span>}
        {notice && <span className="verify-ok">{notice}</span>}
        {error && <span className="verify-err" role="alert">{error}</span>}
      </div>
      <button type="button" className="button small" disabled={busy} onClick={() => void onResend()}>{t('verify.resend')}</button>
    </div>
  );
}

export default function Layout() {
  const { t } = useTranslation();
  const { status, me, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
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
    <>
      <header className="topbar">
        <div className="topbar-inner">
          <NavLink to="/" className="brand">
            <BrandMark />
            <span className="brand-name">Dwa Dukaty</span>
          </NavLink>
          <div className="fact">
            <b>{t('fact')}</b>
            <span>{fact ?? t('tagline')}</span>
          </div>
          {status === 'anon' && (
            <div className="topbar-cta">
              <Link className="button small secondary" to="/logowanie">{t('nav.login')}</Link>
              <Link className="button small" to="/rejestracja">{t('nav.register')}</Link>
            </div>
          )}
          {authed && (
            <div className="plaque">
              <div className="plaque-who">
                <PlayerAvatar src={avatar?.image_url} name={me.user.character_name} size={48} />
                <span className="who-wrap">
                  <span className="who">{me.user.character_name}</span>
                  {me.progress && (
                    <span className="where">
                      {t('world.worldNo', { n: me.progress.world_ordinal })} · {t('world.chapterNo', { n: me.progress.chapter_ordinal })}
                    </span>
                  )}
                </span>
              </div>
              <ul className="plaque-stats">
                <li title={t('wallet.coins')}>
                  <Icon name="coins" />
                  <span><b><Coins cents={me.wallet.coins_cents} /></b><small>{t('wallet.coins')}</small></span>
                </li>
                <li className="tickets" title={t('wallet.tickets')}>
                  <Icon name="ticket" />
                  <span><b>{me.wallet.tickets}</b><small>{t('wallet.tickets')}</small></span>
                </li>
                <li className="points" title={t('wallet.points')}>
                  <Icon name="points" />
                  <span><b><Points value={me.wallet.points} /></b><small>{t('wallet.points')}</small></span>
                </li>
              </ul>
            </div>
          )}
        </div>
      </header>

      {authed && !me.user.email_verified && <VerifyBanner email={me.user.email} deadline={me.user.email_verification_deadline} />}

      <div className="shell">
        <nav className="menu" aria-label={t('nav.menu')}>
          <ul>
            <MenuLink to={authed ? '/start' : '/'} end={!authed} icon="home" label={t('nav.home')} />
            {authed ? (
              <>
                <MenuLink to="/profil" icon="profile" label={t('nav.profile')} />
                <MenuLink to="/umiejetnosci" icon="skills" label={t('nav.skills')} />
                <MenuLink to="/praca" icon="job" label={t('nav.jobs')} />
                <MenuLink to="/odznaki" icon="medal" label={t('nav.badges')} />
                <MenuLink to="/ranking" icon="points" label={t('nav.ranking')} />
                {me?.user.role === 'admin' && <MenuLink to="/admin" icon="admin" label={t('nav.admin')} />}
                <li>
                  <span className="menu-item disabled" aria-disabled="true" title={t('nav.soon')}>
                    <Icon name="team" />
                    <span>{t('nav.squad')}</span>
                    <small className="soon">{t('nav.soon')}</small>
                  </span>
                </li>
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

        <main className="content">
          <Outlet />
        </main>
      </div>

      <footer className="site-footer">
        <span>{t('footer')}</span>
        <nav className="footer-links" aria-label={t('footerLinks.terms')}>
          <Link to="/regulamin">{t('footerLinks.terms')}</Link>
          <Link to="/polityka-prywatnosci">{t('footerLinks.privacy')}</Link>
          {authed && <Link to="/zglos-blad" state={{ from: location.pathname }}>{t('footerLinks.bug')}</Link>}
        </nav>
      </footer>
    </>
  );
}
