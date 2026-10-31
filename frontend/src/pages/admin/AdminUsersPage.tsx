import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import type { AdminUserRow, Paged } from '../../api/types';
import Coins from '../../components/Coins';
import Pager from '../../components/Pager';
import Points from '../../components/Points';
import { StatusBadge } from '../../components/admin/AdminTables';
import { useAdminData } from '../../hooks/useAdminData';
import { formatDate, formatDateTime } from '../../format';

/** Lista graczy z wyszukiwaniem (nazwa, e-mail, ID), filtrami statusu i roli oraz sortowaniem. */
export default function AdminUsersPage() {
  const { t } = useTranslation();
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');
  const [status, setStatus] = useState('');
  const [role, setRole] = useState('');
  const [sort, setSort] = useState('new');
  const [page, setPage] = useState(1);

  useEffect(() => {
    const id = window.setTimeout(() => {
      setQuery(search.trim());
      setPage(1);
    }, 300);
    return () => window.clearTimeout(id);
  }, [search]);

  const params = new URLSearchParams({ page: String(page), sort });
  if (query) params.set('q', query);
  if (status) params.set('status', status);
  if (role) params.set('role', role);
  const { data, error } = useAdminData<Paged<AdminUserRow>>(`/admin/users?${params.toString()}`);

  const reset = (set: (v: string) => void) => (e: React.ChangeEvent<HTMLSelectElement>) => {
    set(e.target.value);
    setPage(1);
  };

  return (
    <section className="panel">
      <h2>{t('admin.users.title')}</h2>
      <div className="ranking-filters">
        <input type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t('admin.users.search')} aria-label={t('admin.users.search')} maxLength={60} />
        <select value={status} onChange={reset(setStatus)} aria-label={t('admin.cols.status')}>
          <option value="">{t('admin.users.anyStatus')}</option>
          {(['active', 'suspended', 'banned', 'pending'] as const).map((s) => <option key={s} value={s}>{t(`admin.status.${s}`)}</option>)}
        </select>
        <select value={role} onChange={reset(setRole)} aria-label={t('admin.cols.role')}>
          <option value="">{t('admin.users.anyRole')}</option>
          {(['player', 'moderator', 'admin'] as const).map((r) => <option key={r} value={r}>{t(`admin.role.${r}`)}</option>)}
        </select>
        <select value={sort} onChange={reset(setSort)} aria-label={t('admin.users.sort')}>
          {(['new', 'old', 'points', 'login'] as const).map((s) => <option key={s} value={s}>{t(`admin.users.sorts.${s}`)}</option>)}
        </select>
      </div>

      {error && <p className="form-error" role="alert">{error}</p>}
      <div className="table-scroll">
        <table className="ranking-table">
          <thead>
            <tr>
              <th className="num">ID</th>
              <th>{t('admin.cols.player')}</th>
              <th>E-mail</th>
              <th>{t('admin.cols.role')}</th>
              <th>{t('admin.cols.status')}</th>
              <th>{t('ranking.worldChapter')}</th>
              <th className="num">{t('wallet.points')}</th>
              <th className="num">{t('wallet.coins')}</th>
              <th>{t('admin.cols.registered')}</th>
              <th>{t('admin.cols.lastLogin')}</th>
            </tr>
          </thead>
          <tbody>
            {data && data.rows.length === 0 && <tr><td colSpan={10} className="ranking-empty">{t('admin.users.empty')}</td></tr>}
            {data?.rows.map((u) => (
              <tr key={u.id}>
                <td className="num">{u.id}</td>
                <td><Link to={`/admin/gracze/${u.id}`}>{u.name}</Link></td>
                <td className="cell-wrap">{u.email}{!u.email_verified && <small className="muted" title={t('admin.users.unverified')}> ●</small>}</td>
                <td>{t(`admin.role.${u.role}`)}</td>
                <td><StatusBadge status={u.status} /></td>
                <td>{u.world !== null ? `${u.world} / ${u.chapter}` : '–'}</td>
                <td className="num"><Points value={u.points} /></td>
                <td className="num"><Coins cents={u.coins_cents} /></td>
                <td>{formatDate(u.registered_at)}</td>
                <td>{u.last_login_at ? formatDateTime(u.last_login_at) : '–'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {data && <Pager page={data.page} pages={data.pages} total={data.total} onPage={setPage} />}
    </section>
  );
}
