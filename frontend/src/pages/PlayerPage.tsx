import { useEffect, useState } from 'react';
import { Link, Navigate, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { PlayerProfile, SkillsResponse } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import BadgeBook from '../components/BadgeBook';
import ProfileCard from '../components/ProfileCard';
import { useBadgeView, type BadgesData } from '../hooks/useBadges';

/** Podglad profilu innego gracza: karta i ksiazeczka odznak (bez szczegolowych statystyk). */
export default function PlayerPage() {
  const { t } = useTranslation();
  const { me } = useAuth();
  const { id } = useParams();
  const playerId = Number(id);
  const [player, setPlayer] = useState<PlayerProfile | null>(null);
  const [skills, setSkills] = useState<SkillsResponse | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setPlayer(null);
    setError(null);
    Promise.all([api<PlayerProfile>('GET', `/players/${playerId}`), api<SkillsResponse>('GET', '/skills')])
      .then(([p, s]) => {
        if (cancelled) return;
        setPlayer(p);
        setSkills(s);
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
      });
    return () => {
      cancelled = true;
    };
  }, [playerId, t]);

  const view = useBadgeView(skills, player?.badges ?? null);

  if (me && me.user.id === playerId) return <Navigate to="/profil" replace />;
  if (error) {
    return (
      <section className="panel">
        <p className="form-error">{error}</p>
        <Link to="/ranking" className="btn">{t('player.backToRanking')}</Link>
      </section>
    );
  }
  if (!player || !skills) return <p className="panel page-message">{t('loading')}</p>;

  // Odznaki cudzego gracza: tylko do podgladu (bez wyboru wyroznionych i bez linku do wlasnej ksiazeczki).
  const badges = {
    ...view,
    ready: true,
    error: null,
    setError: () => undefined,
    skills,
    earned: player.badges,
    featured: player.featured,
    setFeatured: () => undefined,
  } as BadgesData;

  return (
    <>
      <p className="player-back"><Link to="/ranking">← {t('player.backToRanking')}</Link></p>
      <ProfileCard
        data={{
          name: player.name,
          gender: player.gender,
          avatarName: player.avatar_name,
          avatarUrl: player.avatar_url,
          world: player.world,
          chapter: player.chapter,
          rank: player.rank,
          points: player.points,
          registeredAt: player.registered_at,
          longestStreak: player.longest_streak,
          skillLevels: player.skill_levels,
        }}
      />
      <BadgeBook data={badges} showLink={false} />
    </>
  );
}
