import { useTranslation } from 'react-i18next';
import { useAuth } from '../auth/AuthContext';
import { formatCoins } from '../format';

export default function GameHeader() {
  const { t } = useTranslation();
  const { status, me } = useAuth();

  if (status === 'authed' && me) {
    return (
      <div className="game-header">
        <strong>{me.user.character_name}</strong>
        <span>{t('wallet.coins')}: {formatCoins(me.wallet.coins_cents)}</span>
        <span>{t('wallet.tickets')}: {me.wallet.tickets}</span>
        <span>{t('wallet.points')}: {me.wallet.points}</span>
      </div>
    );
  }
  return <div className="game-header">{t('tagline')}</div>;
}
