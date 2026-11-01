import { useState, type FormEvent } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import FormField from '../components/FormField';

/** Prosba o link do ustawienia nowego hasla; odpowiedz jest taka sama niezaleznie od tego, czy konto istnieje. */
export default function ForgotPasswordPage() {
  const { t } = useTranslation();
  const { status } = useAuth();
  const [email, setEmail] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [fieldError, setFieldError] = useState<string | undefined>();
  const [sent, setSent] = useState(false);
  const [busy, setBusy] = useState(false);

  if (status === 'authed') return <Navigate to="/" replace />;

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    setFieldError(undefined);
    try {
      await api('POST', '/auth/forgot-password', { email });
      setSent(true);
    } catch (err) {
      if (err instanceof ApiError) {
        setFieldError(err.fields.email);
        if (!err.fields.email) setError(t(`errors.${err.code}`, { defaultValue: err.message }));
      } else {
        setError(t('errors.network'));
      }
    } finally {
      setBusy(false);
    }
  };

  if (sent) {
    return (
      <div className="card">
        <h1>{t('forgot.title')}</h1>
        <p className="form-success" role="status">{t('forgot.sent', { email })}</p>
        <p className="muted">{t('forgot.sentHint')}</p>
        <p className="actions"><Link className="button" to="/logowanie">{t('forgot.backToLogin')}</Link></p>
      </div>
    );
  }

  return (
    <form className="card" onSubmit={(e) => void onSubmit(e)} noValidate>
      <h1>{t('forgot.title')}</h1>
      <p>{t('forgot.lead')}</p>
      {error && <p className="form-error" role="alert">{error}</p>}
      <FormField label={t('register.email')} name="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} error={fieldError} autoComplete="email" required />
      <button className="button" type="submit" disabled={busy || !email}>{t('forgot.submit')}</button>
      <p className="muted"><Link to="/logowanie">{t('forgot.backToLogin')}</Link></p>
    </form>
  );
}
