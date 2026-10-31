import { useEffect, useState, type ReactNode } from 'react';
import { Navigate, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { Avatar, Gender } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { JobPreview, MenuPreview, TrainingPreview, WalletPreview, WorldsPreview } from '../components/GamePreviews';
import PlayerAvatar from '../components/PlayerAvatar';

const GENDERS: Gender[] = ['f', 'm', 'x'];

type StepId = 'map' | 'sold' | 'gender' | 'avatar' | 'skills' | 'jobs' | 'worlds' | 'road';
const STEPS: StepId[] = ['map', 'sold', 'gender', 'avatar', 'skills', 'jobs', 'worlds', 'road'];

/** Liczba akapitow historii (p1..pN) i podpowiedzi o interfejsie (tip1..tipN) w kazdym kroku opowiesci. */
const TEXT: Record<'map' | 'sold' | 'skills' | 'jobs' | 'worlds' | 'road', { p: number; tip: number }> = {
  map: { p: 2, tip: 0 },
  sold: { p: 1, tip: 2 },
  skills: { p: 2, tip: 2 },
  jobs: { p: 1, tip: 2 },
  worlds: { p: 2, tip: 2 },
  road: { p: 2, tip: 1 },
};

export default function TutorialPage() {
  const { t } = useTranslation();
  const { me, saveProfile } = useAuth();
  const navigate = useNavigate();

  const [step, setStep] = useState(0);
  const [gender, setGender] = useState<Gender | null>(null);
  const [avatars, setAvatars] = useState<Avatar[]>([]);
  const [avatarId, setAvatarId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    api<{ avatars: Avatar[] }>('GET', '/avatars')
      .then((r) => setAvatars(r.avatars))
      .catch(() => setError(t('errors.network')));
  }, [t]);

  if (me?.user.tutorial_done) return <Navigate to="/" replace />;

  const id = STEPS[step];
  const name = me?.user.character_name ?? '';
  const chosen = avatars.find((a) => a.id === avatarId);
  const visible = avatars.filter((a) => a.gender === gender);
  const isLast = step === STEPS.length - 1;

  const onFinish = async () => {
    if (!gender || avatarId === null) return;
    setBusy(true);
    setError(null);
    try {
      await saveProfile(gender, avatarId);
      navigate('/', { replace: true });
    } catch (err) {
      setError(err instanceof ApiError ? t(`errors.${err.code}`, { defaultValue: err.message }) : t('errors.network'));
    } finally {
      setBusy(false);
    }
  };

  const nav = (canNext: boolean): ReactNode => (
    <p className="actions tp-actions">
      {step > 0 && <button className="button secondary" type="button" onClick={() => setStep(step - 1)}>{t('tutorial.back')}</button>}
      {isLast ? (
        <button className="button" type="button" disabled={busy || avatarId === null} onClick={() => void onFinish()}>{t('tutorial.finish')}</button>
      ) : (
        <button className="button" type="button" disabled={!canNext} onClick={() => setStep(step + 1)}>{t('tutorial.next')}</button>
      )}
    </p>
  );

  const story = (key: keyof typeof TEXT, preview: ReactNode) => {
    const cfg = TEXT[key];
    return (
      <>
        <h2>{t(`tutorial.${key}.title`)}</h2>
        <div className="tp-story">
          {chosen && step > STEPS.indexOf('avatar') && <PlayerAvatar src={chosen.image_url} name={name} size={84} />}
          <div className="tp-text">
            {Array.from({ length: cfg.p }, (_, i) => <p key={i}>{t(`tutorial.${key}.p${i + 1}`, { name })}</p>)}
          </div>
        </div>
        {cfg.tip > 0 && (
          <aside className="tp-tip">
            <b>{t('tutorial.inGame')}</b>
            {Array.from({ length: cfg.tip }, (_, i) => <p key={i}>{t(`tutorial.${key}.tip${i + 1}`)}</p>)}
            {preview && <div className="tp-preview">{preview}</div>}
          </aside>
        )}
        {nav(true)}
      </>
    );
  };

  return (
    <div className="card tutorial">
      <p className="tp-progress">{t('tutorial.stepOf', { n: step + 1, total: STEPS.length })}</p>
      <h1>{t('tutorial.title')}</h1>
      {error && <p className="form-error" role="alert">{error}</p>}

      {id === 'map' && story('map', null)}
      {id === 'sold' && story('sold', <WalletPreview />)}

      {id === 'gender' && (
        <>
          <h2>{t('tutorial.genderTitle')}</h2>
          <p>{t('tutorial.genderLead')}</p>
          <div className="choices">
            {GENDERS.map((g) => (
              <button
                key={g}
                type="button"
                className={'choice' + (gender === g ? ' selected' : '')}
                onClick={() => {
                  setGender(g);
                  setAvatarId(null);
                }}
              >
                {t(`gender.${g}`)}
              </button>
            ))}
          </div>
          <p className="field-hint">{t('tutorial.genderHint')}</p>
          {nav(gender !== null)}
        </>
      )}

      {id === 'avatar' && (
        <>
          <h2>{t('tutorial.avatarTitle')}</h2>
          <p>{t('tutorial.avatarLead')}</p>
          <div className="choices">
            {visible.map((a) => (
              <button
                key={a.id}
                type="button"
                className={'choice avatar' + (avatarId === a.id ? ' selected' : '')}
                onClick={() => setAvatarId(a.id)}
              >
                <img src={a.image_url} alt="" onError={(e) => (e.currentTarget.style.display = 'none')} />
                <span>{a.name}</span>
              </button>
            ))}
          </div>
          {nav(avatarId !== null)}
        </>
      )}

      {id === 'skills' && story('skills', <><MenuPreview active={['skills']} /><TrainingPreview /></>)}
      {id === 'jobs' && story('jobs', <><MenuPreview active={['jobs']} /><JobPreview /></>)}
      {id === 'worlds' && story('worlds', <><MenuPreview active={['skills']} /><WorldsPreview /></>)}
      {id === 'road' && story('road', <MenuPreview active={['home', 'badges', 'ranking']} />)}
    </div>
  );
}
