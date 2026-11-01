import { useState, type FormEvent } from 'react';
import { Link, Navigate, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import FormField from '../components/FormField';

/** Strona z linku w mailu: ustawienie nowego hasla. */
export default function ResetPasswordPage() {
  const { t } = useTranslation();
  const { status } = useAuth();
  const [params] = useSearchParams();
  const token = params.get('token') ?? '';
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [fields, setFields] = useState<Record<string, string>>({});
  const [done, setDone] = useState(false);
  const [busy, setBusy] = useState(false);

  if (status === 'authed' && !done) return <Navigate to="/" replace />;

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    setFields({});
    try {
      await api('POST', '/auth/reset-password', { token, password, password_confirm: confirm });
      setDone(true);
    } catch (err) {
      if (err instanceof ApiError) {
        setFields(err.fields);
        if (Object.keys(err.fields).length === 0) setError(t(`errors.${err.code}`, { defaultValue: err.message }));
      } else {
        setError(t('errors.network'));
      }
    } finally {
      setBusy(false);
    }
  };

  if (done) {
    return (
      <div className="card">
        <h1>{t('resetPassword.title')}</h1>
        <p className="form-success" role="status">{t('resetPassword.done')}</p>
        <p className="actions"><Link className="button" to="/logowanie">{t('resetPassword.toLogin')}</Link></p>
      </div>
    );
  }

  if (!token) {
    return (
      <div className="card">
        <h1>{t('resetPassword.title')}</h1>
        <p className="form-error" role="alert">{t('errors.invalid_token')}</p>
        <p className="actions"><Link className="button" to="/zapomniane-haslo">{t('resetPassword.askAgain')}</Link></p>
      </div>
    );
  }

  return (
    <form className="card" onSubmit={(e) => void onSubmit(e)} noValidate>
      <h1>{t('resetPassword.title')}</h1>
      <p>{t('resetPassword.lead')}</p>
      {error && (
        <p className="form-error" role="alert">
          {error} {error === t('errors.invalid_token') && <Link to="/zapomniane-haslo">{t('resetPassword.askAgain')}</Link>}
        </p>
      )}
      <FormField label={t('editProfile.newPassword')} name="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} error={fields.password} autoComplete="new-password" required />
      <FormField label={t('editProfile.newPasswordConfirm')} name="password_confirm" type="password" value={confirm} onChange={(e) => setConfirm(e.target.value)} error={fields.password_confirm} autoComplete="new-password" required />
      <button className="button" type="submit" disabled={busy || !password || !confirm}>{t('resetPassword.submit')}</button>
    </form>
  );
}
