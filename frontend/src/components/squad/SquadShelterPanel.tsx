import { useCallback, useEffect, useRef, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import type { TFunction } from 'i18next';
import { api, ApiError } from '../../api/client';
import type { SquadShelter, SquadShelterRoom } from '../../api/types';
import { formatCoins, formatDateTime, formatDuration, formatPercent } from '../../format';
import InfoHint from '../InfoHint';
import ProgressBar from '../ProgressBar';

function errText(t: TFunction, e: unknown): string {
  return e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network');
}

type Build = NonNullable<SquadShelter['build']>;

/** Pasek postepu trwajacej rozbudowy (liczony po stronie przegladarki z czasu startu i konca budowy). Po zakonczeniu woła onDone. */
function BuildProgress({ build, onDone }: { build: Build; onDone?: () => void }) {
  const { t } = useTranslation();
  const [now, setNow] = useState(() => Date.now());
  const start = new Date(build.started_at).getTime();
  const end = new Date(build.finish_at).getTime();
  const finished = now >= end;
  const percent = Math.min(100, Math.max(0, ((now - start) / Math.max(1, end - start)) * 100));

  useEffect(() => {
    if (finished) return undefined;
    const id = window.setInterval(() => setNow(Date.now()), 1000);
    return () => window.clearInterval(id);
  }, [finished]);

  // Po zakonczeniu budowy odswiezamy dane raz (z krotkim opoznieniem, zeby serwer zdazyl zakonczyc budowe).
  const doneRef = useRef(onDone);
  doneRef.current = onDone;
  const notified = useRef(false);
  useEffect(() => {
    if (!finished || notified.current) return undefined;
    notified.current = true;
    window.setTimeout(() => doneRef.current?.(), 1500);
    return undefined;
  }, [finished]);

  return (
    <>
      <ProgressBar value={percent / 100} />
      <p className="muted">
        {finished ? t('squad.shelter.buildFinished') : t('squad.shelter.buildRemaining', { percent: Math.floor(percent), time: formatDuration((end - now) / 1000) })}
      </p>
    </>
  );
}

/** Czas budowy w godzinach jako "12 godz." albo "2 dni 12 godz.". */
function hoursLabel(t: TFunction, hours: number): string {
  const days = Math.floor(hours / 24);
  const rest = hours % 24;
  if (days === 0) return t('squad.shelter.hours', { n: rest });
  return rest === 0 ? t('squad.shelter.days', { count: days }) : `${t('squad.shelter.days', { count: days })} ${t('squad.shelter.hours', { n: rest })}`;
}

function effectText(t: TFunction, room: SquadShelterRoom): string {
  if (room.effect_soon) return t('squad.shelter.effectSoon');
  return t(`squad.shelter.effect.${room.code}`, { pct: formatPercent(room.effect_percent ?? 0) });
}

/** Schronisko oddzialu: magazyn skladnikow, wplaty dukatow i rozbudowa pomieszczen bez maksymalnego poziomu. */
export default function SquadShelterPanel({ squadId }: { squadId: number }) {
  const { t } = useTranslation();
  const [shelter, setShelter] = useState<SquadShelter | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [coins, setCoins] = useState('');
  const [confirm, setConfirm] = useState<SquadShelterRoom['code'] | null>(null);

  const load = useCallback(async () => {
    try {
      setShelter((await api<{ shelter: SquadShelter }>('GET', `/squads/${squadId}/shelter`)).shelter);
      setError(null);
    } catch (e) {
      setError(errText(t, e));
    }
  }, [squadId, t]);

  useEffect(() => {
    void load();
  }, [load]);

  if (!shelter) return <p className="panel page-message">{error ?? t('loading')}</p>;

  const act = async (path: string, body: Record<string, unknown>) => {
    setBusy(true);
    setError(null);
    try {
      setShelter((await api<{ shelter: SquadShelter }>('POST', `/squads/${squadId}/shelter/${path}`, body)).shelter);
      return true;
    } catch (e) {
      setError(errText(t, e));
      return false;
    } finally {
      setBusy(false);
    }
  };

  const submitDeposit = async (e: FormEvent) => {
    e.preventDefault();
    const n = Number(coins);
    if (!Number.isInteger(n) || n < 1) {
      setError(t('squad.shelter.depositInvalid'));
      return;
    }
    if (await act('deposit', { coins: n })) setCoins('');
  };

  const { vault, deposit } = shelter;
  const maxCoins = Math.floor(deposit.remaining_cents / 100);

  return (
    <>
      <section className="panel">
        <InfoHint wide label={t('badges.moreInfo')} text={t('squad.shelter.info')} />
        <h2>{t('squad.shelter.vault')}</h2>
        <ul className="squad-stats">
          <li><InfoHint label={t('badges.moreInfo')} text={t('squad.shelter.emberHint')} /><b>{vault.ember}</b><small>{t('squad.shelter.ember')}</small></li>
          <li><InfoHint label={t('badges.moreInfo')} text={t('squad.shelter.stampsHint')} /><b>{vault.stamps}</b><small>{t('squad.shelter.stamps')}</small></li>
          <li><InfoHint label={t('badges.moreInfo')} text={t('squad.shelter.coinsHint')} /><b>{formatCoins(vault.cents)}</b><small>{t('squad.shelter.coins')}</small></li>
        </ul>
        {maxCoins < 1 && <p className="field-hint">{t('squad.shelter.limitReached', { limit: formatCoins(deposit.limit_cents) })}</p>}
        {maxCoins >= 1 && (
        <form className="shelter-deposit" onSubmit={(e) => void submitDeposit(e)}>
          <label htmlFor="shelter-coins">{t('squad.shelter.depositLabel')}</label>
          <div className="shelter-deposit-row">
            <input id="shelter-coins" type="number" inputMode="numeric" min={1} max={Math.max(1, maxCoins)} step={1} value={coins} onChange={(e) => setCoins(e.target.value)} disabled={busy} />
            <button type="submit" className="btn" disabled={busy || coins === ''}>{t('squad.shelter.deposit')}</button>
          </div>
          <p className="field-hint">{t('squad.shelter.depositHint', { left: maxCoins, limit: formatCoins(deposit.limit_cents) })}</p>
          {shelter.supplies_percent > 0 && <p className="field-hint">{t('squad.shelter.suppliesNote', { pct: formatPercent(shelter.supplies_percent) })}</p>}
        </form>
        )}
        {deposit.record_cents > 0 && <p className="field-hint">{t('squad.shelter.record', { amount: formatCoins(deposit.record_cents) })}</p>}
        {error && <p className="form-error" role="alert">{error}</p>}
      </section>

      <section className="panel">
        <h2>{t('squad.shelter.depositsTitle')}</h2>
        {shelter.ranking.length === 0 ? <p className="muted">{t('squad.shelter.depositsEmpty')}</p> : (
          <div className="deposit-columns">
            <div>
              <h3>{t('squad.shelter.rankingTitle')}</h3>
              <ol className="deposit-list">
                {shelter.ranking.map((r, i) => (
                  <li key={r.user_id}><span>{i + 1}. <Link to={`/gracz/${r.user_id}`}>{r.name}</Link></span><b>{formatCoins(r.cents)} {t('wallet.coinsShort')}</b></li>
                ))}
              </ol>
            </div>
            <div>
              <h3>{t('squad.shelter.historyTitle')}</h3>
              <ul className="deposit-list">
                {shelter.deposits.map((d, i) => (
                  <li key={i}><span><Link to={`/gracz/${d.user_id}`}>{d.name}</Link> <small className="muted">{formatDateTime(d.created_at)}</small></span><b>{formatCoins(d.cents)} {t('wallet.coinsShort')}</b></li>
                ))}
              </ul>
            </div>
          </div>
        )}
      </section>

      {shelter.build && (
        <section className="panel shelter-build">
          <h2>{t('squad.shelter.building')}</h2>
          <p>{t('squad.shelter.buildingLine', { room: t(`squad.shelter.rooms.${shelter.build.room}`), level: shelter.build.level, until: formatDateTime(shelter.build.finish_at) })}</p>
          <BuildProgress build={shelter.build} onDone={() => void load()} />
        </section>
      )}

      <section className="panel">
        <h2>{t('squad.shelter.roomsTitle')}</h2>
        <ul className="shelter-rooms">
          {shelter.rooms.map((room) => {
            const enough = {
              ember: vault.ember >= room.next.ember,
              stamps: vault.stamps >= room.next.stamps,
              coins: vault.cents >= room.next.cents,
            };
            return (
              <li key={room.code} className={'shelter-room' + (room.building ? ' building' : '')}>
                <div className="shelter-room-head">
                  <h3>{t(`squad.shelter.rooms.${room.code}`)}</h3>
                  <span className="trail-chip">{t('squad.shelter.level', { n: room.level })}</span>
                </div>
                <p className="muted">{t(`squad.shelter.desc.${room.code}`)}</p>
                <p>{effectText(t, room)}</p>
                <p className="shelter-cost">
                  <span className="muted">{t('squad.shelter.nextLevel', { n: room.next.level })}:</span>{' '}
                  <span className={enough.ember ? 'ok' : 'lack'}>{t('squad.shelter.costEmber', { n: room.next.ember })}</span>{' · '}
                  <span className={enough.stamps ? 'ok' : 'lack'}>{t('squad.shelter.costStamps', { n: room.next.stamps })}</span>{' · '}
                  <span className={enough.coins ? 'ok' : 'lack'}>{t('squad.shelter.costCoins', { n: formatCoins(room.next.cents) })}</span>{' · '}
                  <span className="muted">{t('squad.shelter.buildTime', { time: hoursLabel(t, room.next.hours) })}</span>
                </p>
                {!room.effect_soon && room.next_effect_percent !== null && (
                  <p className="field-hint">{t('squad.shelter.nextEffect', { pct: formatPercent(room.next_effect_percent) })}</p>
                )}
                {shelter.can_build && confirm !== room.code && (
                  <button type="button" className="btn" disabled={busy || !room.affordable || shelter.build !== null} onClick={() => setConfirm(room.code)}>
                    {t('squad.shelter.upgrade', { n: room.next.level })}
                  </button>
                )}
                {shelter.can_build && confirm === room.code && (
                  <div className="shelter-confirm" role="alertdialog" aria-label={t('squad.shelter.confirmTitle')}>
                    <p>
                      {t('squad.shelter.confirmText', {
                        room: t(`squad.shelter.rooms.${room.code}`),
                        n: room.next.level,
                        ember: room.next.ember,
                        stamps: room.next.stamps,
                        coins: formatCoins(room.next.cents),
                        time: hoursLabel(t, room.next.hours),
                      })}
                    </p>
                    <div className="trail-actions">
                      <button type="button" className="btn" disabled={busy} onClick={() => void act('upgrade', { room: room.code }).then(() => setConfirm(null))}>{t('squad.shelter.confirmYes')}</button>
                      <button type="button" className="btn ghost" disabled={busy} onClick={() => setConfirm(null)}>{t('squad.shelter.confirmNo')}</button>
                    </div>
                  </div>
                )}
              </li>
            );
          })}
        </ul>
        {!shelter.can_build && <p className="field-hint">{t('squad.shelter.noPower')}</p>}
      </section>
    </>
  );
}

/** Skrot schroniska na Przegladzie oddzialu: stan magazynu, trwajaca budowa i poziomy pomieszczen. */
export function SquadShelterTile({ squadId }: { squadId: number }) {
  const { t } = useTranslation();
  const [shelter, setShelter] = useState<SquadShelter | null>(null);

  useEffect(() => {
    let alive = true;
    api<{ shelter: SquadShelter }>('GET', `/squads/${squadId}/shelter`)
      .then((r) => {
        if (alive) setShelter(r.shelter);
      })
      .catch(() => undefined);
    return () => {
      alive = false;
    };
  }, [squadId]);

  if (!shelter) return null;
  return (
    <section className="panel shelter-tile">
      <div className="shelter-tile-head">
        <h2>{t('squad.shelter.tileTitle')}</h2>
        <Link to="/oddzial/schronisko">{t('squad.shelter.openShelter')}</Link>
      </div>
      <p className="shelter-tile-line">
        <span>{t('squad.shelter.ember')}: <b>{shelter.vault.ember}</b></span>
        <span>{t('squad.shelter.stamps')}: <b>{shelter.vault.stamps}</b></span>
        <span>{t('squad.shelter.coins')}: <b>{formatCoins(shelter.vault.cents)}</b></span>
      </p>
      {shelter.build && (
        <>
          <p className="shelter-tile-build">{t('squad.shelter.buildingLine', { room: t(`squad.shelter.rooms.${shelter.build.room}`), level: shelter.build.level, until: formatDateTime(shelter.build.finish_at) })}</p>
          <BuildProgress build={shelter.build} />
        </>
      )}
    </section>
  );
}
