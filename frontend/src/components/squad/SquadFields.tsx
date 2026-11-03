import { useTranslation } from 'react-i18next';
import type { SquadJoinMode, SquadMeta } from '../../api/types';

export interface SquadDraft {
  name: string;
  specialization: string;
  join_mode: SquadJoinMode;
}

interface Props {
  meta: SquadMeta;
  value: SquadDraft;
  onChange: (next: SquadDraft) => void;
  errors?: Record<string, string>;
  disabled?: boolean;
  /** Prefiks identyfikatorow pol, gdy na stronie jest kilka formularzy. */
  idPrefix: string;
  nameHint?: string;
  specHint?: string;
}

const GROUPS = ['movement', 'water', 'travel', 'sightseeing', 'other', 'general'];
const MODES: SquadJoinMode[] = ['open', 'request', 'invite'];

/** Wspolne pola oddzialu: nazwa wlasna, specjalizacja (jedyna kategoria) i tryb przyjmowania. */
export default function SquadFields({ meta, value, onChange, errors = {}, disabled, idPrefix, nameHint, specHint }: Props) {
  const { t } = useTranslation();
  return (
    <>
      <div className="field">
        <label htmlFor={`${idPrefix}-name`}>{t('squad.fields.name')}</label>
        <input
          id={`${idPrefix}-name`}
          value={value.name}
          maxLength={meta.limits.name_max}
          disabled={disabled}
          autoComplete="off"
          placeholder={t('squad.fields.namePlaceholder')}
          aria-invalid={errors.name ? true : undefined}
          onChange={(e) => onChange({ ...value, name: e.target.value })}
        />
        {errors.name && <p className="field-error">{errors.name}</p>}
        <p className="field-hint">{nameHint ?? t('squad.fields.nameHint', { min: meta.limits.name_min, max: meta.limits.name_max })}</p>
      </div>

      <div className="field">
        <label htmlFor={`${idPrefix}-spec`}>{t('squad.fields.specialization')}</label>
        <select
          id={`${idPrefix}-spec`}
          value={value.specialization}
          disabled={disabled}
          aria-invalid={errors.specialization ? true : undefined}
          onChange={(e) => onChange({ ...value, specialization: e.target.value })}
        >
          {GROUPS.map((g) => {
            const items = meta.specializations.filter((s) => s.group === g);
            if (items.length === 0) return null;
            return (
              <optgroup key={g} label={t(`squad.groups.${g}`)}>
                {items.map((s) => (
                  <option key={s.code} value={s.code}>{s.label}</option>
                ))}
              </optgroup>
            );
          })}
        </select>
        {errors.specialization && <p className="field-error">{errors.specialization}</p>}
        <p className="field-hint">{specHint ?? t('squad.fields.specializationHint')}</p>
      </div>

      <div className="field">
        <label htmlFor={`${idPrefix}-mode`}>{t('squad.fields.joinMode')}</label>
        <select
          id={`${idPrefix}-mode`}
          value={value.join_mode}
          disabled={disabled}
          aria-invalid={errors.join_mode ? true : undefined}
          onChange={(e) => onChange({ ...value, join_mode: e.target.value as SquadJoinMode })}
        >
          {MODES.map((m) => (
            <option key={m} value={m}>{t(`squad.modes.${m}`)}</option>
          ))}
        </select>
        {errors.join_mode && <p className="field-error">{errors.join_mode}</p>}
        <p className="field-hint">{t(`squad.modeHints.${value.join_mode}`)}</p>
      </div>
    </>
  );
}
