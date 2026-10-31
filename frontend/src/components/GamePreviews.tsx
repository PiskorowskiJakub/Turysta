import { useTranslation } from 'react-i18next';
import type { DailyRewardState } from '../api/types';
import { formatDuration } from '../format';
import Coins from './Coins';
import DailyReward from './DailyReward';
import Points from './Points';
import ProgressBar from './ProgressBar';
import { Icon, type IconName } from './icons';

/**
 * Statyczne miniatury ekranow gry zlozone z prawdziwych komponentow i styli. Uzywane w samouczku
 * i na stronie dla niezalogowanych; nie reaguja na klikniecia i nie pobieraja zadnych danych.
 */

const MENU: { key: string; icon: IconName; label: string }[] = [
  { key: 'home', icon: 'home', label: 'nav.home' },
  { key: 'profile', icon: 'profile', label: 'nav.profile' },
  { key: 'skills', icon: 'skills', label: 'nav.skills' },
  { key: 'jobs', icon: 'job', label: 'nav.jobs' },
  { key: 'badges', icon: 'medal', label: 'nav.badges' },
  { key: 'ranking', icon: 'points', label: 'nav.ranking' },
];

/** Miniatura menu z gry; podswietlone pozycje to te, o ktorych opowiada krok. */
export function MenuPreview({ active }: { active: string[] }) {
  const { t } = useTranslation();
  return (
    <ul className="tp-menu" aria-hidden="true">
      {MENU.map((m) => (
        <li key={m.key} className={'tp-menu-item' + (active.includes(m.key) ? ' active' : '')}>
          <Icon name={m.icon} />
          <span>{t(m.label)}</span>
        </li>
      ))}
    </ul>
  );
}

/** Tabliczka z dukatami, biletami i punktami, taka jak u gory strony. */
export function WalletPreview() {
  const { t } = useTranslation();
  return (
    <div className="plaque tp-plaque" aria-hidden="true">
      <ul className="plaque-stats">
        <li>
          <Icon name="coins" />
          <span><b><Coins cents={200} /></b><small>{t('wallet.coins')}</small></span>
        </li>
        <li className="tickets">
          <Icon name="ticket" />
          <span><b>0</b><small>{t('wallet.tickets')}</small></span>
        </li>
        <li className="points">
          <Icon name="points" />
          <span><b><Points value={0} /></b><small>{t('wallet.points')}</small></span>
        </li>
      </ul>
    </div>
  );
}

/** Karta szkolenia jak w zakladce Umiejetnosci (tylko do ogladania). */
export function TrainingPreview() {
  const { t } = useTranslation();
  return (
    <article className="tile train-card" aria-hidden="true">
      <h3>{t('skills.fitness')}</h3>
      <div className="train-meta">
        <span>{t('training.cost')}: <strong><Coins cents={30} /> {t('wallet.coinsShort')}</strong></span>
        <span>{t('training.remaining')}: <strong>{formatDuration(1260)}</strong></span>
        <span>{t('training.level')}: <strong>1</strong></span>
      </div>
      <div className="icon-tile"><Icon name="fitness" /></div>
      <ProgressBar value={0.35} />
    </article>
  );
}

/** Karta pracy jak w zakladce Praca (tylko do ogladania). */
export function JobPreview() {
  const { t } = useTranslation();
  return (
    <article className="tile job-card" aria-hidden="true">
      <h3>{t('jobs.names.market')}</h3>
      <div className="job-desc">
        <p>{t('jobs.stories.market')}</p>
        <p className="job-policy">{t('jobs.policyProportional')}</p>
      </div>
      <div className="job-img"><div className="icon-tile"><Icon name="market" /></div></div>
      <div className="job-stats">
        <div className="lines">
          <div className="line">{t('jobs.time')}: <strong>{formatDuration(28800)}</strong></div>
          <div className="line">{t('jobs.reward')}: <strong><Coins cents={24700} /></strong></div>
        </div>
        <button type="button" className="btn" tabIndex={-1} disabled>{t('jobs.start')}</button>
      </div>
    </article>
  );
}

/** Szlak swiatow i rozdzialow oraz karta awansu jak w panelu Swiat (tylko do ogladania). */
export function WorldsPreview() {
  const { t } = useTranslation();
  return (
    <>
      <ol className="tp-path" aria-hidden="true">
        <li className="current">{t('world.worldNo', { n: 1 })} · {t('world.chapterNo', { n: 1 })}</li>
        <li>{t('world.chapterNo', { n: 2 })}</li>
        <li>{t('world.chapterNo', { n: 3 })}</li>
        <li className="gap">…</li>
        <li className="next-world">{t('world.worldNo', { n: 2 })}</li>
      </ol>
      <article className="tile train-card" aria-hidden="true">
        <h3>{t('tutorial.worlds.promo')}</h3>
        <div className="train-meta">
          <span>{t('training.cost')}: <strong><Coins cents={200} /> {t('wallet.coinsShort')}</strong></span>
          <span>{t('training.remaining')}: <strong>{formatDuration(300)}</strong></span>
          <span>{t('training.rank')}: <strong>2</strong></span>
        </div>
        <div className="icon-tile"><Icon name="rank" /></div>
        <ProgressBar value={0} />
      </article>
    </>
  );
}

const DAILY_SAMPLE: DailyRewardState = {
  days: [
    { day: 1, coins_cents: 20, points: 1 },
    { day: 2, coins_cents: 30, points: 2 },
    { day: 3, coins_cents: 40, points: 3 },
    { day: 4, coins_cents: 50, points: 4 },
    { day: 5, coins_cents: 70, points: 5 },
    { day: 6, coins_cents: 100, points: 7 },
    { day: 7, coins_cents: 200, points: 10 },
  ],
  claimed_through: 3,
  claimed_today: false,
  next_day: 4,
  can_claim: true,
};

/** Codzienna wedrowka z przykladowa seria (tylko do ogladania). */
export function DailyPreview() {
  return <DailyReward daily={DAILY_SAMPLE} busy={false} onClaim={() => undefined} />;
}

const RANKING_SAMPLE = [
  { rank: 1, name: 'Bieszczadnik', world: 3, chapter: 7, points: 4820 },
  { rank: 2, name: 'Tatrzanka', world: 3, chapter: 5, points: 3915 },
  { rank: 3, name: 'Mgła nad Gorcami', world: 2, chapter: 9, points: 3302 },
  { rank: 4, name: 'Wędrowiec', world: 2, chapter: 6, points: 2870 },
  { rank: 5, name: 'Kasia z Mazur', world: 2, chapter: 3, points: 2410 },
];

/** Czolowka rankingu z przykladowymi graczami (tylko do ogladania). */
export function RankingPreview() {
  const { t } = useTranslation();
  return (
    <section className="panel" aria-hidden="true">
      <h2>{t('ranking.title')}</h2>
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
          {RANKING_SAMPLE.map((r) => (
            <tr key={r.rank}>
              <td className="num">{r.rank}</td>
              <td>{r.name}</td>
              <td className="num">{r.world} / {r.chapter}</td>
              <td className="num"><Points value={r.points} /></td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}
