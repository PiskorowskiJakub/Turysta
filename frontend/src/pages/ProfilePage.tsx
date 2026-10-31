import { useEffect, useState } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api } from '../api/client';
import type { Avatar } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import ProfileCard from '../components/ProfileCard';
import BadgeBook from '../components/BadgeBook';
import { useBadges } from '../hooks/useBadges';
import Notifications from '../components/Notifications';
import { Icon, Sprout } from '../components/icons';
import Coins from '../components/Coins';
import { formatDays } from '../format';

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
  // Umiejetnosci pogrupowane: podstawowe, potem swiaty (kod w{n}_...); pokazujemy tylko te, ktore gracz juz rozwija.
  const groupMap = new Map<number | null, typeof me.skills>();
  for (const sk of me.skills) {
    const m = /^w(\d+)_/.exec(sk.code);
    const world = m ? Number(m[1]) : null;
    groupMap.set(world, [...(groupMap.get(world) ?? []), sk]);
  }
  const skillGroups = [...groupMap.entries()]
    .sort(([a], [b]) => (a ?? 0) - (b ?? 0))
    .map(([world, items]) => ({ key: world ?? 0, world, items }));

  return (
    <>
    <ProfileCard
      hint={t('profileCard.publicInfo')}
      data={{
        name: me.user.character_name,
        gender: me.user.gender,
        avatarName: avatar?.name,
        avatarUrl: avatar?.image_url,
        world: me.progress ? { ordinal: me.progress.world_ordinal, name: me.progress.world_name } : null,
        chapter: me.progress ? { ordinal: me.progress.chapter_ordinal, name: me.progress.chapter_name } : null,
        rank: me.stats.rank,
        points: me.wallet.points,
        registeredAt: me.stats.registered_at,
        longestStreak: me.stats.longest_streak,
        skillLevels,
      }}
    />

    <p className="profile-edit-link"><Link className="button secondary" to="/profil/edycja">{t('profile.edit')}</Link></p>

    {badges.ready ? <BadgeBook data={badges} /> : null}

    <div className="card">
      {!me.user.email_verified && <p className="form-notice">{t('profile.verifyEmail')}</p>}

      <div className="stat-grid">
        {me.progress && (
          <section className="stat-group">
            <h3><span className="stat-ico"><Sprout stage={me.progress.chapter_ordinal} /></span>{t('world.progress')}</h3>
            <ul className="stats">
              <li><span>{t('world.worldNo', { n: me.progress.world_ordinal })}</span><strong>{me.progress.world_name}</strong></li>
              <li><span>{t('world.chapterNo', { n: me.progress.chapter_ordinal })}</span><strong>{me.progress.chapter_name}</strong></li>
            </ul>
          </section>
        )}

        <section className="stat-group">
          <h3><span className="stat-ico"><Icon name="coins" /></span>{t('profile.resources')}</h3>
          <ul className="stats">
            <li><span>{t('wallet.coins')}</span><strong><Coins cents={me.wallet.coins_cents} /> {t('wallet.coinsShort')}</strong></li>
            <li><span>{t('wallet.tickets')}</span><strong>{me.wallet.tickets}</strong></li>
          </ul>
        </section>

        <section className="stat-group">
          <h3><span className="stat-ico"><Icon name="points" /></span>{t('profile.activity')}</h3>
          <ul className="stats">
            <li><span>{t('stats.accountAge')}</span><strong>{me.stats.account_days === 0 ? t('stats.today') : formatDays(me.stats.account_days)}</strong></li>
            <li><span>{t('stats.activeDays')}</span><strong>{formatDays(me.stats.active_days)}</strong></li>
            <li><span>{t('stats.streak')}</span><strong>{formatDays(me.stats.current_streak)}</strong></li>
          </ul>
        </section>

        <section className="stat-group">
          <h3><span className="stat-ico"><Icon name="job" /></span>{t('stats.earnings')}</h3>
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
        </section>

        <section className="stat-group stat-wide">
          <h3><span className="stat-ico"><Icon name="skills" /></span>{t('profile.skills')}</h3>
          {skillGroups.map((g) => (
            <div key={g.key} className="skill-level-group">
              <h4>{g.world === null ? t('profile.basicSkills') : t('world.worldNo', { n: g.world })}</h4>
              <ul className="stats stats-cols">
                {g.items.map((sk) => (
                  <li key={sk.code}>
                    <span>{t(`skills.${sk.code}`, { defaultValue: sk.name })}</span>
                    <strong>{sk.level}</strong>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </section>
      </div>

      <Notifications />
    </div>
    </>
  );
}
