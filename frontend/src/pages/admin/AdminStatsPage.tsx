import { useTranslation } from 'react-i18next';
import type { AdminStats, DayCount } from '../../api/types';
import ActivityChart from '../../components/admin/ActivityChart';
import Coins from '../../components/Coins';
import Points from '../../components/Points';
import { useAdminData } from '../../hooks/useAdminData';
import { formatDateTime } from '../../format';

function Tile({ label, value, hint }: { label: string; value: React.ReactNode; hint?: string }) {
  return (
    <div className="admin-tile" title={hint}>
      <dt>{label}</dt>
      <dd>{value}</dd>
    </div>
  );
}

/** Prosty wykres slupkowy (CSS) z ostatnich dni; najechanie pokazuje date i liczbe. */
function Bars({ title, data }: { title: string; data: DayCount[] }) {
  const max = Math.max(1, ...data.map((d) => d.count));
  const total = data.reduce((s, d) => s + d.count, 0);
  return (
    <figure className="bars">
      <figcaption>{title} <small>({total})</small></figcaption>
      <div className="bars-row" role="img" aria-label={`${title}: ${total}`}>
        {data.map((d) => (
          <span key={d.day} className="chart-bar" title={`${d.day}: ${d.count}`} style={{ height: `${Math.max(2, (d.count / max) * 100)}%` }} />
        ))}
      </div>
      <div className="bars-axis">
        <span>{data[0]?.day.slice(5)}</span>
        <span>{data[data.length - 1]?.day.slice(5)}</span>
      </div>
    </figure>
  );
}

/** Przeglad: statystyki konta, aktywnosci i ekonomii calej gry. */
export default function AdminStatsPage() {
  const { t } = useTranslation();
  const { data: s, error, reload } = useAdminData<AdminStats>('/admin/stats');

  if (error) return <p className="form-error" role="alert">{error}</p>;
  if (!s) return <p className="muted">{t('loading')}</p>;

  const maxWorld = Math.max(1, ...s.worlds.map((w) => w.players));
  return (
    <>
      <p className="muted">
        {t('admin.stats.generated', { time: formatDateTime(s.generated_at) })}{' '}
        <button type="button" className="button small secondary" onClick={() => void reload()}>{t('admin.stats.refresh')}</button>
      </p>

      <section className="panel">
        <h2>{t('admin.stats.accounts')}</h2>
        <dl className="admin-tiles">
          <Tile label={t('admin.stats.total')} value={s.accounts.total} />
          <Tile label={t('admin.stats.active')} value={s.accounts.active} />
          <Tile label={t('admin.stats.suspended')} value={s.accounts.suspended} />
          <Tile label={t('admin.stats.banned')} value={s.accounts.banned} />
          <Tile label={t('admin.stats.unverified')} value={s.accounts.unverified} hint={t('admin.stats.unverifiedHint')} />
          <Tile label={t('admin.stats.admins')} value={s.accounts.admins} />
          <Tile label={t('admin.stats.new24')} value={s.accounts.new_24h} />
          <Tile label={t('admin.stats.new7')} value={s.accounts.new_7d} />
          <Tile label={t('admin.stats.new30')} value={s.accounts.new_30d} />
          <Tile label={t('admin.stats.tutorialDone')} value={s.accounts.tutorial_done} />
          <Tile label={t('admin.stats.referred')} value={s.accounts.referred} />
        </dl>
        <Bars title={t('admin.stats.registrations30')} data={s.registrations} />
      </section>

      <section className="panel">
        <h2>{t('admin.stats.activity')}</h2>
        <dl className="admin-tiles">
          <Tile label={t('admin.stats.online')} value={s.activity.online_now} hint={t('admin.stats.onlineHint')} />
          <Tile label="DAU" value={s.activity.dau} hint={t('admin.stats.dauHint')} />
          <Tile label="WAU" value={s.activity.wau} hint={t('admin.stats.wauHint')} />
          <Tile label="MAU" value={s.activity.mau} hint={t('admin.stats.mauHint')} />
          <Tile label={t('admin.stats.logins24')} value={s.activity.logins_24h} />
          <Tile label={t('admin.stats.logins7')} value={s.activity.logins_7d} />
          <Tile label={t('admin.stats.avgActiveDays')} value={s.activity.avg_active_days} />
          <Tile label={t('admin.stats.claimsToday')} value={s.activity.daily_claims_today} />
          <Tile label={t('admin.stats.claimsTotal')} value={s.activity.daily_claims_total} />
        </dl>
        <h3>{t('admin.stats.chart.hourlyTitle')}</h3>
        <ActivityChart view="hours" spans={[24, 48, 72, 168]} defaultSpan={24} />
        <h3>{t('admin.stats.chart.dailyTitle')}</h3>
        <ActivityChart view="days" spans={[7, 30, 90, 180, 365]} defaultSpan={30} />
      </section>

      <section className="panel">
        <h2>{t('admin.stats.economy')}</h2>
        <dl className="admin-tiles">
          <Tile label={t('admin.stats.coins')} value={<Coins cents={s.economy.coins_cents} />} hint={t('admin.stats.coinsHint')} />
          <Tile label={t('admin.stats.points')} value={<Points value={s.economy.points} />} />
          <Tile label={t('admin.stats.tickets')} value={s.economy.tickets} />
          <Tile label={t('admin.stats.jobs24')} value={s.economy.jobs_done_24h} />
          <Tile label={t('admin.stats.jobsTotal')} value={s.economy.jobs_done_total} />
          <Tile label={t('admin.stats.trainings24')} value={s.economy.trainings_done_24h} />
          <Tile label={t('admin.stats.trainingsTotal')} value={s.economy.trainings_done_total} />
          <Tile label={t('admin.stats.badges')} value={s.economy.badges_earned} />
          <Tile label={t('admin.stats.giftsTotal')} value={s.economy.gifts_total} hint={t('admin.stats.giftsHint')} />
          <Tile label={t('admin.stats.gifts24')} value={s.economy.gifts_24h} />
          <Tile label={t('admin.stats.giftsCoins')} value={<Coins cents={s.economy.gifts_cents_total} />} hint={t('admin.stats.giftsHint')} />
        </dl>
      </section>

      <section className="panel">
        <h2>{t('admin.stats.worlds')}</h2>
        {s.worlds.length === 0 && <p className="muted">{t('admin.noRows')}</p>}
        <ul className="world-bars">
          {s.worlds.map((w) => (
            <li key={w.world}>
              <span>{t('ranking.worldN', { n: w.world })}</span>
              <span className="world-bar"><i style={{ width: `${(w.players / maxWorld) * 100}%` }} /></span>
              <b>{w.players}</b>
            </li>
          ))}
        </ul>
      </section>
    </>
  );
}
