import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { ActiveJob, Avatar, ClaimResponse, HomeResponse, TrainingItem } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { useBadges } from '../hooks/useBadges';
import { usePolling } from '../hooks/usePolling';
import { useServerNow } from '../hooks/useServerNow';
import { formatDuration } from '../format';
import AnnouncementList from './AnnouncementList';
import BadgeBook from './BadgeBook';
import Coins from './Coins';
import DailyReward from './DailyReward';
import FriendActivity from './FriendActivity';
import GiftQuick from './GiftQuick';
import { Icon } from './icons';
import Points from './Points';
import ProfileCard from './ProfileCard';

/** Strona glowna zalogowanego gracza ("Plecak"): ogloszenia, codzienna nagroda, dziennik wyprawy, skrot postaci i ranking. */
export default function Dashboard() {
  const { t } = useTranslation();
  const { me, refresh } = useAuth();
  const navigate = useNavigate();
  const badges = useBadges();
  const now = useServerNow(me?.server_time ?? null);

  const [home, setHome] = useState<HomeResponse | null>(null);
  const [avatars, setAvatars] = useState<Avatar[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [claiming, setClaiming] = useState(false);
  const [copied, setCopied] = useState<'link' | 'code' | null>(null);
  const [search, setSearch] = useState('');

  const load = useCallback(async () => {
    try {
      setHome(await api<HomeResponse>('GET', '/home'));
      setError(null);
    } catch (e) {
      setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
    }
  }, [t]);

  useEffect(() => {
    void load();
    api<{ avatars: Avatar[] }>('GET', '/avatars').then((r) => setAvatars(r.avatars)).catch(() => setAvatars([]));
  }, [load]);
  usePolling(() => void load(), 30000);

  if (!me) return null;

  const avatar = avatars.find((a) => a.id === me.user.avatar_id);
  const training = me.active.training as TrainingItem | null;
  const job = me.active.job as ActiveJob | null;
  const left = (iso: string | null) => (iso ? formatDuration(Math.max(0, (Date.parse(iso) - now) / 1000)) : '');
  const skillLevels = me.skills.reduce((sum, s) => sum + s.level, 0);

  const claim = async () => {
    setClaiming(true);
    try {
      const r = await api<ClaimResponse>('POST', '/daily-reward/claim');
      setHome((h) => (h ? { ...h, daily: r.daily } : h));
      await refresh();
      setError(null);
    } catch (e) {
      setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
      void load();
    } finally {
      setClaiming(false);
    }
  };

  const inviteLink = home?.referral_code ? `${window.location.origin}/rejestracja?ref=${home.referral_code}` : '';
  const copy = async (what: 'link' | 'code') => {
    try {
      await navigator.clipboard.writeText(what === 'link' ? inviteLink : (home?.referral_code ?? ''));
      setCopied(what);
      window.setTimeout(() => setCopied(null), 2000);
    } catch {
      setCopied(null);
    }
  };

  const onSearch = (e: FormEvent) => {
    e.preventDefault();
    const q = search.trim();
    navigate(q ? `/ranking?q=${encodeURIComponent(q)}` : '/ranking');
  };

  const trainingName = training
    ? training.kind === 'chapter'
      ? training.chapter?.name ?? ''
      : t(`skills.${training.skill?.code ?? ''}`, { defaultValue: training.skill?.name ?? '' })
    : '';
  const myRowShown = home?.top.some((r) => r.me) ?? false;

  return (
    <div className="home-grid">
      <div className="home-main">
        <section className="panel home-welcome">
          <h1>{t('home.welcome', { name: me.user.character_name })}</h1>
          <p>{t('home.leadAuthed')}</p>
        </section>

        {error && <p className="form-error" role="alert">{error}</p>}

        <section className="panel">
          <h2>{t('home.board.title')}</h2>
          {home && <AnnouncementList items={home.announcements} />}
        </section>

        <section className="panel season">
          <h2>{t('home.season.title')}</h2>
          <p className="muted">{t('home.season.soon')}</p>
        </section>

        {home && <DailyReward daily={home.daily} busy={claiming} onClaim={() => void claim()} />}

        <FriendActivity />

        <GiftQuick />

        <ProfileCard
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
        {badges.ready ? <BadgeBook data={badges} /> : null}

        {home?.referral_code && (
          <section className="panel">
            <h2>{t('home.invite.title')}</h2>
            <p className="muted">{t('home.invite.lead')}</p>
            <div className="invite-row">
              <input readOnly value={inviteLink} aria-label={t('home.invite.title')} onFocus={(e) => e.currentTarget.select()} />
              <button type="button" className="button small" onClick={() => void copy('link')}>{copied === 'link' ? t('home.invite.copied') : t('home.invite.copy')}</button>
            </div>
            <p className="muted invite-code-label">{t('home.invite.codeLead')}</p>
            <div className="invite-row">
              <input readOnly value={home.referral_code} aria-label={t('home.invite.codeLabel')} onFocus={(e) => e.currentTarget.select()} />
              <button type="button" className="button small" onClick={() => void copy('code')}>{copied === 'code' ? t('home.invite.copied') : t('home.invite.copyCode')}</button>
            </div>
            <p className="muted">{t('home.invite.badgeHint')}</p>
            {home.referrals && (
              <dl className="admin-tiles invite-stats">
                <div className="admin-tile"><dt>{t('home.invite.registered')}</dt><dd>{home.referrals.registered}</dd></div>
                <div className="admin-tile"><dt>{t('home.invite.active', { n: home.referrals.active_days_required })}</dt><dd>{home.referrals.active}</dd></div>
              </dl>
            )}
          </section>
        )}
      </div>

      <aside className="home-side">
        <section className="panel">
          <h2>{t('home.log.title')}</h2>
          <ul className="log-list">
            <li>
              <i className={'log-dot' + (training ? ' on' : '')} />
              <span>
                <Link to="/umiejetnosci">{t('home.log.training')}</Link>
                <small>{training ? `${trainingName} → ${training.target_level} · ${left(training.ends_at)}` : t('home.log.none')}</small>
              </span>
            </li>
            <li>
              <i className={'log-dot' + (job ? ' on' : '')} />
              <span>
                <Link to="/praca">{t('home.log.job')}</Link>
                <small>{job ? `${job.job.name} · ${left(job.ends_at)}` : t('home.log.free')}</small>
              </span>
            </li>
            <li>
              <i className={'log-dot' + (home?.daily.can_claim ? ' warn' : ' on')} />
              <span>
                <span>{t('home.log.daily')}</span>
                <small>{home?.daily.can_claim ? t('home.log.dailyReady') : t('home.log.dailyDone')}</small>
              </span>
            </li>
            <li>
              <i className={'log-dot' + (me.notifications_unread > 0 ? ' warn' : '')} />
              <span>
                <Link to="/start/powiadomienia">{t('home.log.notifications')}</Link>
                <small>{me.notifications_unread > 0 ? t('home.log.unread', { n: me.notifications_unread }) : t('home.log.none')}</small>
              </span>
            </li>
          </ul>
          <ul className="log-wallet">
            <li><Icon name="coins" /><Coins cents={me.wallet.coins_cents} /></li>
            <li><Icon name="points" /><Points value={me.wallet.points} /></li>
          </ul>
        </section>

        <section className="panel">
          <h2>{t('home.search.title')}</h2>
          <form className="search-row" onSubmit={onSearch}>
            <input value={search} onChange={(e) => setSearch(e.target.value)} maxLength={40} placeholder={t('home.search.placeholder')} aria-label={t('home.search.title')} />
            <button className="button small" type="submit">{t('home.search.go')}</button>
          </form>
        </section>

        <section className="panel">
          <h2>{t('home.top.title')}</h2>
          <ol className="top-list">
            {home?.top.map((r) => (
              <li key={r.id} className={r.me ? 'me' : ''}>
                <b>{r.rank}.</b>
                <Link to={r.me ? '/profil' : `/gracz/${r.id}`}>{r.name}</Link>
                <Points value={r.points} />
              </li>
            ))}
            {home && !myRowShown && (
              <li className="me gap">
                <b>{me.stats.rank}.</b>
                <Link to="/profil">{me.user.character_name}</Link>
                <Points value={me.wallet.points} />
              </li>
            )}
          </ol>
          <p className="top-more"><Link to="/ranking">{t('home.top.all')} →</Link></p>
        </section>
      </aside>
    </div>
  );
}
