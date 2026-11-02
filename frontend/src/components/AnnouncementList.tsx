import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import type { Announcement } from '../api/types';
import { formatDate } from '../format';

/** Lista ogloszen z tablicy (przypiete oznaczone gwiazdka). */
export default function AnnouncementList({ items }: { items: Announcement[] }) {
  const { t } = useTranslation();
  if (items.length === 0) return <p className="muted">{t('home.board.empty')}</p>;
  return (
    <ul className="board">
      {items.map((a) => (
        <li key={a.id} className={a.pinned ? 'pinned' : ''}>
          <h3>{a.pinned && <span title={t('home.board.pinned')} aria-label={t('home.board.pinned')}>★ </span>}{a.title}</h3>
          <time dateTime={a.created_at}>{formatDate(a.created_at)}</time>
          <p>{a.body}</p>
          {typeof a.update_id === 'number' && <p><Link to={`/aktualizacje/${a.update_id}`}>{t('home.board.more')}</Link></p>}
        </li>
      ))}
    </ul>
  );
}
