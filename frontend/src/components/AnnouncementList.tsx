import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import type { Announcement } from '../api/types';
import { formatDate } from '../format';

/** Lista ogloszen z tablicy (przypiete oznaczone gwiazdka). Zwiniete do tytulu i daty; domyslnie rozwiniete tylko pierwsze. */
export default function AnnouncementList({ items }: { items: Announcement[] }) {
  const { t } = useTranslation();
  const [open, setOpen] = useState<Set<number>>(() => new Set(items.length > 0 ? [items[0].id] : []));
  if (items.length === 0) return <p className="muted">{t('home.board.empty')}</p>;
  const toggle = (id: number) =>
    setOpen((prev) => {
      const next = new Set(prev);
      if (!next.delete(id)) next.add(id);
      return next;
    });
  return (
    <ul className="board">
      {items.map((a) => {
        const isOpen = open.has(a.id);
        return (
          <li key={a.id} className={(a.pinned ? 'pinned' : '') + (isOpen ? ' open' : '')}>
            <button type="button" className="board-head" aria-expanded={isOpen} onClick={() => toggle(a.id)}>
              <span className="board-title">
                {a.pinned && <span title={t('home.board.pinned')} aria-label={t('home.board.pinned')}>★ </span>}
                {a.title}
              </span>
              <time dateTime={a.created_at}>{formatDate(a.created_at)}</time>
              <span className="board-chevron" aria-hidden="true">{isOpen ? '▲' : '▼'}</span>
            </button>
            {isOpen && (
              <div className="board-body">
                <p>{a.body}</p>
                {typeof a.update_id === 'number' && <p><Link to={`/aktualizacje/${a.update_id}`}>{t('home.board.more')}</Link></p>}
              </div>
            )}
          </li>
        );
      })}
    </ul>
  );
}
