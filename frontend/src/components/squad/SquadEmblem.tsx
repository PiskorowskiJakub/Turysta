import { DEFAULT_EMBLEM_SVG, EMBLEM_SVG, type EmblemCode } from './squadEmblems';

/** Emblemat oddzialu w ramce (braz..rubin). Oddzial bez emblematu dostaje szara tarcze, a bez ramki delikatny obrys. */
export default function SquadEmblem({ emblem, frame, size = 44, label }: { emblem: string | null | undefined; frame?: string | null; size?: number; label?: string }) {
  const known = !!emblem && emblem in EMBLEM_SVG;
  return (
    <span
      className={'squad-emblem' + (known ? '' : ' default') + (frame ? ` tier-${frame} framed` : '')}
      style={{ width: size, height: size }}
      role={label ? 'img' : undefined}
      aria-label={label}
      aria-hidden={label ? undefined : true}
    >
      <svg viewBox="0 0 64 64" dangerouslySetInnerHTML={{ __html: known ? EMBLEM_SVG[emblem as EmblemCode] : DEFAULT_EMBLEM_SVG }} />
    </span>
  );
}
