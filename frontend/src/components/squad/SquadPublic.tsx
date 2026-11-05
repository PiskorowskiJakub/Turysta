import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../../api/client';
import type { SquadMeta, SquadProfile } from '../../api/types';
import PlayerAvatar from '../PlayerAvatar';
import { formatDate } from '../../format';

interface Props {
  squad: SquadProfile;
  meta: SquadMeta;
  /** Wywolywane po kazdej zmianie - odswieza profil i przeglad "moj oddzial". */
  onChanged: () => void;
}

/** Publiczny profil oddzialu: dane, lista czlonkow z tytulami i datami dolaczenia oraz przyciski dolaczenia i zgloszenia nazwy. */
export default function SquadPublic({ squad, meta, onChanged }: Props) {
  const { t } = useTranslation();
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<{ ok: boolean; text: string } | null>(null);
  const [reporting, setReporting] = useState(false);
  const [reason, setReason] = useState(meta.report_reasons[0] ?? 'other');
  const me = squad.me;
  const specLabel = meta.specializations.find((s) => s.code === squad.specialization)?.label ?? squad.specialization;

  const act = async (action: () => Promise<{ result?: string; message?: string }>, okText: string) => {
    setBusy(true);
    setMessage(null);
    try {
      const r = await action();
      onChanged();
      setMessage({ ok: true, text: r.result === 'joined' ? t('squad.public.joined') : okText });
      setReporting(false);
    } catch (e) {
      setMessage({ ok: false, text: e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network') });
    } finally {
      setBusy(false);
    }
  };

  const eligibility = me.eligibility;
  const blocked = eligibility ? !eligibility.ok : false;

  return (
    <>
      <section className="panel squad-head">
        <h2>{squad.display_name}</h2>
        <p className="muted">
          {specLabel} · {t('squad.head.members', { n: squad.members, max: squad.max_members })} · {t(`squad.flame.${squad.flame_level}`)}
        </p>
        <p className="muted">{t('squad.head.founded', { date: formatDate(squad.created_at) })}</p>
        {message && <p className={message.ok ? 'form-success' : 'form-error'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}

        {!me.is_member && (
          <div className="actions">
            {me.pending === 'invite' && (
              <button type="button" className="button" disabled={busy} onClick={() => void act(() => api('POST', `/squads/${squad.id}/invitation/accept`), t('squad.public.joined'))}>{t('squad.public.acceptInvite')}</button>
            )}
            {me.pending === 'request' && (
              <button type="button" className="button secondary" disabled={busy} onClick={() => void act(() => api('DELETE', `/squads/${squad.id}/request`), t('squad.public.requestDropped'))}>{t('squad.public.dropRequest')}</button>
            )}
            {me.pending === null && squad.join_mode !== 'invite' && (
              <button type="button" className="button" disabled={busy || blocked || squad.members >= squad.max_members} onClick={() => void act(() => api('POST', `/squads/${squad.id}/join`), t('squad.public.requestSent'))}>
                {squad.join_mode === 'open' ? t('squad.public.join') : t('squad.public.askToJoin')}
              </button>
            )}
            {me.pending === null && squad.join_mode === 'invite' && <span className="muted">{t('squad.public.inviteOnly')}</span>}
          </div>
        )}
        {!me.is_member && eligibility && !eligibility.ok && eligibility.reason && (
          <p className="field-hint">{t(`squad.eligibility.${eligibility.reason}`, { until: eligibility.until ? formatDate(eligibility.until) : '', days: meta.limits.min_account_days })}</p>
        )}
        {!me.is_member && squad.members >= squad.max_members && <p className="field-hint">{t('squad.public.full')}</p>}
        {me.is_member && <p><Link to="/oddzial/moj">{t('squad.public.openMine')}</Link></p>}
      </section>

      <section className="panel">
        <h2>{t('squad.shelter.publicTitle')}</h2>
        <ul className="shelter-levels">
          {(Object.keys(squad.shelter_levels) as (keyof typeof squad.shelter_levels)[]).map((room) => (
            <li key={room}>
              <span>{t(`squad.shelter.rooms.${room}`)}</span>
              <b>{squad.shelter_levels[room] > 0 ? t('squad.shelter.level', { n: squad.shelter_levels[room] }) : '—'}</b>
            </li>
          ))}
        </ul>
      </section>

      <section className="panel">
        <h2>{t('squad.members.title', { n: squad.member_list.length, max: squad.max_members })}</h2>
        <div className="table-scroll">
          <table className="ranking-table">
            <thead>
              <tr>
                <th>{t('squad.members.player')}</th>
                <th>{t('squad.members.role')}</th>
                <th>{t('squad.members.joined')}</th>
              </tr>
            </thead>
            <tbody>
              {squad.member_list.map((m) => (
                <tr key={m.id}>
                  <td>
                    <span className="friend-who">
                      <PlayerAvatar src={m.avatar_url ?? undefined} name={m.name} size={32} />
                      <Link to={`/gracz/${m.id}`}>{m.name}</Link>
                      {m.recruit && <span className="recruit-badge">{t('squad.members.recruit')}</span>}
                    </span>
                  </td>
                  <td data-label={t('squad.members.role')}>{m.title ? t(`squad.roles.${m.title}`) : '—'}</td>
                  <td data-label={t('squad.members.joined')}>{formatDate(m.joined_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      <section className="panel">
        {reporting ? (
          <div className="actions">
            <select value={reason} onChange={(e) => setReason(e.target.value)} aria-label={t('squad.report.reason')}>
              {meta.report_reasons.map((r) => (
                <option key={r} value={r}>{t(`squad.reportReasons.${r}`)}</option>
              ))}
            </select>
            <button type="button" className="button small" disabled={busy} onClick={() => void act(() => api('POST', `/squads/${squad.id}/report`, { reason }), t('squad.report.sent'))}>{t('squad.report.send')}</button>
            <button type="button" className="button small secondary" onClick={() => setReporting(false)}>{t('squad.cancel')}</button>
          </div>
        ) : (
          <button type="button" className="button small secondary" onClick={() => setReporting(true)}>{t('squad.report.open')}</button>
        )}
      </section>
    </>
  );
}
