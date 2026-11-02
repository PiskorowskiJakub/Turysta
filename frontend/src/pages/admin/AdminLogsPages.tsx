import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import type { AuditRow, LoginRow, Paged } from '../../api/types';
import Pager from '../../components/Pager';
import { AuditTable, LoginTable } from '../../components/admin/AdminTables';
import { useAdminData } from '../../hooks/useAdminData';

/** Logowania wszystkich graczy z wyszukiwaniem po nazwie, e-mailu lub IP. */
export function AdminLoginsPage() {
  const { t } = useTranslation();
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');
  const [page, setPage] = useState(1);

  useEffect(() => {
    const id = window.setTimeout(() => {
      setQuery(search.trim());
      setPage(1);
    }, 300);
    return () => window.clearTimeout(id);
  }, [search]);

  const { data, error } = useAdminData<Paged<LoginRow>>(`/admin/logins?page=${page}${query ? `&q=${encodeURIComponent(query)}` : ''}`);
  return (
    <section className="panel">
      <h2>{t('admin.logs.logins')}</h2>
      <div className="ranking-filters">
        <input type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t('admin.logs.search')} aria-label={t('admin.logs.search')} maxLength={60} />
      </div>
      {error && <p className="form-error" role="alert">{error}</p>}
      {data && <LoginTable rows={data.rows} showUser />}
      {data && <Pager page={data.page} pages={data.pages} total={data.total} onPage={setPage} />}
    </section>
  );
}

const ACTIONS = [
  'user.update', 'user.ban', 'user.unban', 'user.suspend', 'user.unsuspend', 'user.wallet', 'user.logout',
  'announcement.create', 'announcement.update', 'announcement.delete', 'fact.create', 'fact.update', 'fact.delete',
  'bugreport.status', 'bugreport.delete',
] as const;

/** Dziennik dzialan administratorow z filtrem po rodzaju dzialania. */
export function AdminAuditPage() {
  const { t } = useTranslation();
  const [action, setAction] = useState('');
  const [page, setPage] = useState(1);
  const { data, error } = useAdminData<Paged<AuditRow>>(`/admin/audit?page=${page}${action ? `&action=${encodeURIComponent(action)}` : ''}`);
  return (
    <section className="panel">
      <h2>{t('admin.logs.audit')}</h2>
      <div className="ranking-filters">
        <select value={action} onChange={(e) => { setAction(e.target.value); setPage(1); }} aria-label={t('admin.cols.action')}>
          <option value="">{t('admin.logs.anyAction')}</option>
          {ACTIONS.map((a) => <option key={a} value={a}>{t(`admin.action.${a.replace('.', '_')}`)}</option>)}
        </select>
      </div>
      {error && <p className="form-error" role="alert">{error}</p>}
      {data && <AuditTable rows={data.rows} showTarget />}
      {data && <Pager page={data.page} pages={data.pages} total={data.total} onPage={setPage} />}
    </section>
  );
}
