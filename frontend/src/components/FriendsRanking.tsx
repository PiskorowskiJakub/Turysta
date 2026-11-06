import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { FriendRanking } from '../api/types';
import { formatDate } from '../format';
import Coins from './Coins';
import Points from './Points';
import SquadTag from './squad/SquadTag';

const PERIODS = ['week', 'month', 'all'] as const;
const METRICS = ['points', 'coins'] as const;

/** Ranking gracza i jego znajomych: punkty albo dukaty zdobyte w tygodniu, w miesiacu (takze wczesniejszych) albo lacznie. */
export default function FriendsRanking() {
  const { t } = useTranslation();
  const [period, setPeriod] = useState<(typeof PERIODS)[number]>('week');
  const [metric, setMetric] = useState<(typeof METRICS)[number]>('points');
  const [offset, setOffset] = useState(0);
  const [data, setData] = useState<FriendRanking | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setData(null);
    api<FriendRanking>('GET', `/ranking/friends?period=${period}&metric=${metric}&offset=${offset}`)
      .then((r) => {
        if (!cancelled) {
          setData(r);
          setError(null);
        }
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
      });
    return () => {
      cancelled = true;
    };
  }, [period, metric, offset, t]);

  const choosePeriod = (p: (typeof PERIODS)[number]) => {
    setPeriod(p);
    setOffset(0);
  };

  const me = data?.rows.find((r) => r.me);
  const range = data?.from && data.to ? t('ranking.friends.range', { from: formatDate(data.from), to: formatDate(data.to) }) : '';
  const past = (data?.offset ?? 0) > 0;
  const value = (v: number) => (metric === 'coins' ? <Coins cents={v} /> : <Points value={v} />);

  return (
    <>
      <div className="activity-controls">
        <div className="segmented" role="group" aria-label={t('ranking.friends.metric')}>
          {METRICS.map((m) => (
            <button key={m} type="button" className={'seg' + (metric === m ? ' active' : '')} aria-pressed={metric === m} onClick={() => setMetric(m)}>
              {t(`ranking.friends.metrics.${m}`)}
            </button>
          ))}
        </div>
        <div className="segmented" role="group" aria-label={t('ranking.friends.period')}>
          {PERIODS.map((p) => (
            <button key={p} type="button" className={'seg' + (period === p ? ' active' : '')} aria-pressed={period === p} onClick={() => choosePeriod(p)}>
              {t(`ranking.friends.${p}`)}
            </button>
          ))}
        </div>
      </div>
      {period !== 'all' && (
        <div className="ranking-pager period-nav">
          <button type="button" className="btn" disabled={!data || offset >= data.max_offset} onClick={() => setOffset(offset + 1)}>{t('ranking.friends.older', { context: period })}</button>
          <span>{offset === 0 ? t('ranking.friends.current', { context: period }) : range}</span>
          <button type="button" className="btn" disabled={offset === 0} onClick={() => setOffset(offset - 1)}>{t('ranking.friends.newer', { context: period })}</button>
        </div>
      )}
      {error && <p className="form-error" role="alert">{error}</p>}
      {!data && !error && <p className="muted">{t('loading')}</p>}
      {data && (
        <>
          {me && data.rows.length > 1 && (
            <p className="page-message ranking-me">{t('ranking.friends.myPlace', { rank: me.rank, n: data.rows.length })}</p>
          )}
          <p className="muted">{t(`ranking.friends.note_${metric}_${period}`, { range })}</p>
          {data.rows.length <= 1 && <p className="muted">{t('ranking.friends.empty')}</p>}
          <div className="table-scroll">
            <table className="ranking-table">
              <thead>
                <tr>
                  <th className="num">{t('ranking.place')}</th>
                  <th>{t('ranking.player')}</th>
                  <th>{t('ranking.squad')}</th>
                  <th className="num">{t(`ranking.friends.gained_${metric}${period === 'all' && metric === 'points' ? '_all' : ''}`)}</th>
                  {period !== 'all' && !past && <th className="num">{t(`ranking.friends.total_${metric}`)}</th>}
                </tr>
              </thead>
              <tbody>
                {data.rows.map((r) => (
                  <tr key={r.id} className={r.me ? 'me' : undefined}>
                    <td className="num">{r.rank}</td>
                    <td><Link to={r.me ? '/profil' : `/gracz/${r.id}`}>{r.name}</Link></td>
                    <td data-label={t('ranking.squad')}><SquadTag squad={r.squad} /></td>
                    <td className="num">{value(r.gained)}</td>
                    {period !== 'all' && !past && <td className="num">{value(r.total)}</td>}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </>
      )}
    </>
  );
}
