import { Navigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../auth/AuthContext';
import Notifications from '../components/Notifications';
import { Sprout } from '../components/icons';
import Coins from '../components/Coins';
import { formatDate, formatDays } from '../format';

export default function ProfilePage() {
  const { t } = useTranslation();
  const { me } = useAuth();

  if (!me) return null;
  if (!me.user.tutorial_done) return <Navigate to="/samouczek" replace />;

  return (
    <div className="card">
      <h1>{me.user.character_name}</h1>
      {!me.user.email_verified && <p className="form-notice">{t('profile.verifyEmail')}</p>}

      {me.progress && (
        <>
          <h2>{t('world.progress')}</h2>
          <div className="progress-card">
            <span className="stage stage-current"><Sprout stage={me.progress.chapter_ordinal} /></span>
            <div>
              <b>{t('world.worldNo', { n: me.progress.world_ordinal })}: {me.progress.world_name}</b>
              <span>{t('world.chapterNo', { n: me.progress.chapter_ordinal })}: {me.progress.chapter_name}</span>
            </div>
          </div>
        </>
      )}

      <h2>{t('profile.wallet')}</h2>
      <ul className="stats">
        <li><span>{t('wallet.coins')}</span><strong><Coins cents={me.wallet.coins_cents} /></strong></li>
        <li><span>{t('wallet.tickets')}</span><strong>{me.wallet.tickets}</strong></li>
        <li><span>{t('wallet.points')}</span><strong>{me.wallet.points}</strong></li>
      </ul>

      <h2>{t('stats.title')}</h2>
      <ul className="stats">
        <li><span>{t('stats.registered')}</span><strong>{formatDate(me.stats.registered_at)}</strong></li>
        <li><span>{t('stats.accountAge')}</span><strong>{me.stats.account_days === 0 ? t('stats.today') : formatDays(me.stats.account_days)}</strong></li>
        <li><span>{t('stats.activeDays')}</span><strong>{formatDays(me.stats.active_days)}</strong></li>
        <li><span>{t('stats.streak')}</span><strong>{formatDays(me.stats.current_streak)}</strong></li>
        <li><span>{t('stats.longestStreak')}</span><strong>{formatDays(me.stats.longest_streak)}</strong></li>
      </ul>

      <h2>{t('stats.earnings')}</h2>
      <ul className="stats">
        {me.stats.earnings.jobs.map((j) => (
          <li key={j.code}>
            <span>{t(`jobs.names.${j.code}`, { defaultValue: j.name })}</span>
            <strong><Coins cents={j.cents} /> {t('wallet.coinsShort')}</strong>
          </li>
        ))}
        {me.stats.earnings.other_cents > 0 && (
          <li><span>{t('stats.other')}</span><strong><Coins cents={me.stats.earnings.other_cents} /> {t('wallet.coinsShort')}</strong></li>
        )}
        <li className="stats-total"><span>{t('stats.total')}</span><strong><Coins cents={me.stats.earnings.total_cents} /> {t('wallet.coinsShort')}</strong></li>
      </ul>

      <h2>{t('profile.skills')}</h2>
      <ul className="stats">
        {me.skills.map((s) => (
          <li key={s.code}>
            <span>{t(`skills.${s.code}`, { defaultValue: s.name })}</span>
            <strong>{t('profile.level', { level: s.level })}</strong>
          </li>
        ))}
      </ul>

      <Notifications />
    </div>
  );
}
