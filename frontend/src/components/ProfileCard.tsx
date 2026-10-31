import { useTranslation } from 'react-i18next';
import { formatDate, formatDays } from '../format';
import PlayerAvatar from './PlayerAvatar';
import Points from './Points';

export interface ProfileCardData {
  name: string;
  avatarUrl?: string | null;
  world: { ordinal: number; name: string | null } | null;
  chapter: { ordinal: number; name: string | null } | null;
  rank: number;
  points: number;
  registeredAt: string;
  longestStreak: number;
  skillLevels: number;
}

/** Karta gracza: awatar z ramka, miejsce, punkty i najwazniejsze dane. Ta sama dla wlasnego i cudzego profilu. */
export default function ProfileCard({ data }: { data: ProfileCardData }) {
  const { t } = useTranslation();
  return (
    <section className="panel profile-card">
      <PlayerAvatar src={data.avatarUrl} name={data.name} size={132} />
      <div className="profile-card-main">
        <h1>{data.name}</h1>
        {data.world && data.chapter && (
          <p className="profile-where">
            <span>{t('world.worldNo', { n: data.world.ordinal })}{data.world.name && <>: <strong>{data.world.name}</strong></>}</span>
            <span>{t('world.chapterNo', { n: data.chapter.ordinal })}{data.chapter.name && <>: <strong>{data.chapter.name}</strong></>}</span>
          </p>
        )}
        <dl className="profile-facts">
          <div><dt>{t('profileCard.rank')}</dt><dd>{data.rank}</dd></div>
          <div><dt>{t('wallet.points')}</dt><dd><Points value={data.points} /></dd></div>
          <div><dt>{t('profileCard.registered')}</dt><dd>{formatDate(data.registeredAt)}</dd></div>
          <div><dt>{t('stats.longestStreak')}</dt><dd>{formatDays(data.longestStreak)}</dd></div>
          <div><dt>{t('profileCard.skillLevels')}</dt><dd>{data.skillLevels}</dd></div>
        </dl>
      </div>
    </section>
  );
}
