import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../../api/client';
import type { AdminSquadDetail, AdminSquadReport, AdminSquadRow, Paged } from '../../api/types';
import Pager from '../../components/Pager';
import { useAdminData } from '../../hooks/useAdminData';
import { formatDate, formatDateTime } from '../../format';

interface WordsResponse {
  words: { id: number; word: string; created_at: string }[];
}

type SortKey = 'id' | 'name' | 'members' | 'ember' | 'created' | 'reports';

function useErr() {
  const { t } = useTranslation();
  return (e: unknown) => (e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
}

/** Lista oddzialow z wyszukiwaniem, sortowaniem i podgladem szczegolow (czlonkowie, historia nazw, zgloszenia). */
export function AdminSquadListPage() {
  const { t } = useTranslation();
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');
  const [state, setState] = useState('active');
  const [sort, setSort] = useState<{ key: SortKey; dir: 'asc' | 'desc' }>({ key: 'id', dir: 'desc' });
  const [page, setPage] = useState(1);
  const [openId, setOpenId] = useState<number | null>(null);

  useEffect(() => {
    const id = window.setTimeout(() => {
      setQuery(search.trim());
      setPage(1);
    }, 300);
    return () => window.clearTimeout(id);
  }, [search]);

  const path = `/admin/squads?page=${page}&sort=${sort.key}&dir=${sort.dir}&state=${state}${query ? `&q=${encodeURIComponent(query)}` : ''}`;
  const { data, error, reload } = useAdminData<Paged<AdminSquadRow>>(path);

  const th = (key: SortKey, label: string) => (
    <th aria-sort={sort.key === key ? (sort.dir === 'asc' ? 'ascending' : 'descending') : 'none'}>
      <button type="button" className={`sort-btn${sort.key === key ? ' active' : ''}`} onClick={() => setSort((s) => ({ key, dir: s.key === key && s.dir === 'desc' ? 'asc' : 'desc' }))}>
        {label}<span aria-hidden="true">{sort.key === key ? (sort.dir === 'desc' ? ' ▼' : ' ▲') : ' ↕'}</span>
      </button>
    </th>
  );

  return (
    <>
      <section className="panel">
        <h2>{t('admin.squads.title')}</h2>
        <div className="ranking-filters">
          <input type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t('admin.squads.search')} aria-label={t('admin.squads.search')} maxLength={60} />
          <select value={state} onChange={(e) => { setState(e.target.value); setPage(1); }} aria-label={t('admin.squads.state')}>
            <option value="active">{t('admin.squads.stateActive')}</option>
            <option value="archived">{t('admin.squads.stateArchived')}</option>
            <option value="all">{t('admin.squads.stateAll')}</option>
          </select>
        </div>
        {error && <p className="form-error" role="alert">{error}</p>}
        {data && (
          <div className="table-scroll">
            <table className="ranking-table">
              <thead>
                <tr>
                  {th('id', 'ID')}
                  {th('name', t('admin.squads.cols.name'))}
                  {th('members', t('admin.squads.cols.members'))}
                  {th('ember', t('admin.squads.cols.ember'))}
                  {th('reports', t('admin.squads.cols.reports'))}
                  {th('created', t('admin.squads.cols.created'))}
                  <th />
                </tr>
              </thead>
              <tbody>
                {data.rows.map((r) => (
                  <tr key={r.id}>
                    <td>{r.id}</td>
                    <td>
                      <Link to={`/oddzial/${r.id}`}>{r.display_name}</Link>
                      {r.archived && <small className="muted"> ({t('admin.squads.archived')})</small>}
                      {r.name_locked && <small className="muted"> 🔒</small>}
                    </td>
                    <td className="num" data-label={t('admin.squads.cols.members')}>{r.members}</td>
                    <td className="num" data-label={t('admin.squads.cols.ember')}>{r.ember}</td>
                    <td className="num" data-label={t('admin.squads.cols.reports')}>{r.reports}</td>
                    <td data-label={t('admin.squads.cols.created')}>{formatDate(r.created_at)}</td>
                    <td><button type="button" className="button small secondary" onClick={() => setOpenId(openId === r.id ? null : r.id)}>{openId === r.id ? t('admin.squads.hide') : t('admin.squads.details')}</button></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        {data && data.rows.length === 0 && <p className="muted">{t('admin.noRows')}</p>}
        {data && <Pager page={data.page} pages={data.pages} total={data.total} onPage={setPage} />}
      </section>
      {openId !== null && <SquadDetail id={openId} onChanged={() => void reload()} />}
    </>
  );
}

function SquadDetail({ id, onChanged }: { id: number; onChanged: () => void }) {
  const { t } = useTranslation();
  const errText = useErr();
  const { data, error, setData } = useAdminData<AdminSquadDetail>(`/admin/squads/${id}`);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [confirmForce, setConfirmForce] = useState(false);

  const act = async (path: string, body?: unknown) => {
    setBusy(true);
    setMessage(null);
    try {
      setData(await api<AdminSquadDetail>('POST', path, body));
      setConfirmForce(false);
      onChanged();
    } catch (e) {
      setMessage(errText(e));
    } finally {
      setBusy(false);
    }
  };

  if (error) return <p className="panel form-error" role="alert">{error}</p>;
  if (!data) return <p className="panel">{t('loading')}</p>;

  return (
    <section className="panel">
      <h2>{data.display_name} (ID {data.id})</h2>
      <p className="muted">
        {t('admin.squads.detail.summary', { members: data.members.length, ember: data.ember, streak: data.flame_streak, date: formatDate(data.created_at) })}
        {data.name_forced && ` · ${t('admin.squads.detail.forced')}`}
        {data.archived && ` · ${t('admin.squads.archived')}`}
      </p>
      {message && <p className="form-error" role="alert">{message}</p>}
      {!data.archived && (
        <div className="actions">
          {confirmForce ? (
            <>
              <button type="button" className="button small" disabled={busy} onClick={() => void act(`/admin/squads/${id}/force-name`)}>{t('admin.squads.detail.forceConfirm')}</button>
              <button type="button" className="button small secondary" onClick={() => setConfirmForce(false)}>{t('admin.cancel')}</button>
            </>
          ) : (
            <button type="button" className="button small" onClick={() => setConfirmForce(true)}>{t('admin.squads.detail.force')}</button>
          )}
          <button type="button" className="button small secondary" disabled={busy} onClick={() => void act(`/admin/squads/${id}/lock-name`, { locked: !data.name_locked })}>
            {data.name_locked ? t('admin.squads.detail.unlock') : t('admin.squads.detail.lock')}
          </button>
        </div>
      )}
      <h3>{t('admin.squads.detail.members')}</h3>
      <ul className="admin-list">
        {data.members.map((m) => (
          <li key={m.id}>
            <Link to={`/admin/gracze/${m.id}`}>{m.name}</Link>
            <span className="muted">{t(`squad.roles.${m.role}`)} · {formatDate(m.joined_at)}</span>
          </li>
        ))}
      </ul>
      <h3>{t('admin.squads.detail.history')}</h3>
      {data.name_history.length === 0 ? <p className="muted">{t('admin.noRows')}</p> : (
        <ul className="admin-list">
          {data.name_history.map((h) => (
            <li key={h.id}>
              <span>{h.name}</span>
              <span className="muted">{h.by ?? t('admin.squads.detail.system')} · {formatDateTime(h.created_at)}</span>
            </li>
          ))}
        </ul>
      )}
      <h3>{t('admin.squads.detail.reports')}</h3>
      {data.reports.length === 0 ? <p className="muted">{t('admin.noRows')}</p> : (
        <ul className="admin-list">
          {data.reports.map((r) => (
            <li key={r.id}>
              <span>„{r.reported_name}” – {t(`squad.reportReasons.${r.reason}`, { defaultValue: r.reason })} ({r.reporter})</span>
              <span className="muted">{t(`admin.squads.status.${r.status}`)} · {formatDateTime(r.created_at)}</span>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}

/** Kolejka zgloszen nazw oddzialow. */
export function AdminSquadReportsPage() {
  const { t } = useTranslation();
  const errText = useErr();
  const [status, setStatus] = useState('new');
  const [page, setPage] = useState(1);
  const { data, error, reload } = useAdminData<Paged<AdminSquadReport>>(`/admin/squad-reports?page=${page}&status=${status}`);
  const [message, setMessage] = useState<string | null>(null);

  const set = async (id: number, next: string) => {
    setMessage(null);
    try {
      await api('PATCH', `/admin/squad-reports/${id}`, { status: next });
      await reload();
    } catch (e) {
      setMessage(errText(e));
    }
  };

  return (
    <section className="panel">
      <h2>{t('admin.squads.reportsTitle')}</h2>
      <div className="ranking-filters">
        <select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} aria-label={t('admin.squads.state')}>
          {['new', 'resolved', 'dismissed', 'all'].map((s) => (
            <option key={s} value={s}>{t(`admin.squads.status.${s}`)}</option>
          ))}
        </select>
      </div>
      {(error || message) && <p className="form-error" role="alert">{error ?? message}</p>}
      {data && data.rows.length === 0 && <p className="muted">{t('admin.noRows')}</p>}
      {data && data.rows.length > 0 && (
        <div className="table-scroll">
          <table className="ranking-table">
            <thead>
              <tr>
                <th>{t('admin.squads.reportCols.name')}</th>
                <th>{t('admin.squads.reportCols.reason')}</th>
                <th>{t('admin.squads.reportCols.reporter')}</th>
                <th>{t('admin.squads.reportCols.date')}</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {data.rows.map((r) => (
                <tr key={r.id}>
                  <td>
                    „{r.reported_name}”
                    {r.reported_name !== r.current_name && <small className="muted"> → {r.display_name}</small>}
                    {r.archived && <small className="muted"> ({t('admin.squads.archived')})</small>}
                    <br /><small className="muted">ID {r.squad_id}</small>
                  </td>
                  <td data-label={t('admin.squads.reportCols.reason')}>{t(`squad.reportReasons.${r.reason}`, { defaultValue: r.reason })}</td>
                  <td data-label={t('admin.squads.reportCols.reporter')}>{r.reporter}</td>
                  <td data-label={t('admin.squads.reportCols.date')}>{formatDateTime(r.created_at)}</td>
                  <td>
                    <span className="actions">
                      {r.status === 'new' ? (
                        <>
                          <button type="button" className="button small" onClick={() => void set(r.id, 'resolved')}>{t('admin.squads.resolve')}</button>
                          <button type="button" className="button small secondary" onClick={() => void set(r.id, 'dismissed')}>{t('admin.squads.dismiss')}</button>
                        </>
                      ) : (
                        <button type="button" className="button small secondary" onClick={() => void set(r.id, 'new')}>{t('admin.squads.reopen')}</button>
                      )}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      {data && <Pager page={data.page} pages={data.pages} total={data.total} onPage={setPage} />}
    </section>
  );
}

/** Slowa zakazane w nazwach oddzialow (przechowywane w postaci znormalizowanej). */
export function AdminBannedWordsPage() {
  const { t } = useTranslation();
  const errText = useErr();
  const { data, error, setData } = useAdminData<WordsResponse>('/admin/banned-words');
  const [word, setWord] = useState('');
  const [fieldError, setFieldError] = useState<string | undefined>();
  const [message, setMessage] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const add = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setFieldError(undefined);
    setMessage(null);
    try {
      setData(await api<WordsResponse>('POST', '/admin/banned-words', { word }));
      setWord('');
    } catch (err) {
      if (err instanceof ApiError && err.fields.word) setFieldError(err.fields.word);
      else setMessage(errText(err));
    } finally {
      setBusy(false);
    }
  };

  const remove = async (id: number) => {
    setMessage(null);
    try {
      setData(await api<WordsResponse>('DELETE', `/admin/banned-words/${id}`));
    } catch (err) {
      setMessage(errText(err));
    }
  };

  return (
    <section className="panel">
      <h2>{t('admin.squads.wordsTitle')}</h2>
      <form onSubmit={(e) => void add(e)} noValidate>
        <div className="field">
          <label htmlFor="banned-word">{t('admin.squads.wordLabel')}</label>
          <input id="banned-word" value={word} maxLength={40} autoComplete="off" onChange={(e) => setWord(e.target.value)} aria-invalid={fieldError ? true : undefined} />
          {fieldError && <p className="field-error">{fieldError}</p>}
          <p className="field-hint">{t('admin.squads.wordHint')}</p>
        </div>
        <button className="button" type="submit" disabled={busy || word.trim() === ''}>{t('admin.add')}</button>
      </form>
      {(error || message) && <p className="form-error" role="alert">{error ?? message}</p>}
      {data && data.words.length === 0 && <p className="muted">{t('admin.noRows')}</p>}
      {data && data.words.length > 0 && (
        <ul className="admin-list">
          {data.words.map((w) => (
            <li key={w.id}>
              <span>{w.word}</span>
              <button type="button" className="button small secondary" onClick={() => void remove(w.id)}>{t('admin.delete')}</button>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
