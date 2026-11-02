import { useState, type FormEvent } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';

const MAX = 2000;

/** Formularz zgloszenia bledu; adres strony, z ktorej gracz przyszedl, dolacza sie automatycznie. */
export default function BugReportPage() {
  const { t } = useTranslation();
  const location = useLocation();
  const from = (location.state as { from?: string } | null)?.from;
  const [message, setMessage] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [fieldError, setFieldError] = useState<string | undefined>();
  const [busy, setBusy] = useState(false);
  const [sent, setSent] = useState(false);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    setFieldError(undefined);
    try {
      await api('POST', '/bug-reports', { message, page: from ?? '' });
      setSent(true);
      setMessage('');
    } catch (err) {
      if (err instanceof ApiError) {
        setFieldError(err.fields.message);
        setError(err.fields.message ? null : t(`errors.${err.code}`, { defaultValue: err.message }));
      } else {
        setError(t('errors.network'));
      }
    } finally {
      setBusy(false);
    }
  };

  if (sent) {
    return (
      <section className="card">
        <h1>{t('bugReport.title')}</h1>
        <p className="form-success" role="status">{t('bugReport.sent')}</p>
        <div className="actions">
          <button type="button" className="button secondary" onClick={() => setSent(false)}>{t('bugReport.another')}</button>
          <Link className="button" to="/start">{t('bugReport.back')}</Link>
        </div>
      </section>
    );
  }

  return (
    <form className="card" onSubmit={(e) => void onSubmit(e)} noValidate>
      <h1>{t('bugReport.title')}</h1>
      <p>{t('bugReport.intro')}</p>
      {from && <p className="muted">{t('bugReport.about', { page: from })}</p>}
      {error && <p className="form-error" role="alert">{error}</p>}
      <div className="field">
        <label htmlFor="bug-message">{t('bugReport.label')}</label>
        <textarea
          id="bug-message"
          rows={8}
          value={message}
          maxLength={MAX}
          placeholder={t('bugReport.placeholder')}
          onChange={(e) => setMessage(e.target.value)}
          aria-invalid={fieldError ? true : undefined}
        />
        {fieldError && <p className="field-error">{fieldError}</p>}
        <small className="muted">{t('bugReport.counter', { n: message.length, max: MAX })}</small>
      </div>
      <p className="muted">{t('bugReport.privacy')}</p>
      <button className="button" type="submit" disabled={busy}>{t('bugReport.submit')}</button>
    </form>
  );
}
