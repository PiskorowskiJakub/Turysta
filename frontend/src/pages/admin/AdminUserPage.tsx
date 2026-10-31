import { useEffect, useState, type FormEvent } from 'react';
import { Link, Navigate, Route, Routes, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../../api/client';
import type { AdminUser, Avatar, AuditRow, Gender, LedgerRow, LoginRow, Paged, Role } from '../../api/types';
import { useAuth } from '../../auth/AuthContext';
import Coins from '../../components/Coins';
import Pager from '../../components/Pager';
import Points from '../../components/Points';
import Tabs, { type TabItem } from '../../components/Tabs';
import { AuditTable, LedgerTable, LoginTable, StatusBadge } from '../../components/admin/AdminTables';
import { useAdminData } from '../../hooks/useAdminData';
import { formatDate, formatDateTime } from '../../format';

interface Msg {
  ok: boolean;
  text: string;
}

function useAction(setUser: (u: AdminUser) => void) {
  const { t } = useTranslation();
  const [busy, setBusy] = useState(false);
  const [msg, setMsg] = useState<Msg | null>(null);
  const [fields, setFields] = useState<Record<string, string>>({});

  const run = async (method: 'PATCH' | 'POST', path: string, body: unknown, ok: string): Promise<boolean> => {
    setBusy(true);
    setMsg(null);
    setFields({});
    try {
      const r = await api<{ user: AdminUser }>(method, path, body);
      setUser(r.user);
      setMsg({ ok: true, text: ok });
      return true;
    } catch (e) {
      if (e instanceof ApiError) {
        setFields(e.fields);
        setMsg({ ok: false, text: t(`errors.${e.code}`, { defaultValue: e.message }) });
      } else {
        setMsg({ ok: false, text: t('errors.network') });
      }
      return false;
    } finally {
      setBusy(false);
    }
  };
  return { busy, msg, fields, run, clear: () => setMsg(null) };
}

function Message({ msg }: { msg: Msg | null }) {
  if (!msg) return null;
  return <p className={msg.ok ? 'form-success' : 'form-error'} role={msg.ok ? 'status' : 'alert'}>{msg.text}</p>;
}

function Facts({ u }: { u: AdminUser }) {
  const { t } = useTranslation();
  const rows: [string, React.ReactNode][] = [
    ['ID', u.id],
    [t('admin.cols.role'), t(`admin.role.${u.role}`)],
    [t('admin.cols.status'), <>
      <StatusBadge status={u.status} />
      {u.suspended_until && u.status === 'suspended' && <> {t('admin.until', { time: formatDateTime(u.suspended_until) })}</>}
      {u.status_reason && <small className="muted"> ({u.status_reason})</small>}
    </>],
    [t('admin.cols.registered'), formatDateTime(u.registered_at)],
    [t('admin.cols.lastLogin'), u.last_login_at ? formatDateTime(u.last_login_at) : '–'],
    [t('admin.user.emailVerified'), u.email_verified ? t('admin.yes') : t('admin.no')],
    [t('admin.user.tutorial'), u.tutorial_done ? t('admin.yes') : t('admin.no')],
    [t('ranking.worldChapter'), u.progress ? `${u.progress.world_ordinal}. ${u.progress.world_name} / ${u.progress.chapter_ordinal}. ${u.progress.chapter_name}` : '–'],
    [t('stats.activeDays'), u.stats.active_days],
    [t('stats.longestStreak'), u.stats.longest_streak],
    [t('admin.user.lastActiveDay'), u.stats.last_active_day ? formatDate(u.stats.last_active_day) : '–'],
    [t('admin.user.logins'), u.counts.logins],
    [t('admin.user.sessions'), u.counts.sessions],
    [t('admin.user.jobs'), u.counts.jobs_done],
    [t('admin.user.trainings'), u.counts.trainings_done],
    [t('admin.user.badges'), u.counts.badges],
    [t('admin.user.referralCode'), u.referral_code ?? '–'],
    [t('admin.user.invited'), u.counts.referrals],
    [t('admin.user.referredBy'), u.referred_by ? <Link to={`/admin/gracze/${u.referred_by.id}`}>{u.referred_by.name}</Link> : '–'],
    [t('admin.user.genderChanged'), u.gender_changed_at ? formatDateTime(u.gender_changed_at) : '–'],
  ];
  return (
    <section className="panel">
      <h2>{t('admin.user.facts')}</h2>
      <dl className="kv">
        {rows.map(([k, v]) => (
          <div key={k}><dt>{k}</dt><dd>{v}</dd></div>
        ))}
      </dl>
    </section>
  );
}

function EditForm({ u, setUser, isSelf }: { u: AdminUser; setUser: (u: AdminUser) => void; isSelf: boolean }) {
  const { t } = useTranslation();
  const { busy, msg, fields, run } = useAction(setUser);
  const [avatars, setAvatars] = useState<Avatar[]>([]);
  const [name, setName] = useState(u.name);
  const [email, setEmail] = useState(u.email);
  const [role, setRole] = useState<Role>(u.role);
  const [gender, setGender] = useState<Gender | ''>(u.gender ?? '');
  const [avatarId, setAvatarId] = useState<number | ''>(u.avatar_id ?? '');
  const [verified, setVerified] = useState(u.email_verified);

  useEffect(() => {
    api<{ avatars: Avatar[] }>('GET', '/avatars').then((r) => setAvatars(r.avatars)).catch(() => setAvatars([]));
  }, []);
  useEffect(() => {
    setName(u.name);
    setEmail(u.email);
    setRole(u.role);
    setGender(u.gender ?? '');
    setAvatarId(u.avatar_id ?? '');
    setVerified(u.email_verified);
  }, [u]);

  const visible = avatars.filter((a) => !gender || a.gender === gender);
  const submit = (e: FormEvent) => {
    e.preventDefault();
    const body: Record<string, unknown> = { character_name: name, email, email_verified: verified };
    if (!isSelf) body.role = role;
    if (gender) body.gender = gender;
    if (avatarId !== '') body.avatar_id = avatarId;
    void run('PATCH', `/admin/users/${u.id}`, body, t('admin.user.saved'));
  };

  return (
    <form className="panel edit-section" onSubmit={submit} noValidate>
      <h2>{t('admin.user.edit')}</h2>
      <Message msg={msg} />
      <div className="field">
        <label htmlFor="au-name">{t('admin.user.characterName')}</label>
        <input id="au-name" value={name} maxLength={20} onChange={(e) => setName(e.target.value)} aria-invalid={fields.character_name ? true : undefined} />
        {fields.character_name && <p className="field-error">{fields.character_name}</p>}
      </div>
      <div className="field">
        <label htmlFor="au-email">E-mail</label>
        <input id="au-email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} aria-invalid={fields.email ? true : undefined} />
        {fields.email && <p className="field-error">{fields.email}</p>}
      </div>
      <p className="check-row">
        <label><input type="checkbox" checked={verified} onChange={(e) => setVerified(e.target.checked)} /> {t('admin.user.emailVerified')}</label>
      </p>
      <div className="field">
        <label htmlFor="au-role">{t('admin.cols.role')}</label>
        <select id="au-role" value={role} disabled={isSelf} onChange={(e) => setRole(e.target.value as Role)}>
          {(['player', 'moderator', 'admin'] as const).map((r) => <option key={r} value={r}>{t(`admin.role.${r}`)}</option>)}
        </select>
        {isSelf && <p className="field-hint">{t('admin.user.ownRole')}</p>}
        {fields.role && <p className="field-error">{fields.role}</p>}
      </div>
      <div className="field">
        <label htmlFor="au-gender">{t('editProfile.gender')}</label>
        <select id="au-gender" value={gender} onChange={(e) => { setGender(e.target.value as Gender | ''); setAvatarId(''); }}>
          <option value="">–</option>
          {(['f', 'm', 'x'] as const).map((g) => <option key={g} value={g}>{t(`gender.${g}`)}</option>)}
        </select>
        <p className="field-hint">{t('admin.user.genderBypass')}</p>
        {fields.gender && <p className="field-error">{fields.gender}</p>}
      </div>
      <div className="field">
        <label htmlFor="au-avatar">{t('editProfile.avatar')}</label>
        <select id="au-avatar" value={avatarId} onChange={(e) => setAvatarId(e.target.value === '' ? '' : Number(e.target.value))}>
          <option value="">–</option>
          {visible.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
        </select>
        {fields.avatar_id && <p className="field-error">{fields.avatar_id}</p>}
      </div>
      <button className="button" type="submit" disabled={busy}>{t('admin.save')}</button>
    </form>
  );
}

function StatusPanel({ u, setUser, isSelf }: { u: AdminUser; setUser: (u: AdminUser) => void; isSelf: boolean }) {
  const { t } = useTranslation();
  const { busy, msg, fields, run } = useAction(setUser);
  const [reason, setReason] = useState('');
  const [days, setDays] = useState(7);
  const [confirm, setConfirm] = useState<string | null>(null);

  const locked = isSelf || u.role === 'admin';
  const act = async (action: string, ok: string) => {
    const done = await run('POST', `/admin/users/${u.id}/status`, { action, reason, days }, ok);
    if (done) {
      setReason('');
      setConfirm(null);
    }
  };

  return (
    <section className="panel edit-section">
      <h2>{t('admin.user.statusTitle')}</h2>
      <p><StatusBadge status={u.status} />{u.status === 'suspended' && u.suspended_until && <> {t('admin.until', { time: formatDateTime(u.suspended_until) })}</>}{u.status_reason && <small className="muted"> ({u.status_reason})</small>}</p>
      <Message msg={msg} />

      {(u.status === 'banned' || u.status === 'suspended') && (
        <div className="actions">
          {u.status === 'banned' && <button type="button" className="button" disabled={busy} onClick={() => void act('unban', t('admin.user.unbanned'))}>{t('admin.user.unban')}</button>}
          {u.status === 'suspended' && <button type="button" className="button" disabled={busy} onClick={() => void act('unsuspend', t('admin.user.unsuspended'))}>{t('admin.user.unsuspend')}</button>}
        </div>
      )}

      {locked ? (
        <p className="field-hint">{isSelf ? t('admin.user.selfLocked') : t('admin.user.adminLocked')}</p>
      ) : u.status !== 'banned' && (
        <>
          <div className="field">
            <label htmlFor="st-reason">{t('admin.user.reason')}</label>
            <input id="st-reason" value={reason} maxLength={255} onChange={(e) => setReason(e.target.value)} aria-invalid={fields.reason ? true : undefined} />
            {fields.reason && <p className="field-error">{fields.reason}</p>}
          </div>
          <div className="field">
            <label htmlFor="st-days">{t('admin.user.days')}</label>
            <input id="st-days" type="number" min={1} max={365} value={days} onChange={(e) => setDays(Number(e.target.value))} aria-invalid={fields.days ? true : undefined} />
            {fields.days && <p className="field-error">{fields.days}</p>}
          </div>
          <div className="actions">
            <button type="button" className="button secondary" disabled={busy} onClick={() => void act('suspend', t('admin.user.suspended'))}>{t('admin.user.suspend')}</button>
            {confirm === 'ban' ? (
              <>
                <button type="button" className="button" disabled={busy} onClick={() => void act('ban', t('admin.user.banned'))}>{t('admin.user.confirmBan')}</button>
                <button type="button" className="button secondary" onClick={() => setConfirm(null)}>{t('admin.cancel')}</button>
              </>
            ) : (
              <button type="button" className="button secondary" disabled={busy} onClick={() => setConfirm('ban')}>{t('admin.user.ban')}</button>
            )}
          </div>
        </>
      )}

      <hr className="admin-sep" />
      <p className="muted">{t('admin.user.sessionsInfo', { n: u.counts.sessions })}</p>
      <button type="button" className="button secondary" disabled={busy} onClick={() => void run('POST', `/admin/users/${u.id}/logout`, {}, t('admin.user.loggedOut'))}>{t('admin.user.logout')}</button>
    </section>
  );
}

function WalletTab({ u, setUser }: { u: AdminUser; setUser: (u: AdminUser) => void }) {
  const { t } = useTranslation();
  const { busy, msg, fields, run } = useAction(setUser);
  const [currency, setCurrency] = useState<'coins' | 'points' | 'tickets'>('coins');
  const [value, setValue] = useState('');
  const [reason, setReason] = useState('');

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    const n = Number(value.replace(',', '.'));
    // Dukaty podajemy w dk (np. 2,5), a serwer liczy w setnych; punkty i bilety to liczby calkowite.
    const amount = Number.isFinite(n) ? Math.round(currency === 'coins' ? n * 100 : n) : 0;
    const ok = await run('POST', `/admin/users/${u.id}/wallet`, { currency, amount, reason }, t('admin.user.walletSaved'));
    if (ok) {
      setValue('');
      setReason('');
    }
  };

  return (
    <>
      <section className="panel">
        <h2>{t('admin.user.wallet')}</h2>
        <dl className="admin-tiles">
          <div className="admin-tile"><dt>{t('wallet.coins')}</dt><dd><Coins cents={u.wallet.coins_cents} /></dd></div>
          <div className="admin-tile"><dt>{t('wallet.points')}</dt><dd><Points value={u.wallet.points} /></dd></div>
          <div className="admin-tile"><dt>{t('wallet.tickets')}</dt><dd>{u.wallet.tickets}</dd></div>
        </dl>
      </section>
      <form className="panel edit-section" onSubmit={(e) => void submit(e)} noValidate>
        <h2>{t('admin.user.adjust')}</h2>
        <p className="muted">{t('admin.user.adjustInfo')}</p>
        <Message msg={msg} />
        <div className="field">
          <label htmlFor="wl-cur">{t('admin.cols.currency')}</label>
          <select id="wl-cur" value={currency} onChange={(e) => setCurrency(e.target.value as typeof currency)}>
            {(['coins', 'points', 'tickets'] as const).map((c) => <option key={c} value={c}>{t(`admin.currency.${c}`)}</option>)}
          </select>
        </div>
        <div className="field">
          <label htmlFor="wl-amount">{currency === 'coins' ? t('admin.user.amountCoins') : t('admin.user.amount')}</label>
          <input id="wl-amount" inputMode="decimal" value={value} onChange={(e) => setValue(e.target.value)} placeholder="np. 5 albo -2" aria-invalid={fields.amount ? true : undefined} />
          {fields.amount && <p className="field-error">{fields.amount}</p>}
        </div>
        <div className="field">
          <label htmlFor="wl-reason">{t('admin.user.reason')}</label>
          <input id="wl-reason" value={reason} maxLength={255} onChange={(e) => setReason(e.target.value)} aria-invalid={fields.reason ? true : undefined} />
          {fields.reason && <p className="field-error">{fields.reason}</p>}
        </div>
        <button className="button" type="submit" disabled={busy || !value || !reason}>{t('admin.user.apply')}</button>
      </form>
    </>
  );
}

function LoginsTab({ id }: { id: number }) {
  const { t } = useTranslation();
  const [page, setPage] = useState(1);
  const { data, error } = useAdminData<Paged<LoginRow>>(`/admin/users/${id}/logins?page=${page}`);
  return (
    <section className="panel">
      <h2>{t('admin.logs.logins')}</h2>
      {error && <p className="form-error" role="alert">{error}</p>}
      {data && <LoginTable rows={data.rows} showUser={false} />}
      {data && <Pager page={data.page} pages={data.pages} total={data.total} onPage={setPage} />}
    </section>
  );
}

function LedgerTab({ id }: { id: number }) {
  const { t } = useTranslation();
  const [page, setPage] = useState(1);
  const [currency, setCurrency] = useState('');
  const { data, error } = useAdminData<Paged<LedgerRow>>(`/admin/users/${id}/ledger?page=${page}${currency ? `&currency=${currency}` : ''}`);
  return (
    <section className="panel">
      <h2>{t('admin.user.ledger')}</h2>
      <div className="ranking-filters">
        <select value={currency} onChange={(e) => { setCurrency(e.target.value); setPage(1); }} aria-label={t('admin.cols.currency')}>
          <option value="">{t('admin.user.allCurrencies')}</option>
          {(['coins', 'points', 'tickets'] as const).map((c) => <option key={c} value={c}>{t(`admin.currency.${c}`)}</option>)}
        </select>
      </div>
      {error && <p className="form-error" role="alert">{error}</p>}
      {data && <LedgerTable rows={data.rows} />}
      {data && <Pager page={data.page} pages={data.pages} total={data.total} onPage={setPage} />}
    </section>
  );
}

function HistoryTab({ id }: { id: number }) {
  const { t } = useTranslation();
  const [page, setPage] = useState(1);
  const { data, error } = useAdminData<Paged<AuditRow>>(`/admin/users/${id}/audit?page=${page}`);
  return (
    <section className="panel">
      <h2>{t('admin.user.history')}</h2>
      {error && <p className="form-error" role="alert">{error}</p>}
      {data && <AuditTable rows={data.rows} showTarget={false} />}
      {data && <Pager page={data.page} pages={data.pages} total={data.total} onPage={setPage} />}
    </section>
  );
}

/** Karta gracza w panelu admina: dane i edycja, portfel, logowania, ksiega operacji i historia zmian. */
export default function AdminUserPage() {
  const { t } = useTranslation();
  const { me } = useAuth();
  const params = useParams();
  const id = Number(params.id);
  const { data, error, setData } = useAdminData<{ user: AdminUser }>(Number.isInteger(id) && id > 0 ? `/admin/users/${id}` : null);

  if (!Number.isInteger(id) || id < 1) return <Navigate to="/admin/gracze" replace />;
  if (error) return <><p className="form-error" role="alert">{error}</p><Link to="/admin/gracze">← {t('admin.user.back')}</Link></>;
  if (!data) return <p className="muted">{t('loading')}</p>;

  const u = data.user;
  const setUser = (user: AdminUser) => setData({ user });
  const isSelf = me?.user.id === u.id;
  const base = `/admin/gracze/${u.id}`;
  const tabs: TabItem[] = [
    { path: 'dane', label: t('admin.user.tabs.data') },
    { path: 'portfel', label: t('admin.user.tabs.wallet') },
    { path: 'logowania', label: t('admin.user.tabs.logins') },
    { path: 'ksiega', label: t('admin.user.tabs.ledger') },
    { path: 'historia', label: t('admin.user.tabs.history') },
  ];

  return (
    <>
      <p className="player-back"><Link to="/admin/gracze">← {t('admin.user.back')}</Link></p>
      <header className="admin-user-head">
        <h2>{u.name}</h2>
        <StatusBadge status={u.status} />
        <span className="muted">{u.email}</span>
        <Link className="button small secondary" to={`/gracz/${u.id}`}>{t('admin.user.publicProfile')}</Link>
      </header>
      <div className="tabs-sub flat">
        <Tabs base={base} items={tabs} />
      </div>
      <Routes>
        <Route path="dane" element={<><Facts u={u} /><EditForm u={u} setUser={setUser} isSelf={isSelf} /><StatusPanel u={u} setUser={setUser} isSelf={isSelf} /></>} />
        <Route path="portfel" element={<WalletTab u={u} setUser={setUser} />} />
        <Route path="logowania" element={<LoginsTab id={u.id} />} />
        <Route path="ksiega" element={<LedgerTab id={u.id} />} />
        <Route path="historia" element={<HistoryTab id={u.id} />} />
        <Route path="*" element={<Navigate to={`${base}/dane`} replace />} />
      </Routes>
    </>
  );
}
