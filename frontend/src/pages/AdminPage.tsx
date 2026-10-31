import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { Navigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { Announcement } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { formatDate } from '../format';

interface Draft {
  id: number | null;
  title: string;
  body: string;
  published: boolean;
  pinned: boolean;
}

const EMPTY: Draft = { id: null, title: '', body: '', published: true, pinned: false };

/** Panel administratora. Dostep tylko dla roli admin (serwer sprawdza to przy kazdym zadaniu). */
export default function AdminPage() {
  const { t } = useTranslation();
  const { me } = useAuth();
  const [items, setItems] = useState<Announcement[]>([]);
  const [draft, setDraft] = useState<Draft>(EMPTY);
  const [fields, setFields] = useState<Record<string, string>>({});
  const [message, setMessage] = useState<{ ok: boolean; text: string } | null>(null);
  const [confirmId, setConfirmId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);

  const fail = useCallback(
    (e: unknown) => {
      if (e instanceof ApiError) {
        setFields(e.fields);
        setMessage({ ok: false, text: t(`errors.${e.code}`, { defaultValue: e.message }) });
      } else {
        setMessage({ ok: false, text: t('errors.network') });
      }
    },
    [t],
  );

  const load = useCallback(async () => {
    try {
      setItems((await api<{ announcements: Announcement[] }>('GET', '/admin/announcements')).announcements);
    } catch (e) {
      fail(e);
    }
  }, [fail]);

  const isAdmin = me?.user.role === 'admin';
  useEffect(() => {
    if (isAdmin) void load();
  }, [isAdmin, load]);

  if (!me) return null;
  if (!isAdmin) return <Navigate to="/" replace />;

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setFields({});
    setMessage(null);
    try {
      const body = { title: draft.title, body: draft.body, published: draft.published, pinned: draft.pinned };
      if (draft.id === null) await api('POST', '/admin/announcements', body);
      else await api('PUT', `/admin/announcements/${draft.id}`, body);
      setMessage({ ok: true, text: t(draft.id === null ? 'admin.created' : 'admin.saved') });
      setDraft(EMPTY);
      await load();
    } catch (err) {
      fail(err);
    } finally {
      setBusy(false);
    }
  };

  const remove = async (id: number) => {
    setBusy(true);
    setMessage(null);
    try {
      await api('DELETE', `/admin/announcements/${id}`);
      setConfirmId(null);
      if (draft.id === id) setDraft(EMPTY);
      setMessage({ ok: true, text: t('admin.deleted') });
      await load();
    } catch (err) {
      fail(err);
    } finally {
      setBusy(false);
    }
  };

  return (
    <>
      <h1>{t('admin.title')}</h1>
      {message && <p className={message.ok ? 'form-success' : 'form-error'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}

      <form className="panel edit-section" onSubmit={(e) => void submit(e)} noValidate>
        <h2>{draft.id === null ? t('admin.newAnnouncement') : t('admin.editAnnouncement')}</h2>
        <div className="field">
          <label htmlFor="ann-title">{t('admin.fieldTitle')}</label>
          <input id="ann-title" value={draft.title} maxLength={120} onChange={(e) => setDraft({ ...draft, title: e.target.value })} aria-invalid={fields.title ? true : undefined} />
          {fields.title && <p className="field-error">{fields.title}</p>}
        </div>
        <div className="field">
          <label htmlFor="ann-body">{t('admin.fieldBody')}</label>
          <textarea id="ann-body" rows={6} value={draft.body} maxLength={2000} onChange={(e) => setDraft({ ...draft, body: e.target.value })} aria-invalid={fields.body ? true : undefined} />
          {fields.body && <p className="field-error">{fields.body}</p>}
        </div>
        <p className="check-row">
          <label><input type="checkbox" checked={draft.published} onChange={(e) => setDraft({ ...draft, published: e.target.checked })} /> {t('admin.published')}</label>
          <label><input type="checkbox" checked={draft.pinned} onChange={(e) => setDraft({ ...draft, pinned: e.target.checked })} /> {t('admin.pinned')}</label>
        </p>
        <div className="actions">
          <button className="button" type="submit" disabled={busy}>{draft.id === null ? t('admin.add') : t('admin.save')}</button>
          {draft.id !== null && (
            <button className="button secondary" type="button" onClick={() => { setDraft(EMPTY); setFields({}); }}>{t('admin.cancel')}</button>
          )}
        </div>
      </form>

      <section className="panel">
        <h2>{t('admin.list')}</h2>
        {items.length === 0 && <p className="muted">{t('admin.empty')}</p>}
        <ul className="admin-list">
          {items.map((a) => (
            <li key={a.id}>
              <div className="admin-list-main">
                <strong>{a.pinned && '★ '}{a.title}</strong>
                <small>{formatDate(a.created_at)} · {a.published ? t('admin.statusPublished') : t('admin.statusDraft')}</small>
              </div>
              <div className="actions">
                <button type="button" className="button small secondary" onClick={() => { setDraft({ id: a.id, title: a.title, body: a.body, published: a.published, pinned: a.pinned }); setFields({}); setMessage(null); window.scrollTo({ top: 0, behavior: 'smooth' }); }}>{t('admin.edit')}</button>
                {confirmId === a.id ? (
                  <>
                    <button type="button" className="button small" disabled={busy} onClick={() => void remove(a.id)}>{t('admin.confirmDelete')}</button>
                    <button type="button" className="button small secondary" onClick={() => setConfirmId(null)}>{t('admin.cancel')}</button>
                  </>
                ) : (
                  <button type="button" className="button small secondary" onClick={() => setConfirmId(a.id)}>{t('admin.delete')}</button>
                )}
              </div>
            </li>
          ))}
        </ul>
      </section>
    </>
  );
}
