import { EMBLEM_SVG, type EmblemCode } from './squadEmblems';

/** Emblemat oddzialu w ramce (braz..rubin). Bez emblematu nic nie rysuje, a bez ramki pokazuje delikatny obrys. */
export default function SquadEmblem({ emblem, frame, size = 44, label }: { emblem: string | null | undefined; frame?: string | null; size?: number; label?: string }) {
  if (!emblem || !(emblem in EMBLEM_SVG)) return null;
  return (
    <span
      className={'squad-emblem' + (frame ? ` tier-${frame} framed` : '')}
      style={{ width: size, height: size }}
      role={label ? 'img' : undefined}
      aria-label={label}
      aria-hidden={label ? undefined : true}
    >
      <svg viewBox="0 0 64 64" dangerouslySetInnerHTML={{ __html: EMBLEM_SVG[emblem as EmblemCode] }} />
    </span>
  );
}
