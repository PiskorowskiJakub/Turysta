import { Link, Navigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../auth/AuthContext';
import { DailyPreview, JobPreview, RankingPreview, TrainingPreview, WorldsPreview } from '../components/GamePreviews';
import { Icon, type IconName } from '../components/icons';

const STEPS: { key: 'train' | 'work' | 'advance'; icon: IconName }[] = [
  { key: 'train', icon: 'skills' },
  { key: 'work', icon: 'job' },
  { key: 'advance', icon: 'rank' },
];

const FEATURES: { key: 'badges' | 'ranking' | 'daily' | 'offline'; icon: IconName }[] = [
  { key: 'badges', icon: 'medal' },
  { key: 'ranking', icon: 'points' },
  { key: 'daily', icon: 'gift' },
  { key: 'offline', icon: 'ticket' },
];

/** Ramka "okna przegladarki" z miniatura ekranu gry; zawartosc tylko do ogladania. */
function Shot({ id, children }: { id: 'training' | 'job' | 'worlds' | 'daily' | 'ranking'; children: React.ReactNode }) {
  const { t } = useTranslation();
  return (
    <figure className={`shot shot-${id}`}>
      <div className="shot-bar" aria-hidden="true"><i /><i /><i /><span>{t(`landing.shots.${id}.url`)}</span></div>
      <div className="shot-body" aria-hidden="true">{children}</div>
      <figcaption>
        <b>{t(`landing.shots.${id}.title`)}</b>
        <span>{t(`landing.shots.${id}.text`)}</span>
      </figcaption>
    </figure>
  );
}

function Cta({ className = '' }: { className?: string }) {
  const { t } = useTranslation();
  return (
    <p className={`cta-row ${className}`}>
      <Link className="button large" to="/rejestracja">{t('landing.register')}</Link>
      <Link className="button large secondary" to="/logowanie">{t('landing.login')}</Link>
    </p>
  );
}

/** Strona glowna dla niezalogowanych: zachetka do gry, kroki rozgrywki i miniatury ekranow. Zalogowanych przenosi do gry. */
export default function HomePage() {
  const { t } = useTranslation();
  const { status, me } = useAuth();
  if (status === 'loading') return <p className="muted">{t('loading')}</p>;
  if (status === 'authed') {
    return <Navigate to={me && !me.user.tutorial_done ? '/samouczek' : '/start/przeglad'} replace />;
  }
  return (
    <div className="landing">
      <section className="hero">
        <p className="hero-kicker">{t('landing.kicker')}</p>
        <h1>{t('landing.title')}</h1>
        <p className="hero-lead">{t('landing.lead')}</p>
        <Cta />
        <p className="hero-note">{t('landing.note')}</p>
      </section>

      <section className="landing-section">
        <h2>{t('landing.howTitle')}</h2>
        <ol className="how">
          {STEPS.map((s, i) => (
            <li key={s.key} className="how-step">
              <span className="how-no">{i + 1}</span>
              <span className="how-icon"><Icon name={s.icon} /></span>
              <h3>{t(`landing.how.${s.key}.title`)}</h3>
              <p>{t(`landing.how.${s.key}.text`)}</p>
            </li>
          ))}
        </ol>
      </section>

      <section className="landing-section">
        <h2>{t('landing.shotsTitle')}</h2>
        <p className="muted">{t('landing.shotsLead')}</p>
        <div className="shots">
          <Shot id="training"><TrainingPreview /></Shot>
          <Shot id="job"><JobPreview /></Shot>
          <Shot id="worlds"><WorldsPreview /></Shot>
          <Shot id="daily"><DailyPreview /></Shot>
          <Shot id="ranking"><RankingPreview /></Shot>
        </div>
      </section>

      <section className="landing-section">
        <h2>{t('landing.featuresTitle')}</h2>
        <ul className="features">
          {FEATURES.map((f) => (
            <li key={f.key}>
              <Icon name={f.icon} />
              <span><b>{t(`landing.features.${f.key}.title`)}</b>{t(`landing.features.${f.key}.text`)}</span>
            </li>
          ))}
        </ul>
      </section>

      <section className="hero hero-end">
        <h2>{t('landing.endTitle')}</h2>
        <p className="hero-lead">{t('landing.endLead')}</p>
        <Cta />
      </section>
    </div>
  );
}
