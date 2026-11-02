import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../../api/client';
import type { BugReportRow, Paged } from '../../api/types';
import Pager from '../../components/Pager';
import { formatDateTime } from '../../format';
import { useAdminData } from '../../hooks/useAdminData';

const STATUSES = ['new', 'in_progress', 'resolved'] as const;

/** Zgloszenia bledow od graczy: filtr po statusie, zmiana statusu i usuwanie. */
export default function AdminBugReportsPage() {
  const { t } = useTranslation();
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [confirmId, setConfirmId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<{ ok: boolean; text: string } | null>(null);
  const { data, error, reload } = useAdminData<Paged<BugReportRow> & { new_count: number }>(
    `/admin/bug-reports?page=${page}${status ? `&status=${status}` : ''}`,
  );

  const run = async (action: () => Promise<unknown>, okText: string) => {
    setBusy(true);
    setMessage(null);
    try {
      await action();
      setConfirmId(null);
      setMessage({ ok: true, text: okText });
      await reload();
    } catch (e) {
      setMessage({ ok: false, text: e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network') });
    } finally {
      setBusy(false);
    }
  };

  return (
    <section className="panel">
      <h2>{t('admin.bugs.title')}</h2>
      {data && <p className="muted">{t('admin.bugs.newCount', { n: data.new_count })}</p>}
      <div className="ranking-filters">
        <select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} aria-label={t('admin.bugs.statusLabel')}>
          <option value="">{t('admin.bugs.anyStatus')}</option>
          {STATUSES.map((s) => <option key={s} value={s}>{t(`admin.bugs.status.${s}`)}</option>)}
        </select>
      </div>
      {error && <p className="form-error" role="alert">{error}</p>}
      {message && <p className={message.ok ? 'form-success' : 'form-error'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}
      {data && data.rows.length === 0 && <p className="muted">{t('admin.bugs.empty')}</p>}
      {data && (
        <ul className="admin-list">
          {data.rows.map((r) => (
            <li key={r.id} className={`bug-item bug-${r.status}`}>
              <div className="admin-list-main">
                <p className="bug-message">{r.message}</p>
                <small>
                  <Link to={`/admin/gracze/${r.user.id}`}>{r.user.name}</Link> · {formatDateTime(r.created_at)}
                  {r.page && <> · {t('admin.bugs.page')}: {r.page}</>}
                </small>
                {r.user_agent && <small>{r.user_agent}</small>}
              </div>
              <div className="actions">
                <select
                  value={r.status}
                  disabled={busy}
                  aria-label={t('admin.bugs.statusLabel')}
                  onChange={(e) => void run(() => api('PATCH', `/admin/bug-reports/${r.id}`, { status: e.target.value }), t('admin.bugs.saved'))}
                >
                  {STATUSES.map((s) => <option key={s} value={s}>{t(`admin.bugs.status.${s}`)}</option>)}
                </select>
                {confirmId === r.id ? (
                  <>
                    <button type="button" className="button small" disabled={busy} onClick={() => void run(() => api('DELETE', `/admin/bug-reports/${r.id}`), t('admin.bugs.deleted'))}>{t('admin.confirmDelete')}</button>
                    <button type="button" className="button small secondary" onClick={() => setConfirmId(null)}>{t('admin.cancel')}</button>
                  </>
                ) : (
                  <button type="button" className="button small secondary" onClick={() => setConfirmId(r.id)}>{t('admin.delete')}</button>
                )}
              </div>
            </li>
          ))}
        </ul>
      )}
      {data && <Pager page={data.page} pages={data.pages} total={data.total} onPage={setPage} />}
    </section>
  );
}
