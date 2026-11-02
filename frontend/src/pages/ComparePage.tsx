import { Fragment, useEffect, useState, type ReactNode } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { CompareResponse, ComparePerson } from '../api/types';
import Coins from '../components/Coins';
import PlayerAvatar from '../components/PlayerAvatar';
import Points from '../components/Points';
import { formatDays } from '../format';

interface Row {
  key: string;
  label: string;
  a: number;
  b: number;
  /** Mniejsza liczba jest lepsza (miejsce w rankingu). */
  lower?: boolean;
  /** Bez wyroznienia (np. wiek konta). */
  neutral?: boolean;
  showA?: ReactNode;
  showB?: ReactNode;
}

function verdict(r: Row): 'a' | 'b' | null {
  if (r.neutral || r.a === r.b) return null;
  const aBetter = r.lower ? r.a < r.b : r.a > r.b;
  return aBetter ? 'a' : 'b';
}

/** Porownanie swojego konta z kontem znajomego: tabela statystyk i poziomow umiejetnosci, lepszy wynik na zielono. */
export default function ComparePage() {
  const { t } = useTranslation();
  const { id } = useParams();
  const [data, setData] = useState<CompareResponse | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setData(null);
    setError(null);
    api<CompareResponse>('GET', `/friends/${Number(id)}/compare`)
      .then(setData)
      .catch((e) => setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network')));
  }, [id, t]);

  if (error) {
    return (
      <section className="panel">
        <p className="form-error" role="alert">{error}</p>
        <Link className="button" to="/profil/znajomi">{t('compare.back')}</Link>
      </section>
    );
  }
  if (!data) return <p className="panel page-message">{t('loading')}</p>;

  const { me, friend } = data;
  const num = (n: number) => n.toLocaleString('pl-PL');
  const progress = (p: ComparePerson) => p.world * 1000 + p.chapter;
  const progressText = (p: ComparePerson) => (p.world > 0 ? t('compare.progressValue', { world: p.world, chapter: p.chapter }) : '–');

  const general: Row[] = [
    { key: 'rank', label: t('compare.rows.rank'), a: me.rank, b: friend.rank, lower: true, showA: `#${me.rank}`, showB: `#${friend.rank}` },
    { key: 'points', label: t('wallet.points'), a: me.points, b: friend.points, showA: <Points value={me.points} />, showB: <Points value={friend.points} /> },
    { key: 'coins', label: t('wallet.coins'), a: me.coins_cents, b: friend.coins_cents, showA: <Coins cents={me.coins_cents} />, showB: <Coins cents={friend.coins_cents} /> },
    { key: 'tickets', label: t('wallet.tickets'), a: me.tickets, b: friend.tickets },
    { key: 'progress', label: t('compare.rows.progress'), a: progress(me), b: progress(friend), showA: progressText(me), showB: progressText(friend) },
    { key: 'skillLevels', label: t('compare.rows.skillLevels'), a: me.skill_levels, b: friend.skill_levels },
    { key: 'badges', label: t('compare.rows.badges'), a: me.badges, b: friend.badges },
  ];
  const activity: Row[] = [
    { key: 'activeDays', label: t('stats.activeDays'), a: me.active_days, b: friend.active_days, showA: formatDays(me.active_days), showB: formatDays(friend.active_days) },
    { key: 'streak', label: t('stats.streak'), a: me.current_streak, b: friend.current_streak, showA: formatDays(me.current_streak), showB: formatDays(friend.current_streak) },
    { key: 'longest', label: t('compare.rows.longestStreak'), a: me.longest_streak, b: friend.longest_streak, showA: formatDays(me.longest_streak), showB: formatDays(friend.longest_streak) },
    { key: 'age', label: t('stats.accountAge'), a: me.account_days, b: friend.account_days, neutral: true, showA: me.account_days === 0 ? t('stats.today') : formatDays(me.account_days), showB: friend.account_days === 0 ? t('stats.today') : formatDays(friend.account_days) },
  ];
  const earnings: Row[] = [
    ...data.jobs.map((j) => ({ key: j.code, label: t(`jobs.names.${j.code}`, { defaultValue: j.name }), a: j.me, b: j.friend, showA: <Coins cents={j.me} />, showB: <Coins cents={j.friend} /> })),
    { key: 'total', label: t('stats.total'), a: me.earned_cents, b: friend.earned_cents, showA: <Coins cents={me.earned_cents} />, showB: <Coins cents={friend.earned_cents} /> },
  ];

  // Umiejetnosci pogrupowane: podstawowe, potem kolejne swiaty.
  const groups = new Map<number | null, typeof data.skills>();
  for (const s of data.skills) groups.set(s.world, [...(groups.get(s.world) ?? []), s]);
  const skillGroups = [...groups.entries()].sort(([x], [y]) => (x ?? 0) - (y ?? 0));

  const all: Row[] = [
    ...general, ...activity.filter((r) => !r.neutral), ...earnings,
    ...data.skills.map((s) => ({ key: s.code, label: s.name, a: s.me, b: s.friend })),
  ];
  let mine = 0;
  let theirs = 0;
  for (const r of all) {
    const v = verdict(r);
    if (v === 'a') mine += 1;
    else if (v === 'b') theirs += 1;
  }

  const section = (title: string, rows: Row[]) => (
    <Fragment key={title}>
      <tr className="compare-section"><th colSpan={3}>{title}</th></tr>
      {rows.map((r) => {
        const v = verdict(r);
        return (
          <tr key={r.key}>
            <th scope="row">{r.label}</th>
            <td className={`num${v === 'a' ? ' better' : ''}`}>{r.showA ?? num(r.a)}</td>
            <td className={`num${v === 'b' ? ' better' : ''}`}>{r.showB ?? num(r.b)}</td>
          </tr>
        );
      })}
    </Fragment>
  );

  return (
    <>
      <p className="player-back"><Link to="/profil/znajomi">← {t('compare.back')}</Link></p>
      <section className="panel compare">
        <h2>{t('compare.title')}</h2>
        <p className="muted">{t('compare.legend')}</p>
        <p className="compare-summary">{t('compare.summary', { mine, theirs, name: friend.name })}</p>
        <div className="table-scroll">
          <table className="ranking-table compare-table">
            <thead>
              <tr>
                <th />
                <th className="num">
                  <span className="compare-who"><PlayerAvatar src={me.avatar_url ?? undefined} name={me.name} size={44} />{t('compare.you')}</span>
                </th>
                <th className="num">
                  <span className="compare-who"><PlayerAvatar src={friend.avatar_url ?? undefined} name={friend.name} size={44} /><Link to={`/gracz/${friend.id}`}>{friend.name}</Link></span>
                </th>
              </tr>
            </thead>
            <tbody>
              {section(t('compare.sections.general'), general)}
              {section(t('compare.sections.activity'), activity)}
              {section(t('compare.sections.earnings'), earnings)}
              {skillGroups.map(([world, items]) =>
                section(
                  world === null ? t('compare.sections.basicSkills') : t('compare.sections.worldSkills', { n: world }),
                  items.map((s) => ({ key: s.code, label: t(`skills.${s.code}`, { defaultValue: s.name }), a: s.me, b: s.friend })),
                ),
              )}
            </tbody>
          </table>
        </div>
      </section>
    </>
  );
}
