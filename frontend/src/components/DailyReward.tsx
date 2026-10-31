import { useTranslation } from 'react-i18next';
import type { DailyRewardState } from '../api/types';
import Coins from './Coins';
import { Icon } from './icons';
import Points from './Points';

interface Props {
  daily: DailyRewardState;
  busy: boolean;
  onClaim: () => void;
}

/** Codzienna wedrowka: 7 dni serii, kazdy kolejny wart wiecej; po 7. dniu seria zaczyna sie od nowa. */
export default function DailyReward({ daily, busy, onClaim }: Props) {
  const { t } = useTranslation();
  return (
    <section className="panel">
      <h2>{t('home.daily.title')}</h2>
      <p className="muted">{t('home.daily.lead')}</p>
      <ol className="daily-days">
        {daily.days.map((d) => {
          const state = d.day <= daily.claimed_through ? 'done' : daily.can_claim && d.day === daily.next_day ? 'today' : 'future';
          return (
            <li key={d.day} className={`daily-day ${state}`} aria-current={state === 'today' ? 'step' : undefined}>
              <span className="daily-day-no">{t('home.daily.day', { n: d.day })}</span>
              <span className="daily-day-icon"><Icon name={state === 'done' ? 'check' : 'gift'} /></span>
              <span className="daily-day-reward">
                <Coins cents={d.coins_cents} />
                <small>+<Points value={d.points} /> {t('wallet.pointsShort')}</small>
              </span>
            </li>
          );
        })}
      </ol>
      <div className="daily-actions">
        {daily.can_claim ? (
          <button type="button" className="button" disabled={busy} onClick={onClaim}>{t('home.daily.claim')}</button>
        ) : (
          <p className="form-success">{t('home.daily.claimed')}</p>
        )}
        <small className="muted">{t('home.daily.rule')}</small>
      </div>
    </section>
  );
}
