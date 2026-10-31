import { NavLink, useLocation } from 'react-router-dom';
import { Icon } from './icons';

export interface TabItem {
  path: string;
  label: string;
  children?: TabItem[];
  /** Zablokowana zakladka: widoczna z klodka, bez linku. */
  disabled?: boolean;
  /** Ukonczona zakladka: zielony ptaszek przy etykiecie. */
  done?: boolean;
  hint?: string;
}

interface Props {
  /** Sciezka bazowa, np. /umiejetnosci */
  base: string;
  items: TabItem[];
}

/**
 * Zakladki nad trescia. Aktywna zakladka z podzakladkami pokazuje pod soba
 * kolejny rzad - zagniezdzenie moze byc dowolnie gleboke.
 */
export default function Tabs({ base, items }: Props) {
  const { pathname } = useLocation();
  const active = items.find((i) => pathname === `${base}/${i.path}` || pathname.startsWith(`${base}/${i.path}/`));

  return (
    <>
      <nav className="tabs" aria-label="Zakładki">
        {items.map((i) => i.disabled ? (
          <span key={i.path} className="tab disabled" title={i.hint} aria-disabled="true">
            <Icon name="lock" />
            {i.label}
          </span>
        ) : (
          <NavLink key={i.path} to={`${base}/${i.path}`} className={({ isActive }) => 'tab' + (isActive ? ' active' : '') + (i.done ? ' done' : '')}>
            {i.done && <Icon name="check" />}
            {i.label}
          </NavLink>
        ))}
      </nav>
      {active?.children && active.children.length > 0 && (
        <div className="tabs-sub">
          <Tabs base={`${base}/${active.path}`} items={active.children} />
        </div>
      )}
    </>
  );
}
