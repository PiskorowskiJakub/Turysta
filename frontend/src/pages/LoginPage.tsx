import { useState, type FormEvent } from 'react';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import FormField from '../components/FormField';

export default function LoginPage() {
  const { t } = useTranslation();
  const { status, login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const registered = (location.state as { registered?: boolean } | null)?.registered === true;

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  if (status === 'authed') return <Navigate to="/" replace />;

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      const user = await login(email, password);
      navigate(user.tutorial_done ? '/' : '/samouczek', { replace: true });
    } catch (err) {
      setError(err instanceof ApiError ? t(`errors.${err.code}`, { defaultValue: err.message }) : t('errors.network'));
    } finally {
      setBusy(false);
    }
  };

  return (
    <form className="card" onSubmit={onSubmit} noValidate>
      <h1>{t('login.title')}</h1>
      {registered && <p className="form-success" role="status">{t('login.registered')}</p>}
      {error && <p className="form-error" role="alert">{error}</p>}
      <FormField label={t('register.email')} name="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" required />
      <FormField label={t('register.password')} name="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} autoComplete="current-password" required />
      <button className="button" type="submit" disabled={busy}>{t('login.submit')}</button>
      <p className="muted"><Link to="/zapomniane-haslo">{t('login.forgot')}</Link></p>
      <p className="muted">{t('login.noAccount')} <Link to="/rejestracja">{t('nav.register')}</Link></p>
    </form>
  );
}
