import { useEffect, useRef, useState } from 'react';

/**
 * Aktualny czas serwera (ms), tykajacy co sekunde po stronie przegladarki.
 * Roznice miedzy zegarem serwera a klienta liczymy tylko raz po kazdej odpowiedzi serwera;
 * pozniej licznik tyka lokalnie, a kolejne odczyty z serwera go weryfikuja i korygują.
 */
export function useServerNow(serverTime: string | null): number {
  const offset = useRef(0);
  const lastSync = useRef<string | null>(null);
  const [now, setNow] = useState(() => Date.now());

  if (serverTime && serverTime !== lastSync.current) {
    lastSync.current = serverTime;
    offset.current = Date.parse(serverTime) - Date.now();
  }

  useEffect(() => {
    const id = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(id);
  }, []);

  return now + offset.current;
}
