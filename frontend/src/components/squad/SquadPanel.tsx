import { useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import type { TFunction } from 'i18next';
import { api, ApiError } from '../../api/client';
import type { SquadChronicleEntry, SquadMember, SquadMeta, SquadMine, SquadProfile } from '../../api/types';
import PlayerAvatar from '../PlayerAvatar';
import { formatDate, formatDateTime } from '../../format';
import InfoHint from '../InfoHint';
import SquadFields, { type SquadDraft } from './SquadFields';
import { SquadTrailBanner } from './SquadTrailPanel';

export type SquadSection = 'przeglad' | 'czlonkowie' | 'zaproszeni' | 'oczekujacy' | 'ustawienia' | 'kronika';

interface Props {
  section: SquadSection;
  squad: SquadProfile;
  meta: SquadMeta;
  /** Nowy stan oddzialu po zmianie (zwrocony przez API). */
  onSquad: (next: SquadProfile) => void;
  /** Nowy przeglad po wyjsciu z oddzialu albo jego rozwiazaniu. */
  onMine: (next: SquadMine) => void;
}

type Confirm = { kind: 'leave' } | { kind: 'disband' } | { kind: 'kick'; id: number } | { kind: 'transfer'; id: number } | null;

/** Tekst wpisu kroniki; nazwy graczy wstawiamy z API (anonimowo, gdy gracz ukryl aktywnosc). */
export function chronicleText(t: TFunction, e: SquadChronicleEntry, specLabel: (code: string) => string): string {
  const who = e.user?.name ?? t('squad.someone');
  const target = e.target?.name ?? t('squad.someone');
  const p = e.payload;
  switch (e.type) {
    case 'created':
      return t('squad.chronicle.created', { who });
    case 'joined':
      return t('squad.chronicle.joined', { who });
    case 'left':
      return t('squad.chronicle.left', { who });
    case 'kicked':
      return t('squad.chronicle.kicked', { target, reason: t(`squad.kickReasons.${String(p.reason)}`, { defaultValue: String(p.reason) }) });
    case 'role':
      return t('squad.chronicle.role', { target, role: t(`squad.roles.${String(p.role)}`, { defaultValue: String(p.role) }) });
    case 'leader':
      return t(p.why === 'succession' ? 'squad.chronicle.leaderSuccession' : 'squad.chronicle.leader', { target });
    case 'name':
      return t(p.forced ? 'squad.chronicle.nameForced' : 'squad.chronicle.name', { who, name: String(p.name) });
    case 'spec':
      return t('squad.chronicle.spec', { who, spec: specLabel(String(p.spec)) });
    case 'fire_level':
      return t('squad.chronicle.fireLevel', { level: t(`squad.flame.${Number(p.level)}`), days: Number(p.streak) });
    case 'fire_dim':
      return t('squad.chronicle.fireDim', { level: t(`squad.flame.${Number(p.level)}`) });
    case 'trail_done':
      return t('squad.chronicle.trailDone', { theme: String(p.theme_name ?? ''), percent: Number(p.percent), count: Number(p.stamps) });
    default:
      return e.type;
  }
}

/** Panel wlasnego oddzialu: ognisko, czlonkowie z rolami, zgloszenia i zaproszenia, ustawienia oraz kronika. */
export default function SquadPanel({ section, squad, meta, onSquad, onMine }: Props) {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<{ ok: boolean; text: string } | null>(null);
  const [fields, setFields] = useState<Record<string, string>>({});
  const [confirm, setConfirm] = useState<Confirm>(null);
  const [kickReason, setKickReason] = useState('inactive');
  const [inviteName, setInviteName] = useState('');
  const [draft, setDraft] = useState<SquadDraft>({ name: squad.name, specialization: squad.specialization, join_mode: squad.join_mode });

  const specLabel = (code: string) => meta.specializations.find((s) => s.code === code)?.label ?? code;
  const me = squad.me;
  const fire = squad.fire;

  const fail = (e: unknown) => {
    if (e instanceof ApiError) {
      setFields(e.fields);
      setMessage({ ok: false, text: Object.keys(e.fields).length > 0 ? t('errors.validation_failed', { defaultValue: e.message }) : t(`errors.${e.code}`, { defaultValue: e.message }) });
    } else {
      setMessage({ ok: false, text: t('errors.network') });
    }
  };

  /** Wykonuje akcje zwracajaca { squad } i podmienia stan. */
  const run = async (action: () => Promise<{ squad: SquadProfile } & Record<string, unknown>>, okText?: string) => {
    setBusy(true);
    setMessage(null);
    setFields({});
    try {
      const r = await action();
      onSquad(r.squad);
      setConfirm(null);
      if (okText) setMessage({ ok: true, text: okText });
      return r;
    } catch (e) {
      fail(e);
      return null;
    } finally {
      setBusy(false);
    }
  };

  const leaveOrDisband = async (kind: 'leave' | 'disband') => {
    setBusy(true);
    setMessage(null);
    try {
      const next = kind === 'leave' ? await api<SquadMine>('POST', '/squads/leave') : await api<SquadMine>('DELETE', `/squads/${squad.id}`);
      onMine(next);
      navigate('/oddzial/moj');
    } catch (e) {
      fail(e);
      setConfirm(null);
    } finally {
      setBusy(false);
    }
  };

  const onInvite = async (e: FormEvent) => {
    e.preventDefault();
    const r = await run(() => api('POST', `/squads/${squad.id}/invite`, { name: inviteName }), undefined);
    if (r) {
      setMessage({ ok: true, text: t((r as { joined?: boolean }).joined ? 'squad.invite.joined' : 'squad.invite.sent', { name: inviteName }) });
      setInviteName('');
    }
  };

  const onSaveSettings = async (e: FormEvent) => {
    e.preventDefault();
    const body: Record<string, string> = {};
    if (draft.name !== squad.name) body.name = draft.name;
    if (draft.specialization !== squad.specialization) body.specialization = draft.specialization;
    if (draft.join_mode !== squad.join_mode) body.join_mode = draft.join_mode;
    if (Object.keys(body).length === 0) return;
    await run(() => api('PATCH', `/squads/${squad.id}`, body), t('squad.settings.saved'));
  };

  const canKick = (m: SquadMember): boolean => {
    if (!me.can_manage || m.role === 'leader') return false;
    if (m.role === 'deputy' && !me.is_leader) return false;
    return true;
  };

  const memberActions = (m: SquadMember) => {
    if (confirm?.kind === 'kick' && confirm.id === m.id) {
      return (
        <span className="actions">
          <select value={kickReason} onChange={(e) => setKickReason(e.target.value)} aria-label={t('squad.members.kickReason')}>
            {meta.kick_reasons.map((r) => (
              <option key={r} value={r}>{t(`squad.kickReasons.${r}`)}</option>
            ))}
          </select>
          <button type="button" className="button small" disabled={busy} onClick={() => void run(() => api('POST', `/squads/${squad.id}/members/${m.id}/kick`, { reason: kickReason }), t('squad.members.kicked', { name: m.name }))}>{t('squad.members.kickConfirm')}</button>
          <button type="button" className="button small secondary" onClick={() => setConfirm(null)}>{t('squad.cancel')}</button>
        </span>
      );
    }
    if (confirm?.kind === 'transfer' && confirm.id === m.id) {
      return (
        <span className="actions">
          <button type="button" className="button small" disabled={busy} onClick={() => void run(() => api('POST', `/squads/${squad.id}/transfer`, { user_id: m.id }), t('squad.members.transferred', { name: m.name }))}>{t('squad.members.transferConfirm', { name: m.name })}</button>
          <button type="button" className="button small secondary" onClick={() => setConfirm(null)}>{t('squad.cancel')}</button>
        </span>
      );
    }
    return (
      <span className="actions">
        {me.can_manage && m.role !== 'leader' && (
          <select
            value={m.role ?? 'member'}
            disabled={busy}
            aria-label={t('squad.members.roleFor', { name: m.name })}
            onChange={(e) => void run(() => api('PUT', `/squads/${squad.id}/members/${m.id}/role`, { role: e.target.value }), t('squad.members.roleSaved'))}
          >
            {meta.assignable_roles.map((r) => (
              <option key={r} value={r}>{t(`squad.roles.${r}`)}</option>
            ))}
          </select>
        )}
        {me.is_leader && m.role !== 'leader' && (
          <button type="button" className="button small secondary" onClick={() => setConfirm({ kind: 'transfer', id: m.id })}>{t('squad.members.transfer')}</button>
        )}
        {canKick(m) && (
          <button type="button" className="button small secondary" onClick={() => setConfirm({ kind: 'kick', id: m.id })}>{t('squad.members.kick')}</button>
        )}
      </span>
    );
  };

  const flameName = t(`squad.flame.${fire?.level ?? squad.flame_level}`);
  const progress = fire ? Math.min(100, Math.round((fire.today.active / Math.max(1, fire.today.needed)) * 100)) : 0;

  return (
    <>
      <section className="panel squad-head">
        <h2>{squad.display_name}</h2>
        <p className="muted">
          {t('squad.head.members', { n: squad.members, max: squad.max_members })} · {t(`squad.modes.${squad.join_mode}`)} · {t('squad.head.founded', { date: formatDate(squad.created_at) })}
        </p>
        {squad.name_forced && <p className="form-error" role="alert">{t('squad.head.nameForced')}</p>}
        {message && <p className={message.ok ? 'form-success' : 'form-error'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}
      </section>

      {section === 'ustawienia' && me.can_manage && (
        <form className="panel" onSubmit={(e) => void onSaveSettings(e)} noValidate>
          <h2>{t('squad.settings.title')}</h2>
          <SquadFields
            meta={meta}
            value={draft}
            onChange={setDraft}
            errors={fields}
            disabled={busy}
            idPrefix="squad-edit"
            nameHint={squad.name_locked ? t('squad.settings.nameLocked') : squad.name_change_available_at ? t('squad.settings.nameLater', { date: formatDate(squad.name_change_available_at) }) : t('squad.settings.nameRule', { days: meta.limits.name_change_days })}
            specHint={squad.spec_change_available_at ? t('squad.settings.specLater', { date: formatDate(squad.spec_change_available_at) }) : t('squad.settings.specRule', { days: meta.limits.spec_change_days })}
          />
          <div className="actions">
            <button className="button" type="submit" disabled={busy}>{t('squad.settings.save')}</button>
          </div>
        </form>
      )}

      {section === 'ustawienia' && (
        <section className="panel">
          <h2>{t('squad.head.membership')}</h2>
        <div className="actions">
          {confirm?.kind === 'leave' ? (
            <>
              <button type="button" className="button small" disabled={busy} onClick={() => void leaveOrDisband('leave')}>{t('squad.head.leaveConfirm', { days: meta.limits.rejoin_cooldown_days })}</button>
              <button type="button" className="button small secondary" onClick={() => setConfirm(null)}>{t('squad.cancel')}</button>
            </>
          ) : (
            !me.is_leader && <button type="button" className="button small secondary" onClick={() => setConfirm({ kind: 'leave' })}>{t('squad.head.leave')}</button>
          )}
          {me.is_leader && (confirm?.kind === 'disband' ? (
            <>
              <button type="button" className="button small" disabled={busy} onClick={() => void leaveOrDisband('disband')}>{t('squad.head.disbandConfirm')}</button>
              <button type="button" className="button small secondary" onClick={() => setConfirm(null)}>{t('squad.cancel')}</button>
            </>
          ) : (
            <button type="button" className="button small secondary" onClick={() => setConfirm({ kind: 'disband' })}>{t('squad.head.disband')}</button>
          ))}
        </div>
        {me.is_leader && <p className="muted">{t('squad.head.leaderHint')}</p>}
        </section>
      )}

      {section === 'przeglad' && <SquadTrailBanner squadId={squad.id} />}

      {section === 'przeglad' && fire && (
        <section className="panel squad-fire">
          <InfoHint wide text={t('squad.fire.info', { percent: meta.limits.fire_threshold_percent, min: meta.limits.fire_min_active })} label={t('badges.moreInfo')} />
          <h2>{t('squad.fire.title')}</h2>
          <p className="squad-flame"><span className={`flame-badge level-${fire.level}`}>{flameName}</span> {t('squad.fire.streak', { n: fire.streak })}</p>
          <div className="progress" role="progressbar" aria-valuemin={0} aria-valuemax={fire.today.needed} aria-valuenow={Math.min(fire.today.active, fire.today.needed)} aria-label={t('squad.fire.todayLabel')}>
            <div className="progress-bar" style={{ width: `${progress}%` }} />
          </div>
          <p>{fire.today.lit ? t('squad.fire.litToday', { active: fire.today.active, members: fire.today.members }) : t('squad.fire.todayProgress', { active: fire.today.active, needed: fire.today.needed, members: fire.today.members })}</p>
          <ul className="squad-stats">
            <li><b>{fire.ember}</b><small>{t('squad.fire.ember')}</small></li>
            <li><b>{fire.best_streak}</b><small>{t('squad.fire.best')}</small></li>
            <li><b>{fire.lit_days}</b><small>{t('squad.fire.litDays')}</small></li>
          </ul>
          {fire.next_level && <p className="muted">{t('squad.fire.next', { level: t(`squad.flame.${fire.next_level.level}`), days: fire.next_level.in_days })}</p>}
          {fire.history.length > 0 && (
            <ul className="fire-history" aria-label={t('squad.fire.history')}>
              {[...fire.history].reverse().map((d) => (
                <li key={d.day} className={d.lit ? 'lit' : 'dim'} title={t('squad.fire.dayTitle', { day: formatDate(d.day), active: d.active, needed: d.needed, ember: d.ember })}>
                  <span aria-hidden="true">{d.lit ? '●' : '○'}</span>
                  <span className="sr-only">{t('squad.fire.dayTitle', { day: formatDate(d.day), active: d.active, needed: d.needed, ember: d.ember })}</span>
                </li>
              ))}
            </ul>
          )}
        </section>
      )}

      {section === 'czlonkowie' && (
      <section className="panel">
        <InfoHint wide text={t('squad.members.rolesInfo')} label={t('badges.moreInfo')} />
        <h2>{t('squad.members.title', { n: squad.member_list.length, max: squad.max_members })}</h2>
        <div className="table-scroll">
          <table className="ranking-table">
            <thead>
              <tr>
                <th>{t('squad.members.player')}</th>
                <th>{t('squad.members.role')}</th>
                <th>{t('squad.members.joined')}</th>
                {(me.can_manage || me.is_leader) && <th />}
              </tr>
            </thead>
            <tbody>
              {squad.member_list.map((m) => (
                <tr key={m.id}>
                  <td>
                    <span className="friend-who">
                      <PlayerAvatar src={m.avatar_url ?? undefined} name={m.name} size={32} />
                      <Link to={`/gracz/${m.id}`}>{m.name}</Link>
                      {m.online && <span className="online-dot on" title={t('friends.online')} />}
                      {m.recruit && <span className="recruit-badge">{t('squad.members.recruit')}</span>}
                    </span>
                  </td>
                  <td data-label={t('squad.members.role')}>
                    {t(`squad.roles.${m.role ?? 'member'}`)}
                  </td>
                  <td data-label={t('squad.members.joined')}>{formatDate(m.joined_at)}</td>
                  {(me.can_manage || me.is_leader) && <td className="num">{memberActions(m)}</td>}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <p className="field-hint">{t('squad.members.hint', { days: meta.limits.recruit_days })}</p>
      </section>
      )}

      {section === 'zaproszeni' && me.can_recruit && (
        <section className="panel">
          <h2>{t('squad.recruit.invitedTitle')}</h2>
          <form onSubmit={(e) => void onInvite(e)} noValidate>
            <div className="field">
              <label htmlFor="squad-invite">{t('squad.recruit.inviteLabel')}</label>
              <input id="squad-invite" value={inviteName} maxLength={40} autoComplete="off" onChange={(e) => setInviteName(e.target.value)} aria-invalid={fields.name ? true : undefined} />
              {fields.name && <p className="field-error">{fields.name}</p>}
            </div>
            <button className="button" type="submit" disabled={busy || inviteName.trim() === ''}>{t('squad.recruit.invite')}</button>
            <p className="field-hint">{t('squad.recruit.hint')}</p>
          </form>
          {squad.requests && squad.requests.invites.length > 0 && (
            <>
              <h3>{t('squad.recruit.invites', { n: squad.requests.invites.length })}</h3>
              <ul className="admin-list">
                {squad.requests.invites.map((p) => (
                  <li key={p.id}>
                    <Link to={`/gracz/${p.id}`}>{p.name}</Link>
                    <button type="button" className="button small secondary" disabled={busy} onClick={() => void run(() => api('DELETE', `/squads/${squad.id}/requests/${p.id}`), t('squad.recruit.withdrawn', { name: p.name }))}>{t('squad.recruit.withdraw')}</button>
                  </li>
                ))}
              </ul>
            </>
          )}
          {(!squad.requests || squad.requests.invites.length === 0) && <p className="muted">{t('squad.recruit.noInvites')}</p>}
        </section>
      )}

      {section === 'oczekujacy' && me.can_recruit && (
        <section className="panel">
          <h2>{t('squad.recruit.waitingTitle')}</h2>
          {squad.requests && squad.requests.requests.length > 0 && (
            <>
              <h3>{t('squad.recruit.requests', { n: squad.requests.requests.length })}</h3>
              <ul className="admin-list">
                {squad.requests.requests.map((p) => (
                  <li key={p.id}>
                    <Link to={`/gracz/${p.id}`}>{p.name}</Link>
                    <span className="actions">
                      <button type="button" className="button small" disabled={busy} onClick={() => void run(() => api('POST', `/squads/${squad.id}/requests/${p.id}/approve`), t('squad.recruit.approved', { name: p.name }))}>{t('squad.recruit.approve')}</button>
                      <button type="button" className="button small secondary" disabled={busy} onClick={() => void run(() => api('DELETE', `/squads/${squad.id}/requests/${p.id}`), t('squad.recruit.declined', { name: p.name }))}>{t('squad.recruit.decline')}</button>
                    </span>
                  </li>
                ))}
              </ul>
            </>
          )}
          {(!squad.requests || squad.requests.requests.length === 0) && <p className="muted">{t('squad.recruit.noRequests')}</p>}
        </section>
      )}

      {section === 'kronika' && (
      <section className="panel">
        <h2>{t('squad.chronicle.title')}</h2>
        {!squad.chronicle || squad.chronicle.length === 0 ? (
          <p className="muted">{t('squad.chronicle.empty')}</p>
        ) : (
          <ul className="notifications squad-chronicle">
            {squad.chronicle.map((e) => (
              <li key={e.id}>
                <span>{chronicleText(t, e, specLabel)}</span>
                <time dateTime={e.created_at}>{formatDateTime(e.created_at)}</time>
              </li>
            ))}
          </ul>
        )}
        <p className="field-hint">{t('squad.chronicle.hint')}</p>
      </section>
      )}
    </>
  );
}
