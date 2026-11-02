import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { FriendRanking } from '../api/types';
import { formatDate } from '../format';
import Points from './Points';

const PERIODS = ['week', 'month', 'all'] as const;

/** Ranking gracza i jego znajomych: punkty zdobyte w tygodniu, w miesiacu albo lacznie. */
export default function FriendsRanking() {
  const { t } = useTranslation();
  const [period, setPeriod] = useState<(typeof PERIODS)[number]>('week');
  const [data, setData] = useState<FriendRanking | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setData(null);
    api<FriendRanking>('GET', `/ranking/friends?period=${period}`)
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
  }, [period, t]);

  const me = data?.rows.find((r) => r.me);
  const since = data?.since ? formatDate(data.since) : '';
  const note = period === 'week' ? t('ranking.friends.sinceWeek', { date: since }) : period === 'month' ? t('ranking.friends.sinceMonth', { date: since }) : t('ranking.friends.sinceAll');

  return (
    <>
      <div className="friends-sort" role="group" aria-label={t('ranking.friends.period')}>
        {PERIODS.map((p) => (
          <button key={p} type="button" className={`sort-btn${period === p ? ' active' : ''}`} aria-pressed={period === p} onClick={() => setPeriod(p)}>
            {t(`ranking.friends.${p}`)}
          </button>
        ))}
      </div>
      {error && <p className="form-error" role="alert">{error}</p>}
      {!data && !error && <p className="muted">{t('loading')}</p>}
      {data && (
        <>
          {me && data.rows.length > 1 && (
            <p className="page-message ranking-me">{t('ranking.friends.myPlace', { rank: me.rank, n: data.rows.length })}</p>
          )}
          <p className="muted">{note}</p>
          {data.rows.length <= 1 && <p className="muted">{t('ranking.friends.empty')}</p>}
          <div className="table-scroll">
            <table className="ranking-table">
              <thead>
                <tr>
                  <th className="num">{t('ranking.place')}</th>
                  <th>{t('ranking.player')}</th>
                  <th className="num">{period === 'all' ? t('wallet.points') : t('ranking.friends.gained')}</th>
                  {period !== 'all' && <th className="num">{t('ranking.friends.total')}</th>}
                </tr>
              </thead>
              <tbody>
                {data.rows.map((r) => (
                  <tr key={r.id} className={r.me ? 'me' : undefined}>
                    <td className="num">{r.rank}</td>
                    <td><Link to={r.me ? '/profil' : `/gracz/${r.id}`}>{r.name}</Link></td>
                    <td className="num"><Points value={r.points} /></td>
                    {period !== 'all' && <td className="num"><Points value={r.total} /></td>}
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
