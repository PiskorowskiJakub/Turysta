import { Navigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../auth/AuthContext';
import { formatCoins } from '../format';

export default function ProfilePage() {
  const { t } = useTranslation();
  const { me } = useAuth();

  if (!me) return null;
  if (!me.user.tutorial_done) return <Navigate to="/samouczek" replace />;

  return (
    <div className="card">
      <h1>{me.user.character_name}</h1>
      {!me.user.email_verified && <p className="form-notice">{t('profile.verifyEmail')}</p>}

      <h2>{t('profile.wallet')}</h2>
      <ul className="stats">
        <li><span>{t('wallet.coins')}</span><strong>{formatCoins(me.wallet.coins_cents)}</strong></li>
        <li><span>{t('wallet.tickets')}</span><strong>{me.wallet.tickets}</strong></li>
        <li><span>{t('wallet.points')}</span><strong>{me.wallet.points}</strong></li>
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
    </div>
  );
}
