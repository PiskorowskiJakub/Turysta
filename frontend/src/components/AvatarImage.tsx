import { useEffect, useState } from 'react';

/** Obrazek awatara; gdy sie nie zaladuje, pokazuje pierwsza litere nazwy postaci. */
export default function AvatarImage({ src, name }: { src?: string | null; name: string }) {
  const [failed, setFailed] = useState(false);
  useEffect(() => setFailed(false), [src]);
  if (!src || failed) return <>{name.slice(0, 1).toUpperCase()}</>;
  return <img src={src} alt="" onError={() => setFailed(true)} />;
}
