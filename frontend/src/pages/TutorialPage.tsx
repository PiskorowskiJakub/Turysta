import { useEffect, useState } from 'react';
import { Navigate, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { Avatar, Gender } from '../api/types';
import { useAuth } from '../auth/AuthContext';

const GENDERS: Gender[] = ['f', 'm', 'x'];

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

  const visible = avatars.filter((a) => a.gender === gender);

  return (
    <div className="card">
      <h1>{t('tutorial.title')}</h1>
      {error && <p className="form-error" role="alert">{error}</p>}

      {step === 0 && (
        <>
          <p>{t('tutorial.story', { name: me?.user.character_name ?? '' })}</p>
          <button className="button" type="button" onClick={() => setStep(1)}>{t('tutorial.next')}</button>
        </>
      )}

      {step === 1 && (
        <>
          <h2>{t('tutorial.genderTitle')}</h2>
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
          <button className="button" type="button" disabled={!gender} onClick={() => setStep(2)}>{t('tutorial.next')}</button>
        </>
      )}

      {step === 2 && (
        <>
          <h2>{t('tutorial.avatarTitle')}</h2>
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
          <p className="actions">
            <button className="button secondary" type="button" onClick={() => setStep(1)}>{t('tutorial.back')}</button>
            <button className="button" type="button" disabled={avatarId === null || busy} onClick={onFinish}>{t('tutorial.finish')}</button>
          </p>
        </>
      )}
    </div>
  );
}
