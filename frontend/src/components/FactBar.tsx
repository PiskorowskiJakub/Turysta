import { useEffect, useRef, useState } from 'react';

/**
 * Pasek z ciekawostka w gornej belce. Dluzsza tresc jest przycieta (2 linie na komputerze, 1 na telefonie),
 * a klikniecie lub dotkniecie rozwija ja w calosci.
 */
export default function FactBar({ label, text }: { label: string; text: string }) {
  const [open, setOpen] = useState(false);
  const [clipped, setClipped] = useState(false);
  const innerRef = useRef<HTMLSpanElement>(null);

  useEffect(() => setOpen(false), [text]);

  useEffect(() => {
    if (open) return;
    const measure = () => {
      const inner = innerRef.current;
      if (inner) setClipped(inner.scrollHeight > inner.clientHeight + 1);
    };
    measure();
    window.addEventListener('resize', measure);
    return () => window.removeEventListener('resize', measure);
  }, [text, open]);

  return (
    <button type="button" className={'fact' + (open ? ' open' : '')} aria-expanded={open} onClick={() => setOpen((v) => !v)}>
      <b>{label}</b>
      <span className="fact-text">
        <span className="fact-inner" ref={innerRef}>{text}</span>
      </span>
      {clipped && !open && <i aria-hidden="true">▾</i>}
    </button>
  );
}
