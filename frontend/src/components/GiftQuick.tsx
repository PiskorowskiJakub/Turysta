import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { FriendsResponse } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { formatCoins } from '../format';
import Coins from './Coins';

/** Skrot na stronie glownej: podaruj dukaty znajomemu (z potwierdzeniem) oraz licznik przekazanych i odebranych prezentow. */
export default function GiftQuick() {
  const { t } = useTranslation();
  const { refresh } = useAuth();
  const [data, setData] = useState<FriendsResponse | null>(null);
  const [selected, setSelected] = useState('');
  const [confirming, setConfirming] = useState(false);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<{ ok: boolean; text: string } | null>(null);

  const load = useCallback(async () => {
    try {
      setData(await api<FriendsResponse>('GET', '/friends'));
    } catch {
      setData(null);
    }
  }, []);
  useEffect(() => {
    void load();
  }, [load]);

  if (!data) return null;
  const { gifts } = data;
  const options = data.friends.filter((f) => gifts.per_friend[String(f.id)]?.status === 'ok');
  const chosen = options.find((f) => String(f.id) === selected) ?? null;

  const send = async () => {
    if (!chosen) return;
    setBusy(true);
    setMessage(null);
    try {
      const r = await api<FriendsResponse & { gift: { cents: number; bonus_cents: number } }>('POST', `/friends/${chosen.id}/gift`);
      setData(r);
      setSelected('');
      setConfirming(false);
      setMessage({ ok: true, text: t('friends.giftSent', { name: chosen.name, amount: formatCoins(r.gift.cents), bonus: formatCoins(r.gift.bonus_cents) }) });
      void refresh();
    } catch (e) {
      setConfirming(false);
      setMessage({ ok: false, text: e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network') });
      void load();
    } finally {
      setBusy(false);
    }
  };

  return (
    <section className="panel">
      <h2>{t('giftQuick.title')}</h2>
      {message && <p className={message.ok ? 'form-notice' : 'form-error'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}
      {data.friends.length === 0 ? (
        <p className="muted">{t('giftQuick.noFriends')} <Link to="/profil/znajomi">{t('giftQuick.addFriends')}</Link></p>
      ) : options.length === 0 ? (
        <p className="muted">{gifts.sent_left === 0 ? t('friends.giftLimit') : t('giftQuick.nobody', { hours: gifts.min_friend_hours })}</p>
      ) : confirming && chosen ? (
        <div className="gift-confirm" role="group" aria-label={t('friends.giftConfirmTitle', { name: chosen.name })}>
          <p>{t('friends.giftConfirm', { name: chosen.name, amount: formatCoins(gifts.cents), bonus: formatCoins(gifts.bonus_cents) })}</p>
          <span className="actions">
            <button type="button" className="button small" disabled={busy} onClick={() => void send()}>{t('friends.giftConfirmYes', { amount: formatCoins(gifts.cents) })}</button>
            <button type="button" className="button small secondary" disabled={busy} onClick={() => setConfirming(false)}>{t('admin.cancel')}</button>
          </span>
        </div>
      ) : (
        <div className="invite-row">
          <select value={selected} onChange={(e) => setSelected(e.target.value)} aria-label={t('giftQuick.pick')}>
            <option value="">{t('giftQuick.pick')}</option>
            {options.map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
          </select>
          <button type="button" className="button small" disabled={!chosen} onClick={() => setConfirming(true)}>
            {t('friends.gift')} ({formatCoins(gifts.cents)} {t('wallet.coinsShort')})
          </button>
        </div>
      )}
      <dl className="admin-tiles invite-stats">
        <div className="admin-tile"><dt>{t('giftQuick.sent')}</dt><dd>{gifts.totals.sent_count} <small>(<Coins cents={gifts.totals.sent_cents} /> {t('wallet.coinsShort')})</small></dd></div>
        <div className="admin-tile"><dt>{t('giftQuick.received')}</dt><dd>{gifts.totals.received_count} <small>(<Coins cents={gifts.totals.received_cents} /> {t('wallet.coinsShort')})</small></dd></div>
      </dl>
      <p className="muted">{t('giftQuick.left', { left: gifts.sent_left, max: gifts.max_per_day })}</p>
      <p><Link className="button small secondary" to="/profil/znajomi">{t('giftQuick.goToList')}</Link></p>
      <p className="muted">{t('giftQuick.how')}</p>
    </section>
  );
}
