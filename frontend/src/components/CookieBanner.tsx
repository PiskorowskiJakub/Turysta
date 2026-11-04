import { useEffect, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { getConsent, setConsent, trackPageview } from '../lib/analytics';

export const OPEN_COOKIE_SETTINGS = 'turysta:cookie-settings';

/** Baner zgody na analityke oraz wysylanie odslon strony przy zmianie trasy (tylko po zgodzie, bez panelu admina). */
export default function CookieBanner() {
  const { t } = useTranslation();
  const { pathname } = useLocation();
  const [open, setOpen] = useState(false);

  useEffect(() => {
    setOpen(getConsent() === null);
    const reopen = () => setOpen(true);
    window.addEventListener(OPEN_COOKIE_SETTINGS, reopen);
    return () => window.removeEventListener(OPEN_COOKIE_SETTINGS, reopen);
  }, []);

  useEffect(() => {
    if (!pathname.startsWith('/admin')) trackPageview(pathname);
  }, [pathname]);

  if (!open) return null;

  const choose = (value: 'granted' | 'denied') => {
    setConsent(value);
    setOpen(false);
    if (value === 'granted' && !pathname.startsWith('/admin')) trackPageview(pathname);
  };

  return (
    <section className="cookie-banner" role="dialog" aria-label={t('cookies.title')}>
      <p>
        {t('cookies.text')} <Link to="/polityka-prywatnosci">{t('cookies.more')}</Link>
      </p>
      <div className="actions">
        <button type="button" className="button small" onClick={() => choose('granted')}>{t('cookies.accept')}</button>
        <button type="button" className="button small secondary" onClick={() => choose('denied')}>{t('cookies.reject')}</button>
      </div>
    </section>
  );
}
