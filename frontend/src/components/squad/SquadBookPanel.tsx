import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../../api/client';
import type { SquadBook, SquadProfile } from '../../api/types';
import { tierName } from '../../badges';
import { formatDate } from '../../format';

/** Ksiega wypraw: historia i rekordy oddzialu. Rozdzialy odblokowuja sie wraz z poziomem Izby pamieci. */
export default function SquadBookPanel({ squad }: { squad: SquadProfile }) {
  const { t } = useTranslation();
  const [book, setBook] = useState<SquadBook | null>(null);
  const [error, setError] = useState<string | null>(null);
  const level = squad.memory.level;

  useEffect(() => {
    let alive = true;
    api<{ book: SquadBook }>('GET', `/squads/${squad.id}/book`)
      .then((r) => alive && setBook(r.book))
      .catch((e) => alive && setError(e instanceof ApiError ? e.message : t('errors.network')));
    return () => {
      alive = false;
    };
    // Odswiezamy, gdy zmieni sie poziom Izby pamieci.
  }, [squad.id, level, t]);

  if (error) return <p className="panel form-error" role="alert">{error}</p>;
  if (!book) return <p className="panel page-message">{t('loading')}</p>;

  const diff = (d: string) => t(`squad.trail.difficulty.${d}`);
  const locked = (code: string) => book.chapters.find((c) => c.code === code && !c.unlocked);
  const lockedNote = (code: string) => {
    const c = locked(code);
    return c ? <p className="muted">{t('squad.book.locked', { n: c.min_level })}</p> : null;
  };
  const trailLine = (tr: { week_start: string; difficulty: string; theme: string; percent: number; stamps: number }) =>
    t('squad.book.trailLine', { date: formatDate(tr.week_start), theme: tr.theme, difficulty: diff(tr.difficulty), percent: tr.percent.toLocaleString('pl-PL', { maximumFractionDigits: 1 }), count: tr.stamps });

  return (
    <>
      <section className="panel">
        <h2>{t('squad.book.title')}</h2>
        <p className="muted">{t('squad.book.intro', { level })}</p>
        {level === 0 && <p className="form-notice">{t('squad.book.needRoom')}</p>}
      </section>

      {book.records && (
        <section className="panel">
          <h2>{t('squad.book.records')}</h2>
          <ul className="squad-stats">
            <li><b>{book.records.trails_done}</b><small>{t('squad.book.trailsDone')}</small></li>
            <li><b>{book.records.stamps_total}</b><small>{t('squad.book.stampsTotal')}</small></li>
            <li><b>{book.records.best_streak}</b><small>{t('squad.fire.best')}</small></li>
            <li><b>{book.records.lit_days}</b><small>{t('squad.fire.litDays')}</small></li>
          </ul>
          {book.records.best_trail && <p>{t('squad.book.bestTrail')}: {trailLine(book.records.best_trail)}</p>}
          <p className="muted">{t('squad.head.founded', { date: formatDate(book.records.founded) })}</p>
        </section>
      )}

      {book.trails && (
        <section className="panel">
          <h2>{level >= (book.chapters.find((c) => c.code === 'full')?.min_level ?? 99) ? t('squad.book.fullHistory') : t('squad.book.recent')}</h2>
          {book.trails.length === 0 ? <p className="muted">{t('squad.book.noTrails')}</p> : (
            <ul className="book-list">{book.trails.map((tr) => <li key={tr.week_start}>{trailLine(tr)}</li>)}</ul>
          )}
          {lockedNote('full')}
        </section>
      )}

      {book.milestones && (
        <section className="panel">
          <h2>{t('squad.book.milestones')}</h2>
          <ul className="book-list">
            {book.milestones.map((m) => (
              <li key={`${m.kind}:${m.ref ?? ''}:${m.tier ?? ''}`}>
                <b>{formatDate(m.at)}</b> {m.kind === 'badge'
                  ? t('squad.book.mBadge', { badge: t(`squad.badges.names.${m.ref}`, { defaultValue: m.ref ?? '' }), tier: t(`training.tier.${tierName(m.tier ?? 1)}`) })
                  : t(`squad.book.${m.kind === 'founded' ? 'mFounded' : 'mFirstTrail'}`)}
              </li>
            ))}
          </ul>
        </section>
      )}

      {book.walkers && (
        <section className="panel">
          <h2>{t('squad.book.walkers')}</h2>
          {book.walkers.length === 0 ? <p className="muted">{t('squad.book.noWalkers')}</p> : (
            <ol className="book-list">{book.walkers.map((w) => <li key={w.id}>{t('squad.book.walkerLine', { name: w.name, trails: w.trails, days: w.march_days })}</li>)}</ol>
          )}
        </section>
      )}

      {(locked('recent') || locked('milestones') || locked('walkers')) && (
        <section className="panel">
          <h2>{t('squad.book.next')}</h2>
          <ul className="book-list">
            {book.chapters.filter((c) => !c.unlocked && c.code !== 'full').map((c) => (
              <li key={c.code}>{t(`squad.book.chapter.${c.code}`)}: {t('squad.book.locked', { n: c.min_level })}</li>
            ))}
          </ul>
        </section>
      )}
    </>
  );
}
