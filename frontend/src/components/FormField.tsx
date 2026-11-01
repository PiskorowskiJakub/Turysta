import { useState, type InputHTMLAttributes } from 'react';
import { useTranslation } from 'react-i18next';
import { Icon } from './icons';

interface Props extends InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
}

/** Pole formularza; przy polu hasla pokazuje przycisk oka, ktory odslania wpisane haslo. */
export default function FormField({ label, error, id, ...input }: Props) {
  const { t } = useTranslation();
  const [shown, setShown] = useState(false);
  const fieldId = id ?? input.name ?? label;
  const isPassword = input.type === 'password';

  return (
    <div className="field">
      <label htmlFor={fieldId}>{label}</label>
      {isPassword ? (
        <div className="input-wrap">
          <input id={fieldId} aria-invalid={error ? true : undefined} {...input} type={shown ? 'text' : 'password'} />
          <button
            type="button"
            className="reveal"
            onClick={() => setShown((s) => !s)}
            aria-label={shown ? t('field.hidePassword') : t('field.showPassword')}
            aria-pressed={shown}
            title={shown ? t('field.hidePassword') : t('field.showPassword')}
          >
            <Icon name={shown ? 'eyeOff' : 'eye'} />
          </button>
        </div>
      ) : (
        <input id={fieldId} aria-invalid={error ? true : undefined} {...input} />
      )}
      {error && <p className="field-error">{error}</p>}
    </div>
  );
}
