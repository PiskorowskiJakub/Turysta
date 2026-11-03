import { useState } from 'react';
import { Icon } from './icons';

/** Ikona znaku zapytania w prawym gornym rogu sekcji: po najechaniu, fokusie lub kliknieciu pokazuje wyjasnienie. */
export default function InfoHint({ text, label, wide = false }: { text: string; label: string; wide?: boolean }) {
  const [open, setOpen] = useState(false);
  return (
    <span className="info-hint">
      <button
        type="button"
        className="info-hint-btn"
        aria-label={label}
        aria-expanded={open}
        onClick={() => setOpen((v) => !v)}
        onBlur={() => setOpen(false)}
      >
        <Icon name="help" />
      </button>
      <span role="tooltip" className={'info-hint-text' + (wide ? ' wide' : '') + (open ? ' open' : '')}>{text}</span>
    </span>
  );
}
