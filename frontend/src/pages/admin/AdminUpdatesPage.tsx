import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../../api/client';
import type { UpdateEntry } from '../../api/types';
import { formatDate } from '../../format';

interface Draft {
  id: number | null;
  title: string;
  body: string;
  released_on: string;
  published: boolean;
}

const today = () => new Date().toISOString().slice(0, 10);
const empty = (): Draft => ({ id: null, title: '', body: '', released_on: today(), published: true });

/** Aktualizacje gry: dziennik zmian czytany pod adresem /aktualizacje. */
export default function AdminUpdatesPage() {
  const { t } = useTranslation();
  const [items, setItems] = useState<UpdateEntry[]>([]);
  const [draft, setDraft] = useState<Draft>(empty);
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
      setItems((await api<{ updates: UpdateEntry[] }>('GET', '/admin/updates')).updates);
    } catch (e) {
      fail(e);
    }
  }, [fail]);

  useEffect(() => {
    void load();
  }, [load]);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setFields({});
    setMessage(null);
    try {
      const body = { title: draft.title, body: draft.body, released_on: draft.released_on, published: draft.published };
      if (draft.id === null) await api('POST', '/admin/updates', body);
      else await api('PUT', `/admin/updates/${draft.id}`, body);
      setMessage({ ok: true, text: t(draft.id === null ? 'admin.updates.created' : 'admin.saved') });
      setDraft(empty());
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
      await api('DELETE', `/admin/updates/${id}`);
      setConfirmId(null);
      if (draft.id === id) setDraft(empty());
      setMessage({ ok: true, text: t('admin.updates.deleted') });
      await load();
    } catch (err) {
      fail(err);
    } finally {
      setBusy(false);
    }
  };

  return (
    <>
      {message && <p className={message.ok ? 'form-success' : 'form-error'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}

      <form className="panel edit-section" onSubmit={(e) => void submit(e)} noValidate>
        <h2>{draft.id === null ? t('admin.updates.new') : t('admin.updates.edit')}</h2>
        <div className="field">
          <label htmlFor="upd-title">{t('admin.fieldTitle')}</label>
          <input id="upd-title" value={draft.title} maxLength={120} onChange={(e) => setDraft({ ...draft, title: e.target.value })} aria-invalid={fields.title ? true : undefined} />
          {fields.title && <p className="field-error">{fields.title}</p>}
        </div>
        <div className="field">
          <label htmlFor="upd-date">{t('admin.updates.date')}</label>
          <input id="upd-date" type="date" value={draft.released_on} onChange={(e) => setDraft({ ...draft, released_on: e.target.value })} aria-invalid={fields.released_on ? true : undefined} />
          {fields.released_on && <p className="field-error">{fields.released_on}</p>}
        </div>
        <div className="field">
          <label htmlFor="upd-body">{t('admin.fieldBody')}</label>
          <textarea id="upd-body" rows={12} value={draft.body} maxLength={8000} onChange={(e) => setDraft({ ...draft, body: e.target.value })} aria-invalid={fields.body ? true : undefined} />
          {fields.body && <p className="field-error">{fields.body}</p>}
          <small className="muted">{t('admin.updates.format')}</small>
        </div>
        <p className="check-row">
          <label><input type="checkbox" checked={draft.published} onChange={(e) => setDraft({ ...draft, published: e.target.checked })} /> {t('admin.published')}</label>
        </p>
        <div className="actions">
          <button className="button" type="submit" disabled={busy}>{draft.id === null ? t('admin.updates.add') : t('admin.save')}</button>
          {draft.id !== null && (
            <button className="button secondary" type="button" onClick={() => { setDraft(empty()); setFields({}); }}>{t('admin.cancel')}</button>
          )}
        </div>
      </form>

      <section className="panel">
        <h2>{t('admin.updates.list')}</h2>
        {items.length === 0 && <p className="muted">{t('admin.updates.empty')}</p>}
        <ul className="admin-list">
          {items.map((u) => (
            <li key={u.id}>
              <div className="admin-list-main">
                <strong>{u.title}</strong>
                <small>{formatDate(`${u.released_on}T12:00:00`)} · {u.published ? t('admin.statusPublished') : t('admin.statusDraft')}</small>
              </div>
              <div className="actions">
                <button type="button" className="button small secondary" onClick={() => { setDraft({ id: u.id, title: u.title, body: u.body, released_on: u.released_on, published: u.published }); setFields({}); setMessage(null); window.scrollTo({ top: 0, behavior: 'smooth' }); }}>{t('admin.edit')}</button>
                {confirmId === u.id ? (
                  <>
                    <button type="button" className="button small" disabled={busy} onClick={() => void remove(u.id)}>{t('admin.confirmDelete')}</button>
                    <button type="button" className="button small secondary" onClick={() => setConfirmId(null)}>{t('admin.cancel')}</button>
                  </>
                ) : (
                  <button type="button" className="button small secondary" onClick={() => setConfirmId(u.id)}>{t('admin.delete')}</button>
                )}
              </div>
            </li>
          ))}
        </ul>
      </section>
    </>
  );
}
