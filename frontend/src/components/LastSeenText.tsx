import { useTranslation } from 'react-i18next';
import { lastSeenAgo } from '../format';

/** "Ostatnio aktywny": pelne minuty, godziny albo dni temu (zaokraglone w dol); bez sladu aktywnosci - zwykle "offline". */
export default function LastSeenText({ iso }: { iso: string | null | undefined }) {
  const { t } = useTranslation();
  if (!iso) return <>{t('friends.offline')}</>;
  const ago = lastSeenAgo(iso);
  return <>{t(`lastSeen.${ago.unit}`, { count: ago.count })}</>;
}
