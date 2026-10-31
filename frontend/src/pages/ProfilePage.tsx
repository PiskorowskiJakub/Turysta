import { useEffect, useState } from 'react';
import { Navigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api } from '../api/client';
import type { Avatar } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import PlayerAvatar from '../components/PlayerAvatar';
import BadgeBook from '../components/BadgeBook';
import { useBadges } from '../hooks/useBadges';
import Notifications from '../components/Notifications';
import { Sprout } from '../components/icons';
import Coins from '../components/Coins';
import Points from '../components/Points';
import { formatDate, formatDays } from '../format';

export default function ProfilePage() {
  const { t } = useTranslation();
  const { me } = useAuth();
  const badges = useBadges();
  const [avatars, setAvatars] = useState<Avatar[]>([]);

  useEffect(() => {
    api<{ avatars: Avatar[] }>('GET', '/avatars')
      .then((r) => setAvatars(r.avatars))
      .catch(() => setAvatars([]));
  }, []);

  if (!me) return null;
  if (!me.user.tutorial_done) return <Navigate to="/samouczek" replace />;

  const avatar = avatars.find((a) => a.id === me.user.avatar_id);
  const skillLevels = me.skills.reduce((sum, s) => sum + s.level, 0);

  return (
    <>
    <section className="panel profile-card">
      <PlayerAvatar src={avatar?.image_url} name={me.user.character_name} size={132} />
      <div className="profile-card-main">
        <h1>{me.user.character_name}</h1>
        {me.progress && (
          <p className="profile-where">
            <span>{t('world.worldNo', { n: me.progress.world_ordinal })}: <strong>{me.progress.world_name}</strong></span>
            <span>{t('world.chapterNo', { n: me.progress.chapter_ordinal })}: <strong>{me.progress.chapter_name}</strong></span>
          </p>
        )}
        <dl className="profile-facts">
          <div><dt>{t('profileCard.rank')}</dt><dd>{me.stats.rank}</dd></div>
          <div><dt>{t('wallet.points')}</dt><dd><Points value={me.wallet.points} /></dd></div>
          <div><dt>{t('profileCard.registered')}</dt><dd>{formatDate(me.stats.registered_at)}</dd></div>
          <div><dt>{t('stats.longestStreak')}</dt><dd>{formatDays(me.stats.longest_streak)}</dd></div>
          <div><dt>{t('profileCard.skillLevels')}</dt><dd>{skillLevels}</dd></div>
        </dl>
      </div>
    </section>

    {badges.ready ? <BadgeBook data={badges} /> : null}

    <div className="card">
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
      </ul>

      <h2>{t('stats.title')}</h2>
      <ul className="stats">
        <li><span>{t('stats.accountAge')}</span><strong>{me.stats.account_days === 0 ? t('stats.today') : formatDays(me.stats.account_days)}</strong></li>
        <li><span>{t('stats.activeDays')}</span><strong>{formatDays(me.stats.active_days)}</strong></li>
        <li><span>{t('stats.streak')}</span><strong>{formatDays(me.stats.current_streak)}</strong></li>
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
    </>
  );
}
