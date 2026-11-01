import { useEffect, useState, type FormEvent } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { Avatar, Gender } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import FormField from '../components/FormField';
import { formatDate } from '../format';

const GENDERS: Gender[] = ['f', 'm', 'x'];

interface Result {
  ok?: string;
  error?: string;
  fields: Record<string, string>;
}

const empty: Result = { fields: {} };

export default function EditProfilePage() {
  const { t } = useTranslation();
  const { me, updateAccountProfile, changeEmail, changePassword, deleteAccount } = useAuth();
  const navigate = useNavigate();

  const [avatars, setAvatars] = useState<Avatar[]>([]);
  const [gender, setGender] = useState<Gender | null>(me?.user.gender ?? null);
  const [avatarId, setAvatarId] = useState<number | null>(me?.user.avatar_id ?? null);
  const [character, setCharacter] = useState<Result>(empty);

  const [newEmail, setNewEmail] = useState('');
  const [emailPassword, setEmailPassword] = useState('');
  const [email, setEmail] = useState<Result>(empty);

  const [oldPassword, setOldPassword] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [pass, setPass] = useState<Result>(empty);

  const [deletePassword, setDeletePassword] = useState('');
  const [deleteSure, setDeleteSure] = useState(false);
  const [del, setDel] = useState<Result>(empty);

  const [busy, setBusy] = useState(false);

  useEffect(() => {
    api<{ avatars: Avatar[] }>('GET', '/avatars')
      .then((r) => setAvatars(r.avatars))
      .catch(() => setAvatars([]));
  }, []);

  if (!me) return null;
  if (!me.user.tutorial_done) return <Navigate to="/samouczek" replace />;

  const lockedUntil = me.user.gender_change_available_at;
  const genderLocked = lockedUntil !== null;
  const visible = avatars.filter((a) => a.gender === gender);

  const fail = (err: unknown): Result =>
    err instanceof ApiError
      ? { error: t(`errors.${err.code}`, { defaultValue: err.message }), fields: err.fields }
      : { error: t('errors.network'), fields: {} };

  const run = async (set: (r: Result) => void, action: () => Promise<void>, okMessage: string, after?: () => void) => {
    setBusy(true);
    set(empty);
    try {
      await action();
      set({ ok: okMessage, fields: {} });
      after?.();
    } catch (err) {
      set(fail(err));
    } finally {
      setBusy(false);
    }
  };

  const onCharacter = (e: FormEvent) => {
    e.preventDefault();
    if (!gender || avatarId === null) return;
    void run(setCharacter, () => updateAccountProfile(gender, avatarId), t('editProfile.savedCharacter'));
  };

  const onEmail = (e: FormEvent) => {
    e.preventDefault();
    void run(setEmail, () => changeEmail(newEmail, emailPassword), t('editProfile.savedEmail'), () => {
      setNewEmail('');
      setEmailPassword('');
    });
  };

  const onPassword = (e: FormEvent) => {
    e.preventDefault();
    void run(setPass, () => changePassword(oldPassword, password, passwordConfirm), t('editProfile.savedPassword'), () => {
      setOldPassword('');
      setPassword('');
      setPasswordConfirm('');
    });
  };

  const onDelete = async (e: FormEvent) => {
    e.preventDefault();
    if (!deleteSure || !deletePassword) return;
    setBusy(true);
    setDel(empty);
    try {
      await deleteAccount(deletePassword);
      navigate('/', { replace: true });
    } catch (err) {
      setDel(fail(err));
      setBusy(false);
    }
  };

  const messages = (r: Result) => (
    <>
      {r.error && <p className="form-error" role="alert">{r.error}</p>}
      {r.ok && <p className="form-notice" role="status">{r.ok}</p>}
    </>
  );

  return (
    <>
      <p className="player-back"><Link to="/profil">← {t('editProfile.back')}</Link></p>
      <h1>{t('editProfile.title')}</h1>

      <form className="panel edit-section" onSubmit={onCharacter} noValidate>
        <h2>{t('editProfile.characterSection')}</h2>
        {messages(character)}
        <div className="field">
          <label htmlFor="character_name">{t('editProfile.characterName')}</label>
          <input id="character_name" value={me.user.character_name} disabled readOnly />
          <p className="field-hint">{t('editProfile.nameLocked')}</p>
        </div>

        <div className="field">
          <span id="gender-label">{t('editProfile.gender')}</span>
          <div className="choices" role="group" aria-labelledby="gender-label">
            {GENDERS.map((g) => (
              <button
                key={g}
                type="button"
                className={'choice' + (gender === g ? ' selected' : '')}
                disabled={genderLocked && g !== me.user.gender}
                onClick={() => {
                  if (g !== gender) {
                    setGender(g);
                    setAvatarId(null);
                  }
                }}
              >
                {t(`gender.${g}`)}
              </button>
            ))}
          </div>
          <p className="field-hint">
            {genderLocked && lockedUntil ? t('editProfile.genderLocked', { date: formatDate(lockedUntil) }) : t('editProfile.genderRule')}
          </p>
          {character.fields.gender && <p className="field-error">{character.fields.gender}</p>}
        </div>

        <div className="field">
          <span id="avatar-label">{t('editProfile.avatar')}</span>
          <div className="choices" role="group" aria-labelledby="avatar-label">
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
          {character.fields.avatar_id && <p className="field-error">{character.fields.avatar_id}</p>}
        </div>

        <button className="button" type="submit" disabled={busy || !gender || avatarId === null}>{t('editProfile.saveCharacter')}</button>
      </form>

      <form className="panel edit-section" onSubmit={onEmail} noValidate>
        <h2>{t('editProfile.emailSection')}</h2>
        <p className="muted">{t('editProfile.currentEmail', { email: me.user.email })}</p>
        {messages(email)}
        <FormField label={t('editProfile.newEmail')} name="new_email" type="email" value={newEmail} onChange={(e) => setNewEmail(e.target.value)} error={email.fields.email} autoComplete="email" required />
        <FormField label={t('editProfile.currentPassword')} name="email_current_password" type="password" value={emailPassword} onChange={(e) => setEmailPassword(e.target.value)} error={email.fields.current_password} autoComplete="current-password" required />
        <p className="field-hint">{t('editProfile.emailInfo')}</p>
        <button className="button" type="submit" disabled={busy || !newEmail || !emailPassword}>{t('editProfile.saveEmail')}</button>
      </form>

      <form className="panel edit-section" onSubmit={onPassword} noValidate>
        <h2>{t('editProfile.passwordSection')}</h2>
        {messages(pass)}
        <FormField label={t('editProfile.currentPassword')} name="current_password" type="password" value={oldPassword} onChange={(e) => setOldPassword(e.target.value)} error={pass.fields.current_password} autoComplete="current-password" required />
        <FormField label={t('editProfile.newPassword')} name="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} error={pass.fields.password} autoComplete="new-password" required />
        <FormField label={t('editProfile.newPasswordConfirm')} name="password_confirm" type="password" value={passwordConfirm} onChange={(e) => setPasswordConfirm(e.target.value)} error={pass.fields.password_confirm} autoComplete="new-password" required />
        <p className="field-hint">{t('editProfile.passwordInfo')}</p>
        <button className="button" type="submit" disabled={busy || !oldPassword || !password || !passwordConfirm}>{t('editProfile.savePassword')}</button>
      </form>

      <form className="panel edit-section danger-zone" onSubmit={(e) => void onDelete(e)} noValidate>
        <h2>{t('editProfile.deleteSection')}</h2>
        <p>{t('editProfile.deleteInfo')}</p>
        {messages(del)}
        <FormField label={t('editProfile.currentPassword')} name="delete_current_password" type="password" value={deletePassword} onChange={(e) => setDeletePassword(e.target.value)} error={del.fields.current_password} autoComplete="current-password" required />
        <label className="check">
          <input type="checkbox" checked={deleteSure} onChange={(e) => setDeleteSure(e.target.checked)} />
          <span>{t('editProfile.deleteSure')}</span>
        </label>
        <button className="button danger" type="submit" disabled={busy || !deleteSure || !deletePassword}>{t('editProfile.deleteButton')}</button>
      </form>
    </>
  );
}
