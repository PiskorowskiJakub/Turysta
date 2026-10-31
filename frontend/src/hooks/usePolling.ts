import { useEffect, useRef } from 'react';

/** Wywoluje callback co intervalMs milisekund (np. odswiezanie stanu gry). */
export function usePolling(callback: () => void, intervalMs: number): void {
  const saved = useRef(callback);
  saved.current = callback;
  useEffect(() => {
    const id = setInterval(() => saved.current(), intervalMs);
    return () => clearInterval(id);
  }, [intervalMs]);
}
