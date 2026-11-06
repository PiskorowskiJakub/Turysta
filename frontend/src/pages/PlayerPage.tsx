import { useEffect, useState } from 'react';
import { Link, Navigate, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { FriendsResponse, FriendshipState, PlayerProfile, SkillsResponse, SquadMine } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import BadgeBook from '../components/BadgeBook';
import ProfileCard from '../components/ProfileCard';
import SquadEmblem from '../components/squad/SquadEmblem';
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
  const [friendship, setFriendship] = useState<FriendshipState>('none');
  const [friendMsg, setFriendMsg] = useState<{ ok: boolean; text: string } | null>(null);
  // Zapraszanie do oddzialu: dostepne dla czlonka z uprawnieniem rekrutacji, gdy gracz nie ma oddzialu.
  const [mySquad, setMySquad] = useState<SquadMine['squad']>(null);
  const [inviteMsg, setInviteMsg] = useState<{ ok: boolean; text: string } | null>(null);
  const [inviting, setInviting] = useState(false);

  useEffect(() => {
    let cancelled = false;
    api<SquadMine>('GET', '/squads/mine')
      .then((m) => !cancelled && setMySquad(m.squad))
      .catch(() => undefined);
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    setInviteMsg(null);
  }, [playerId]);

  const onSquadInvite = async () => {
    if (!mySquad || !player) return;
    setInviting(true);
    setInviteMsg(null);
    try {
      const r = await api<{ joined?: boolean }>('POST', `/squads/${mySquad.id}/invite`, { name: player.name });
      setInviteMsg({ ok: true, text: t(r.joined ? 'squad.invite.joined' : 'squad.invite.sent', { name: player.name }) });
    } catch (e) {
      const text = e instanceof ApiError ? (e.fields.name ?? t(`errors.${e.code}`, { defaultValue: e.message })) : t('errors.network');
      setInviteMsg({ ok: false, text });
    } finally {
      setInviting(false);
    }
  };

  useEffect(() => {
    let cancelled = false;
    setPlayer(null);
    setError(null);
    Promise.all([api<PlayerProfile>('GET', `/players/${playerId}`), api<SkillsResponse>('GET', '/skills')])
      .then(([p, s]) => {
        if (cancelled) return;
        setPlayer(p);
        setSkills(s);
        setFriendship(p.friendship);
        setFriendMsg(null);
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
      });
    return () => {
      cancelled = true;
    };
  }, [playerId, t]);

  const onFriend = async (action: 'invite' | 'accept' | 'remove') => {
    setFriendMsg(null);
    try {
      if (action === 'invite') {
        const r = await api<FriendsResponse & { status: string }>('POST', '/friends/requests', { user_id: playerId });
        setFriendship(r.status === 'accepted' ? 'friends' : 'outgoing');
      } else if (action === 'accept') {
        await api('POST', `/friends/${playerId}/accept`);
        setFriendship('friends');
      } else {
        await api('DELETE', `/friends/${playerId}`);
        setFriendship('none');
      }
    } catch (e) {
      setFriendMsg({ ok: false, text: e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network') });
    }
  };

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
      <p className="friend-action">
        {friendship === 'none' && <button type="button" className="button small" onClick={() => void onFriend('invite')}>{t('friends.addToFriends')}</button>}
        {friendship === 'outgoing' && <button type="button" className="button small secondary" onClick={() => void onFriend('remove')}>{t('friends.cancelInvite')}</button>}
        {friendship === 'incoming' && <button type="button" className="button small" onClick={() => void onFriend('accept')}>{t('friends.acceptInvite')}</button>}
        {friendship === 'friends' && <span className="muted">{t('friends.isFriend')} <Link className="button small" to={`/profil/znajomi/porownaj/${playerId}`}>{t('friends.compare')}</Link> <button type="button" className="button small secondary" onClick={() => void onFriend('remove')}>{t('friends.remove')}</button></span>}
        {friendMsg && <span className="form-error" role="alert"> {friendMsg.text}</span>}
      </p>
      {!player.squad && mySquad?.me.can_recruit && (
        <p className="friend-action">
          <button type="button" className="button small" disabled={inviting} onClick={() => void onSquadInvite()}>{t('squad.invite.toSquad')}</button>
          {inviteMsg && <span className={inviteMsg.ok ? 'form-success' : 'form-error'} role={inviteMsg.ok ? 'status' : 'alert'}> {inviteMsg.text}</span>}
        </p>
      )}
      {player.squad && (
        <p className="player-squad">
          {t('squad.player.member')} <SquadEmblem emblem={player.squad.emblem} frame={player.squad.frame} size={24} /> <Link to={`/oddzial/${player.squad.id}`}>{player.squad.display_name}</Link>
          {player.squad.title && <span className="muted"> · {t(`squad.roles.${player.squad.title}`)}</span>}
        </p>
      )}
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
