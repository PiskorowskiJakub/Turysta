import { useState, type FormEvent } from 'react';
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import FormField from '../components/FormField';

export default function RegisterPage() {
  const { t } = useTranslation();
  const { status, register } = useAuth();
  const navigate = useNavigate();
  const [params] = useSearchParams();

  const [form, setForm] = useState({
    character_name: '',
    email: '',
    password: '',
    password_confirm: '',
    referral_code: params.get('ref') ?? '',
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [general, setGeneral] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  if (status === 'authed') return <Navigate to="/" replace />;

  const set = (name: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement>) =>
    setForm((f) => ({ ...f, [name]: e.target.value }));

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErrors({});
    setGeneral(null);
    try {
      await register(form);
      navigate('/logowanie', { state: { registered: true } });
    } catch (err) {
      if (err instanceof ApiError) {
        setErrors(err.fields);
        setGeneral(t(`errors.${err.code}`, { defaultValue: err.message }));
      } else {
        setGeneral(t('errors.network'));
      }
    } finally {
      setBusy(false);
    }
  };

  return (
    <form className="card" onSubmit={onSubmit} noValidate>
      <h1>{t('register.title')}</h1>
      {general && <p className="form-error" role="alert">{general}</p>}
      <FormField label={t('register.name')} name="character_name" value={form.character_name} onChange={set('character_name')} error={errors.character_name} autoComplete="nickname" required />
      <FormField label={t('register.email')} name="email" type="email" value={form.email} onChange={set('email')} error={errors.email} autoComplete="email" required />
      <FormField label={t('register.password')} name="password" type="password" value={form.password} onChange={set('password')} error={errors.password} autoComplete="new-password" required />
      <FormField label={t('register.passwordConfirm')} name="password_confirm" type="password" value={form.password_confirm} onChange={set('password_confirm')} error={errors.password_confirm} autoComplete="new-password" required />
      <FormField label={t('register.referral')} name="referral_code" value={form.referral_code} onChange={set('referral_code')} error={errors.referral_code} autoComplete="off" />
      <button className="button" type="submit" disabled={busy}>{t('register.submit')}</button>
      <p className="muted">{t('register.haveAccount')} <Link to="/logowanie">{t('nav.login')}</Link></p>
    </form>
  );
}
