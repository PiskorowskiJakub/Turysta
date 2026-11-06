import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { FriendsResponse } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import Coins from '../components/Coins';
import InfoHint from '../components/InfoHint';
import SquadEmblem from '../components/squad/SquadEmblem';
import PlayerAvatar from '../components/PlayerAvatar';
import Points from '../components/Points';
import { formatCoins } from '../format';
import { usePolling } from '../hooks/usePolling';

type SortKey = 'coins' | 'points';
interface Sort {
  key: SortKey;
  dir: 'desc' | 'asc';
}

/** Zakladka "Znajomi": zaproszenia, lista znajomych ze statusem online, dukatami i punktami. */
export default function FriendsPage() {
  const { t } = useTranslation();
  const { refresh } = useAuth();
  const [data, setData] = useState<FriendsResponse | null>(null);
  const [name, setName] = useState('');
  const [fieldError, setFieldError] = useState<string | undefined>();
  const [message, setMessage] = useState<{ ok: boolean; text: string } | null>(null);
  const [confirmId, setConfirmId] = useState<number | null>(null);
  const [giftId, setGiftId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  // Klikniecie w kolumne: malejaco, potem rosnaco, potem domyslna kolejnosc (online, nazwa).
  const [sort, setSort] = useState<Sort | null>(null);

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
      setGiftId(null);
      if (okText) setMessage({ ok: true, text: okText });
    } catch (err) {
      fail(err);
    } finally {
      setBusy(false);
    }
  };

  const sendGift = (id: number, friendName: string) =>
    act(async () => {
      const r = await api<FriendsResponse & { gift: { cents: number; bonus_cents: number } }>('POST', `/friends/${id}/gift`);
      setMessage({ ok: true, text: t('friends.giftSent', { name: friendName, amount: formatCoins(r.gift.cents), bonus: formatCoins(r.gift.bonus_cents) }) });
      void refresh();
      return r;
    }, '');

  const toggleSort = (key: SortKey) =>
    setSort((s) => (s?.key !== key ? { key, dir: 'desc' } : s.dir === 'desc' ? { key, dir: 'asc' } : null));

  const friends = useMemo(() => {
    const list = data?.friends ?? [];
    if (!sort) return list;
    const field = sort.key === 'coins' ? 'coins_cents' : 'points';
    const sign = sort.dir === 'desc' ? -1 : 1;
    return [...list].sort((a, b) => sign * (a[field] - b[field]) || a.name.localeCompare(b.name, 'pl'));
  }, [data, sort]);

  const sortHeader = (key: SortKey, label: string) => {
    const active = sort?.key === key;
    return (
      <button
        type="button"
        className={`sort-btn${active ? ' active' : ''}`}
        onClick={() => toggleSort(key)}
        aria-label={t('friends.sortBy', { column: label })}
      >
        {label}
        <span aria-hidden="true">{active ? (sort?.dir === 'desc' ? ' ▼' : ' ▲') : ' ↕'}</span>
      </button>
    );
  };
  const ariaSort = (key: SortKey) => (sort?.key === key ? (sort?.dir === 'desc' ? 'descending' : 'ascending') : 'none');

  const giftButton = (id: number, friendName: string) => {
    const g = data?.gifts.per_friend[String(id)];
    if (!g || !data) return null;
    const hint = g.status === 'sent' ? t('friends.giftAlready') : g.status === 'early' ? t('friends.giftEarly', { hours: data.gifts.min_friend_hours }) : g.status === 'limit' ? t('friends.giftLimit') : t('friends.giftTitle', { amount: formatCoins(data.gifts.cents) });
    return (
      <button type="button" className="button small" disabled={busy || g.status !== 'ok'} title={hint} aria-label={`${t('friends.gift')}: ${friendName}. ${hint}`} onClick={() => { setConfirmId(null); setGiftId(id); }}>
        {g.status === 'sent' ? t('friends.giftSentShort') : `${t('friends.gift')} (${formatCoins(data.gifts.cents)} ${t('wallet.coinsShort')})`}
      </button>
    );
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
        <InfoHint wide text={t('friends.listInfo', { minutes: data.limits.online_minutes, amount: formatCoins(data.gifts.cents), bonus: formatCoins(data.gifts.bonus_cents), max: data.gifts.max_per_day, hours: data.gifts.min_friend_hours })} label={t('badges.moreInfo')} />
        <h2>{t('friends.listTitle', { n: data.friends.length, max: data.limits.max_friends })}</h2>
        {data.friends.length === 0 ? (
          <p className="muted">{t('friends.empty')}</p>
        ) : (
          <>
          <div className="friends-sort">
            <span>{t('friends.sort')}:</span>
            {sortHeader('coins', t('friends.cols.coins'))}
            {sortHeader('points', t('friends.cols.points'))}
          </div>
          <div className="table-scroll">
            <table className="ranking-table friends-table">
              <thead>
                <tr>
                  <th>{t('friends.cols.player')}</th>
                  <th>{t('friends.cols.squad')}</th>
                  <th>{t('friends.cols.status')}</th>
                  <th className="num" aria-sort={ariaSort('coins')}>{sortHeader('coins', t('friends.cols.coins'))}</th>
                  <th className="num" aria-sort={ariaSort('points')}>{sortHeader('points', t('friends.cols.points'))}</th>
                  <th />
                </tr>
              </thead>
              <tbody>
                {friends.map((f) => (
                  <tr key={f.id}>
                    <td>
                      <span className="friend-who">
                        <PlayerAvatar src={f.avatar_url ?? undefined} name={f.name} size={32} />
                        <Link to={`/gracz/${f.id}`}>{f.name}</Link>
                      </span>
                    </td>
                    <td data-label={t('friends.cols.squad')}>
                      {f.squad ? (
                        <span className="squad-title">
                          <SquadEmblem emblem={f.squad.emblem} frame={f.squad.frame} size={28} />
                          <Link to={`/oddzial/${f.squad.id}`}>{f.squad.display_name}</Link>
                        </span>
                      ) : <span className="muted">—</span>}
                    </td>
                    <td><span className={`online-dot${f.online ? ' on' : ''}`} aria-hidden="true" />{f.online ? t('friends.online') : t('friends.offline')}</td>
                    <td className="num" data-label={t('friends.cols.coins')}><Coins cents={f.coins_cents} /> {t('wallet.coinsShort')}</td>
                    <td className="num" data-label={t('friends.cols.points')}><Points value={f.points} /></td>
                    <td className="num">
                      {giftId === f.id && data.gifts.per_friend[String(f.id)]?.status === 'ok' ? (
                        <div className="gift-confirm" role="group" aria-label={t('friends.giftConfirmTitle', { name: f.name })}>
                          <p>{t('friends.giftConfirm', { name: f.name, amount: formatCoins(data.gifts.cents), bonus: formatCoins(data.gifts.bonus_cents) })}</p>
                          <span className="actions">
                            <button type="button" className="button small" disabled={busy} onClick={() => void sendGift(f.id, f.name)}>{t('friends.giftConfirmYes', { amount: formatCoins(data.gifts.cents) })}</button>
                            <button type="button" className="button small secondary" disabled={busy} onClick={() => setGiftId(null)}>{t('admin.cancel')}</button>
                          </span>
                        </div>
                      ) : confirmId === f.id ? (
                        <span className="actions">
                          <button type="button" className="button small" disabled={busy} onClick={() => void act(() => api<FriendsResponse>('DELETE', `/friends/${f.id}`), t('friends.removed', { name: f.name }))}>{t('friends.confirmRemove')}</button>
                          <button type="button" className="button small secondary" onClick={() => setConfirmId(null)}>{t('admin.cancel')}</button>
                        </span>
                      ) : (
                        <span className="actions">
                          {giftButton(f.id, f.name)}
                          <Link className="button small" to={`/profil/znajomi/porownaj/${f.id}`}>{t('friends.compare')}</Link>
                          <button type="button" className="button small secondary" onClick={() => { setGiftId(null); setConfirmId(f.id); }}>{t('friends.remove')}</button>
                        </span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          </>
        )}
        {data.gifts.multiplier_permille > 1000 && <p className="muted">{t('friends.giftMultiplier', { mult: data.gifts.multiplier_permille / 1000, n: data.gifts.active_referrals })}</p>}
        {data.friends.length > 0 && <p className="muted">{t('friends.giftLeft', { left: data.gifts.sent_left, max: data.gifts.max_per_day })}</p>}
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
