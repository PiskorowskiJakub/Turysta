import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { FriendsResponse } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import Coins from '../components/Coins';
import PlayerAvatar from '../components/PlayerAvatar';
import Points from '../components/Points';
import { usePolling } from '../hooks/usePolling';

/** Zakladka "Znajomi": zaproszenia, lista znajomych ze statusem online, dukatami i punktami. */
export default function FriendsPage() {
  const { t } = useTranslation();
  const { refresh } = useAuth();
  const [data, setData] = useState<FriendsResponse | null>(null);
  const [name, setName] = useState('');
  const [fieldError, setFieldError] = useState<string | undefined>();
  const [message, setMessage] = useState<{ ok: boolean; text: string } | null>(null);
  const [confirmId, setConfirmId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    try {
      setData(await api<FriendsResponse>('GET', '/friends'));
    } catch (e) {
      setMessage({ ok: false, text: e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network') });
    }
  }, [t]);

  useEffect(() => {
    void load();
  }, [load]);
  usePolling(() => void load(), 30000);

  const fail = (e: unknown) => {
    if (e instanceof ApiError) {
      setFieldError(e.fields.name);
      setMessage(e.fields.name ? null : { ok: false, text: t(`errors.${e.code}`, { defaultValue: e.message }) });
    } else {
      setMessage({ ok: false, text: t('errors.network') });
    }
  };

  const onInvite = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setFieldError(undefined);
    setMessage(null);
    try {
      const r = await api<FriendsResponse & { status: string; name: string }>('POST', '/friends/requests', { name });
      setData(r);
      setName('');
      setMessage({ ok: true, text: t(r.status === 'accepted' ? 'friends.nowFriends' : 'friends.invited', { name: r.name }) });
      void refresh();
    } catch (err) {
      fail(err);
    } finally {
      setBusy(false);
    }
  };

  const act = async (action: () => Promise<FriendsResponse>, okText: string) => {
    setBusy(true);
    setFieldError(undefined);
    setMessage(null);
    try {
      setData(await action());
      setConfirmId(null);
      setMessage({ ok: true, text: okText });
    } catch (err) {
      fail(err);
    } finally {
      setBusy(false);
    }
  };

  if (!data) return <p className="panel page-message">{message?.text ?? t('loading')}</p>;

  return (
    <>
      {message && <p className={message.ok ? 'form-success' : 'form-error'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}

      <form className="panel" onSubmit={(e) => void onInvite(e)} noValidate>
        <h2>{t('friends.addTitle')}</h2>
        <div className="field">
          <label htmlFor="friend-name">{t('friends.addLabel')}</label>
          <input id="friend-name" value={name} maxLength={40} autoComplete="off" onChange={(e) => setName(e.target.value)} aria-invalid={fieldError ? true : undefined} />
          {fieldError && <p className="field-error">{fieldError}</p>}
        </div>
        <button className="button" type="submit" disabled={busy || name.trim() === ''}>{t('friends.invite')}</button>
        <p className="muted">{t('friends.privacy')}</p>
      </form>

      {data.incoming.length > 0 && (
        <section className="panel">
          <h2>{t('friends.incomingTitle', { n: data.incoming.length })}</h2>
          <ul className="admin-list">
            {data.incoming.map((p) => (
              <li key={p.id}>
                <div className="friend-who">
                  <PlayerAvatar src={p.avatar_url ?? undefined} name={p.name} size={36} />
                  <Link to={`/gracz/${p.id}`}>{p.name}</Link>
                </div>
                <div className="actions">
                  <button type="button" className="button small" disabled={busy} onClick={() => void act(() => api<FriendsResponse>('POST', `/friends/${p.id}/accept`), t('friends.accepted', { name: p.name }))}>{t('friends.accept')}</button>
                  <button type="button" className="button small secondary" disabled={busy} onClick={() => void act(() => api<FriendsResponse>('DELETE', `/friends/${p.id}`), t('friends.declined', { name: p.name }))}>{t('friends.decline')}</button>
                </div>
              </li>
            ))}
          </ul>
        </section>
      )}

      <section className="panel">
        <h2>{t('friends.listTitle', { n: data.friends.length, max: data.limits.max_friends })}</h2>
        {data.friends.length === 0 ? (
          <p className="muted">{t('friends.empty')}</p>
        ) : (
          <div className="table-scroll">
            <table className="ranking-table friends-table">
              <thead>
                <tr>
                  <th>{t('friends.cols.player')}</th>
                  <th>{t('friends.cols.status')}</th>
                  <th className="num">{t('friends.cols.coins')}</th>
                  <th className="num">{t('friends.cols.points')}</th>
                  <th />
                </tr>
              </thead>
              <tbody>
                {data.friends.map((f) => (
                  <tr key={f.id}>
                    <td>
                      <span className="friend-who">
                        <PlayerAvatar src={f.avatar_url ?? undefined} name={f.name} size={32} />
                        <Link to={`/gracz/${f.id}`}>{f.name}</Link>
                      </span>
                    </td>
                    <td><span className={`online-dot${f.online ? ' on' : ''}`} aria-hidden="true" />{f.online ? t('friends.online') : t('friends.offline')}</td>
                    <td className="num"><Coins cents={f.coins_cents} /> {t('wallet.coinsShort')}</td>
                    <td className="num"><Points value={f.points} /></td>
                    <td className="num">
                      {confirmId === f.id ? (
                        <span className="actions">
                          <button type="button" className="button small" disabled={busy} onClick={() => void act(() => api<FriendsResponse>('DELETE', `/friends/${f.id}`), t('friends.removed', { name: f.name }))}>{t('friends.confirmRemove')}</button>
                          <button type="button" className="button small secondary" onClick={() => setConfirmId(null)}>{t('admin.cancel')}</button>
                        </span>
                      ) : (
                        <button type="button" className="button small secondary" onClick={() => setConfirmId(f.id)}>{t('friends.remove')}</button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <p className="muted">{t('friends.onlineHint', { minutes: data.limits.online_minutes })}</p>
      </section>

      {data.outgoing.length > 0 && (
        <section className="panel">
          <h2>{t('friends.outgoingTitle')}</h2>
          <ul className="admin-list">
            {data.outgoing.map((p) => (
              <li key={p.id}>
                <div className="friend-who">
                  <PlayerAvatar src={p.avatar_url ?? undefined} name={p.name} size={36} />
                  <Link to={`/gracz/${p.id}`}>{p.name}</Link>
                </div>
                <button type="button" className="button small secondary" disabled={busy} onClick={() => void act(() => api<FriendsResponse>('DELETE', `/friends/${p.id}`), t('friends.cancelled', { name: p.name }))}>{t('friends.cancel')}</button>
              </li>
            ))}
          </ul>
        </section>
      )}
    </>
  );
}
