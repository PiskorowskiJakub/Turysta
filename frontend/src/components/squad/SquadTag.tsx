import { Link } from 'react-router-dom';
import SquadEmblem from './SquadEmblem';

/** Oddzial gracza w tabelach: emblemat w ramce i nazwa jako link (na telefonie tylko emblemat, nazwa w podpowiedzi); myslnik, gdy gracz nie ma oddzialu. */
export default function SquadTag({ squad }: { squad: { id: number; display_name: string; emblem: string | null; frame: string | null } | null }) {
  if (!squad) return <span className="muted">—</span>;
  return (
    <span className="squad-title squad-tag">
      <Link to={`/oddzial/${squad.id}`} className="squad-tag-emblem" title={squad.display_name} aria-label={squad.display_name}>
        <SquadEmblem emblem={squad.emblem} frame={squad.frame} size={26} />
      </Link>
      <Link to={`/oddzial/${squad.id}`} className="squad-tag-name">{squad.display_name}</Link>
    </span>
  );
}
