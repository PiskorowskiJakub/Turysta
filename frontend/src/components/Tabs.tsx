import { NavLink, useLocation } from 'react-router-dom';

export interface TabItem {
  path: string;
  label: string;
  children?: TabItem[];
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
        {items.map((i) => (
          <NavLink key={i.path} to={`${base}/${i.path}`} className={({ isActive }) => 'tab' + (isActive ? ' active' : '')}>
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
