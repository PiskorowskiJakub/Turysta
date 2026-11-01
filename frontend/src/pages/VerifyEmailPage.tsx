import { useEffect, useRef, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';

type State = 'loading' | 'ok' | 'error';

/** Strona, na ktora prowadzi link z maila: potwierdza adres i pokazuje wynik. */
export default function VerifyEmailPage() {
  const { t } = useTranslation();
  const { status, refresh } = useAuth();
  const [params] = useSearchParams();
  const token = params.get('token') ?? '';
  const [state, setState] = useState<State>('loading');
  const [message, setMessage] = useState('');

  // Token jest jednorazowy, wiec zadanie idzie tylko raz (takze w trybie deweloperskim React z podwojnym efektem).
  const sent = useRef(false);

  useEffect(() => {
    if (sent.current) return;
    sent.current = true;
    api<{ message: string }>('GET', `/auth/verify-email?token=${encodeURIComponent(token)}`)
      .then(() => {
        setState('ok');
        void refresh();
      })
      .catch((err) => {
        setMessage(err instanceof ApiError ? t(`errors.${err.code}`, { defaultValue: err.message }) : t('errors.network'));
        setState('error');
      });
  }, [token, refresh, t]);

  return (
    <div className="card">
      <h1>{t('verifyPage.title')}</h1>
      {state === 'loading' && <p>{t('verifyPage.loading')}</p>}
      {state === 'ok' && (
        <>
          <p className="form-notice" role="status">{t('verifyPage.ok')}</p>
          <p className="actions">
            {status === 'authed' ? (
              <Link className="button" to="/start">{t('verifyPage.toGame')}</Link>
            ) : (
              <Link className="button" to="/logowanie">{t('verifyPage.toLogin')}</Link>
            )}
          </p>
        </>
      )}
      {state === 'error' && (
        <>
          <p className="form-error" role="alert">{message}</p>
          <p>{t('verifyPage.errorHint')}</p>
          <p className="actions">
            <Link className="button" to={status === 'authed' ? '/start' : '/logowanie'}>
              {status === 'authed' ? t('verifyPage.toGame') : t('verifyPage.toLogin')}
            </Link>
          </p>
        </>
      )}
    </div>
  );
}
