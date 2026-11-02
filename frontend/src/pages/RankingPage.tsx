import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { RankingResponse, SkillsResponse } from '../api/types';
import Points from '../components/Points';

export default function RankingPage() {
  const { t } = useTranslation();
  const [data, setData] = useState<RankingResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(50);
  const [params] = useSearchParams();
  const initialSearch = (params.get('q') ?? '').slice(0, 40);
  const [search, setSearch] = useState(initialSearch);
  const [query, setQuery] = useState(initialSearch.trim());
  const [world, setWorld] = useState(0);
  const [worlds, setWorlds] = useState<number[]>([]);

  // Numery swiatow do filtra (bez nazw - nie zdradzamy swiatow, do ktorych gracz jeszcze nie dotarl).
  useEffect(() => {
    api<SkillsResponse>('GET', '/skills').then((s) => setWorlds(s.worlds.map((w) => w.ordinal))).catch(() => undefined);
  }, []);

  // Wyszukiwanie z opoznieniem, zeby nie pytac serwera przy kazdym znaku.
  useEffect(() => {
    const id = window.setTimeout(() => {
      setQuery(search.trim());
      setPage(1);
    }, 300);
    return () => window.clearTimeout(id);
  }, [search]);

  useEffect(() => {
    let cancelled = false;
    const qs = new URLSearchParams({ page: String(page), per_page: String(perPage) });
    if (query) qs.set('q', query);
    if (world > 0) qs.set('world', String(world));
    api<RankingResponse>('GET', `/ranking?${qs.toString()}`)
      .then((r) => {
        if (cancelled) return;
        setData(r);
        setError(null);
        if (r.page !== page) setPage(r.page);
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
      });
    return () => {
      cancelled = true;
    };
  }, [page, perPage, query, world, t]);

  const goToMe = () => {
    if (!data?.me) return;
    setSearch('');
    setQuery('');
    setWorld(0);
    setPage(data.me.page);
  };

  if (!data) return <p className="panel page-message">{error ?? t('loading')}</p>;

  return (
    <section className="panel trail-blue">
      <h1>{t('ranking.title')}</h1>
      {data.me && (
        <p className="page-message ranking-me">
          {t('ranking.myPlace', { rank: data.me.rank })} · <Points value={data.me.points} /> {t('ranking.pointsShort')}{' '}
          <button type="button" className="btn ranking-me-btn" onClick={goToMe}>{t('ranking.goToMe')}</button>
        </p>
      )}

      <div className="ranking-filters">
        <input
          type="search"
          value={search}
          maxLength={40}
          placeholder={t('ranking.searchPlaceholder')}
          aria-label={t('ranking.searchPlaceholder')}
          onChange={(e) => setSearch(e.target.value)}
        />
        <select value={world} aria-label={t('ranking.world')} onChange={(e) => { setWorld(Number(e.target.value)); setPage(1); }}>
          <option value={0}>{t('ranking.allWorlds')}</option>
          {worlds.map((n) => <option key={n} value={n}>{t('ranking.worldN', { n })}</option>)}
        </select>
        <select value={perPage} aria-label={t('ranking.perPage')} onChange={(e) => { setPerPage(Number(e.target.value)); setPage(1); }}>
          {data.per_page_options.map((n) => <option key={n} value={n}>{t('ranking.perPageN', { n })}</option>)}
        </select>
      </div>

      {error && <p className="form-error">{error}</p>}

      <div className="table-scroll">
      <table className="ranking-table">
        <thead>
          <tr>
            <th className="num">{t('ranking.place')}</th>
            <th>{t('ranking.player')}</th>
            <th className="num">{t('ranking.worldChapter')}</th>
            <th className="num">{t('wallet.points')}</th>
          </tr>
        </thead>
        <tbody>
          {data.rows.length === 0 ? (
            <tr><td colSpan={4} className="ranking-empty">{t('ranking.empty')}</td></tr>
          ) : data.rows.map((r) => (
            <tr key={r.id} className={r.me ? 'me' : undefined}>
              <td className="num">{r.rank}</td>
              <td><Link to={r.me ? '/profil' : `/gracz/${r.id}`}>{r.name}</Link></td>
              <td className="num">{r.world !== null ? `${r.world} / ${r.chapter}` : '–'}</td>
              <td className="num"><Points value={r.points} /></td>
            </tr>
          ))}
        </tbody>
      </table>
      </div>

      <div className="ranking-pager">
        <button type="button" className="btn" disabled={page <= 1} onClick={() => setPage(1)}>{t('ranking.first')}</button>
        <button type="button" className="btn" disabled={page <= 1} onClick={() => setPage(page - 1)}>{t('ranking.prev')}</button>
        <span>{t('ranking.pageOf', { page: data.page, pages: data.pages })} · {t('ranking.total', { n: data.total })}</span>
        <button type="button" className="btn" disabled={page >= data.pages} onClick={() => setPage(page + 1)}>{t('ranking.next')}</button>
        <button type="button" className="btn" disabled={page >= data.pages} onClick={() => setPage(data.pages)}>{t('ranking.last')}</button>
      </div>
    </section>
  );
}
