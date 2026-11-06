import { Link } from 'react-router-dom';
import SquadEmblem from './SquadEmblem';

/** Oddzial gracza w tabelach: emblemat w ramce i nazwa jako link; myslnik, gdy gracz nie ma oddzialu. */
export default function SquadTag({ squad }: { squad: { id: number; display_name: string; emblem: string | null; frame: string | null } | null }) {
  if (!squad) return <span className="muted">—</span>;
  return (
    <span className="squad-title">
      <SquadEmblem emblem={squad.emblem} frame={squad.frame} size={26} />
      <Link to={`/oddzial/${squad.id}`}>{squad.display_name}</Link>
    </span>
  );
}
