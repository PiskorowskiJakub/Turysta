import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../../api/client';
import type { Fact } from '../../api/types';

interface Draft {
  id: number | null;
  text: string;
  active: boolean;
}

/** Zgodne z FactService::TEXT_MAX: tyle znakow miesci sie w pasku z ciekawostka. */
const TEXT_MAX = 160;

const EMPTY: Draft = { id: null, text: '', active: true };

/** Ciekawostki dnia (pasek u gory strony): dodawanie, edycja, wlaczanie i usuwanie. */
export default function AdminFactsPage() {
  const { t } = useTranslation();
  const [items, setItems] = useState<Fact[]>([]);
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
      setItems((await api<{ facts: Fact[] }>('GET', '/admin/facts')).facts);
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
      const body = { text: draft.text, active: draft.active };
      if (draft.id === null) await api('POST', '/admin/facts', body);
      else await api('PUT', `/admin/facts/${draft.id}`, body);
      setMessage({ ok: true, text: t(draft.id === null ? 'admin.facts.created' : 'admin.saved') });
      setDraft(EMPTY);
      await load();
    } catch (err) {
      fail(err);
    } finally {
      setBusy(false);
    }
  };

  const toggle = async (f: Fact) => {
    setBusy(true);
    setMessage(null);
    try {
      await api('PUT', `/admin/facts/${f.id}`, { active: !f.active });
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
      await api('DELETE', `/admin/facts/${id}`);
      setConfirmId(null);
      if (draft.id === id) setDraft(EMPTY);
      setMessage({ ok: true, text: t('admin.facts.deleted') });
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
        <h2>{draft.id === null ? t('admin.facts.new') : t('admin.facts.edit')}</h2>
        <div className="field">
          <label htmlFor="fact-text">{t('admin.facts.text')}</label>
          <textarea id="fact-text" rows={3} value={draft.text} maxLength={TEXT_MAX} onChange={(e) => setDraft({ ...draft, text: e.target.value })} aria-invalid={fields.text ? true : undefined} />
          <p className={'field-hint' + (draft.text.length >= TEXT_MAX ? ' form-error' : '')}>{t('admin.facts.counter', { n: draft.text.length, max: TEXT_MAX })}</p>
          {fields.text && <p className="field-error">{fields.text}</p>}
        </div>
        <p className="check-row">
          <label><input type="checkbox" checked={draft.active} onChange={(e) => setDraft({ ...draft, active: e.target.checked })} /> {t('admin.facts.active')}</label>
        </p>
        <div className="actions">
          <button className="button" type="submit" disabled={busy || draft.text.trim() === ''}>{draft.id === null ? t('admin.facts.add') : t('admin.save')}</button>
          {draft.id !== null && (
            <button className="button secondary" type="button" onClick={() => { setDraft(EMPTY); setFields({}); }}>{t('admin.cancel')}</button>
          )}
        </div>
      </form>

      <section className="panel">
        <h2>{t('admin.facts.list')}</h2>
        {items.length === 0 && <p className="muted">{t('admin.facts.empty')}</p>}
        <ul className="admin-list">
          {items.map((f) => (
            <li key={f.id}>
              <div className="admin-list-main">
                <strong>{f.text}</strong>
                <small>{f.active ? t('admin.facts.on') : t('admin.facts.off')}{f.too_long && <span className="form-error"> · {t('admin.facts.tooLong', { max: TEXT_MAX })}</span>}</small>
              </div>
              <div className="actions">
                <button type="button" className="button small secondary" disabled={busy} onClick={() => void toggle(f)}>{f.active ? t('admin.facts.disable') : t('admin.facts.enable')}</button>
                <button type="button" className="button small secondary" onClick={() => { setDraft({ id: f.id, text: f.text, active: f.active }); setFields({}); setMessage(null); window.scrollTo({ top: 0, behavior: 'smooth' }); }}>{t('admin.edit')}</button>
                {confirmId === f.id ? (
                  <>
                    <button type="button" className="button small" disabled={busy} onClick={() => void remove(f.id)}>{t('admin.confirmDelete')}</button>
                    <button type="button" className="button small secondary" onClick={() => setConfirmId(null)}>{t('admin.cancel')}</button>
                  </>
                ) : (
                  <button type="button" className="button small secondary" onClick={() => setConfirmId(f.id)}>{t('admin.delete')}</button>
                )}
              </div>
            </li>
          ))}
        </ul>
      </section>
    </>
  );
}
