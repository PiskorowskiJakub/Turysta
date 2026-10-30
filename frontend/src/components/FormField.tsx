import type { InputHTMLAttributes } from 'react';

interface Props extends InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
}

export default function FormField({ label, error, id, ...input }: Props) {
  const fieldId = id ?? input.name ?? label;
  return (
    <div className="field">
      <label htmlFor={fieldId}>{label}</label>
      <input id={fieldId} aria-invalid={error ? true : undefined} {...input} />
      {error && <p className="field-error">{error}</p>}
    </div>
  );
}
