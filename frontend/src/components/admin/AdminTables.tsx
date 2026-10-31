import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import type { AccountStatus, AuditRow, LedgerRow, LoginRow } from '../../api/types';
import { formatCoins, formatCoinsFull, formatDateTime } from '../../format';

/** Kolorowa plakietka statusu konta. */
export function StatusBadge({ status }: { status: AccountStatus }) {
  const { t } = useTranslation();
  return <span className={`status-badge status-${status}`}>{t(`admin.status.${status}`)}</span>;
}

/** Tabela logowan; w widoku globalnym pokazuje tez gracza. */
export function LoginTable({ rows, showUser }: { rows: LoginRow[]; showUser: boolean }) {
  const { t } = useTranslation();
  return (
    <div className="table-scroll">
      <table className="ranking-table">
        <thead>
          <tr>
            {showUser && <th>{t('admin.cols.player')}</th>}
            <th>{t('admin.cols.loggedIn')}</th>
            <th>{t('admin.cols.loggedOut')}</th>
            <th>IP</th>
            <th>{t('admin.cols.browser')}</th>
          </tr>
        </thead>
        <tbody>
          {rows.length === 0 && <tr><td colSpan={showUser ? 5 : 4} className="ranking-empty">{t('admin.noRows')}</td></tr>}
          {rows.map((r) => (
            <tr key={r.id}>
              {showUser && <td><Link to={`/admin/gracze/${r.user_id}`}>{r.name}</Link></td>}
              <td>{formatDateTime(r.logged_in_at)}</td>
              <td>{r.logged_out_at ? formatDateTime(r.logged_out_at) : '–'}</td>
              <td>{r.ip ?? '–'}</td>
              <td className="cell-wrap" title={r.user_agent ?? ''}>{r.user_agent ?? '–'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function amount(row: LedgerRow): string {
  const sign = row.amount > 0 ? '+' : row.amount < 0 ? '−' : '';
  // formatCoins obcina wartosci ujemne do zera, wiec znak dodajemy sami i formatujemy wartosc bezwzgledna.
  return row.currency === 'coins' ? `${sign}${formatCoins(Math.abs(row.amount))}` : `${sign}${Math.abs(row.amount)}`;
}

/** Pelna wartosc do dymku po najechaniu (dukaty w skrocie tracilyby dokladnosc). */
function fullAmount(row: LedgerRow): string {
  const sign = row.amount < 0 ? '−' : row.amount > 0 ? '+' : '';
  return row.currency === 'coins' ? `${sign}${formatCoinsFull(Math.abs(row.amount))}` : `${sign}${Math.abs(row.amount)}`;
}

/** Ksiega operacji portfela gracza. */
export function LedgerTable({ rows }: { rows: LedgerRow[] }) {
  const { t } = useTranslation();
  return (
    <div className="table-scroll">
      <table className="ranking-table">
        <thead>
          <tr>
            <th>{t('admin.cols.time')}</th>
            <th>{t('admin.cols.currency')}</th>
            <th className="num">{t('admin.cols.amount')}</th>
            <th className="num">{t('admin.cols.balance')}</th>
            <th>{t('admin.cols.reason')}</th>
          </tr>
        </thead>
        <tbody>
          {rows.length === 0 && <tr><td colSpan={5} className="ranking-empty">{t('admin.noRows')}</td></tr>}
          {rows.map((r) => (
            <tr key={r.id}>
              <td>{formatDateTime(r.created_at)}</td>
              <td>{t(`admin.currency.${r.currency}`)}</td>
              <td className={'num ' + (r.amount < 0 ? 'neg' : 'pos')} title={fullAmount(r)}>{amount(r)}</td>
              <td className="num" title={r.currency === 'coins' ? formatCoinsFull(r.balance_after) : undefined}>{r.currency === 'coins' ? formatCoins(r.balance_after) : r.balance_after}</td>
              <td>{t(`admin.reasons.${r.reason}`, { defaultValue: r.reason })}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function detailsText(d: Record<string, unknown> | null): string {
  if (!d) return '';
  return Object.entries(d)
    .map(([k, v]) => {
      if (v && typeof v === 'object' && 'from' in v && 'to' in v) {
        const c = v as { from: unknown; to: unknown };
        return `${k}: ${String(c.from ?? '–')} → ${String(c.to ?? '–')}`;
      }
      return `${k}: ${String(v)}`;
    })
    .join(' · ');
}

/** Dziennik dzialan administratorow. */
export function AuditTable({ rows, showTarget }: { rows: AuditRow[]; showTarget: boolean }) {
  const { t } = useTranslation();
  return (
    <div className="table-scroll">
      <table className="ranking-table">
        <thead>
          <tr>
            <th>{t('admin.cols.time')}</th>
            <th>{t('admin.cols.admin')}</th>
            <th>{t('admin.cols.action')}</th>
            {showTarget && <th>{t('admin.cols.player')}</th>}
            <th>{t('admin.cols.details')}</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          {rows.length === 0 && <tr><td colSpan={showTarget ? 6 : 5} className="ranking-empty">{t('admin.noRows')}</td></tr>}
          {rows.map((r) => (
            <tr key={r.id}>
              <td>{formatDateTime(r.created_at)}</td>
              <td>{r.admin ? <Link to={`/admin/gracze/${r.admin.id}`}>{r.admin.name}</Link> : '–'}</td>
              <td>{t(`admin.action.${r.action.replace('.', '_')}`, { defaultValue: r.action })}</td>
              {showTarget && <td>{r.target ? <Link to={`/admin/gracze/${r.target.id}`}>{r.target.name}</Link> : '–'}</td>}
              <td className="cell-wrap">{detailsText(r.details)}</td>
              <td>{r.ip ?? '–'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
