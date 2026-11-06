import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import type { SquadProfile } from '../../api/types';
import SquadEmblem from './SquadEmblem';
import { SquadBadgeSquares } from './SquadBadgesPanel';

/** Ustawienia wygladu oddzialu z Izby pamieci: emblemat, ramka i gablota wyroznionych odznak. */
export default function SquadAppearancePanel({ squad, busy, onSave }: { squad: SquadProfile; busy: boolean; onSave: (body: Record<string, unknown>) => Promise<void> }) {
  const { t } = useTranslation();
  const mem = squad.memory;
  const [emblem, setEmblem] = useState<string | null>(mem.emblem);
  const [frame, setFrame] = useState<string | null>(mem.frame);
  const [showcase, setShowcase] = useState<string[]>(mem.showcase);
  const earned = squad.badges.filter((b) => b.tier > 0);
  const dirty = emblem !== mem.emblem || frame !== mem.frame || showcase.join() !== mem.showcase.join();

  const toggle = (code: string) =>
    setShowcase((cur) => (cur.includes(code) ? cur.filter((c) => c !== code) : cur.length < mem.slots ? [...cur, code] : cur));

  const save = () => void onSave({ emblem, frame, showcase });

  return (
    <section className="panel">
      <h2>{t('squad.memory.title')}</h2>
      <p className="muted">{t('squad.memory.intro', { level: mem.level })}</p>
      {mem.level === 0 && <p className="form-notice">{t('squad.memory.needRoom')}</p>}

      <h3 className="badge-sub">{t('squad.memory.emblem')}</h3>
      <div className="emblem-grid">
        <button type="button" className={'emblem-opt' + (emblem === null ? ' selected' : '')} onClick={() => setEmblem(null)} aria-pressed={emblem === null}>
          <span className="emblem-none">—</span>
          <small>{t('squad.memory.none')}</small>
        </button>
        {mem.emblems.map((e) => (
          <button
            key={e.code}
            type="button"
            className={'emblem-opt' + (emblem === e.code ? ' selected' : '') + (e.unlocked ? '' : ' locked')}
            disabled={!e.unlocked}
            onClick={() => setEmblem(e.code)}
            aria-pressed={emblem === e.code}
          >
            <SquadEmblem emblem={e.code} frame={frame} size={52} />
            <small>{e.unlocked ? t(`squad.memory.emblemNames.${e.code}`) : t('squad.memory.unlockAt', { n: e.min_level })}</small>
          </button>
        ))}
      </div>

      <h3 className="badge-sub">{t('squad.memory.frame')}</h3>
      <div className="emblem-grid">
        <button type="button" className={'emblem-opt' + (frame === null ? ' selected' : '')} onClick={() => setFrame(null)} aria-pressed={frame === null}>
          <span className="emblem-none">—</span>
          <small>{t('squad.memory.none')}</small>
        </button>
        {mem.frames.map((f) => (
          <button
            key={f.code}
            type="button"
            className={'emblem-opt' + (frame === f.code ? ' selected' : '') + (f.unlocked ? '' : ' locked')}
            disabled={!f.unlocked}
            onClick={() => setFrame(f.code)}
            aria-pressed={frame === f.code}
          >
            <SquadEmblem emblem={emblem ?? mem.emblems[0].code} frame={f.code} size={52} />
            <small>{f.unlocked ? t(`training.tier.${f.code}`) : t('squad.memory.unlockAt', { n: f.min_level })}</small>
          </button>
        ))}
      </div>

      <h3 className="badge-sub">{t('squad.memory.showcase', { used: showcase.length, max: mem.slots })}</h3>
      <p className="muted">
        {t('squad.memory.showcaseHint')}
        {mem.next_slot_level !== null && ` ${t('squad.memory.nextSlot', { n: mem.next_slot_level })}`}
      </p>
      {mem.slots === 0 ? null : earned.length === 0 ? (
        <p className="muted">{t('squad.memory.noBadges')}</p>
      ) : (
        <SquadBadgeSquares badges={earned} pick={{ selected: showcase, onToggle: toggle, full: showcase.length >= mem.slots }} />
      )}

      <div className="actions">
        <button type="button" className="button" disabled={busy || !dirty} onClick={save}>{t('squad.settings.save')}</button>
      </div>
    </section>
  );
}
