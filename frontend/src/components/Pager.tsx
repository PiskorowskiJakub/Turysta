import { useTranslation } from 'react-i18next';

interface Props {
  page: number;
  pages: number;
  total: number;
  onPage: (page: number) => void;
}

/** Stronicowanie list w panelu admina: pierwsza, poprzednia, numer strony, nastepna, ostatnia (w jednym wierszu). */
export default function Pager({ page, pages, total, onPage }: Props) {
  const { t } = useTranslation();
  return (
    <nav className="ranking-pager" aria-label={t('admin.pager')}>
      <button type="button" className="btn" disabled={page <= 1} onClick={() => onPage(1)}>{t('ranking.first')}</button>
      <button type="button" className="btn" disabled={page <= 1} onClick={() => onPage(page - 1)}>{t('ranking.prev')}</button>
      <span>{t('ranking.pageOf', { page, pages })} · {t('admin.total', { n: total })}</span>
      <button type="button" className="btn" disabled={page >= pages} onClick={() => onPage(page + 1)}>{t('ranking.next')}</button>
      <button type="button" className="btn" disabled={page >= pages} onClick={() => onPage(pages)}>{t('ranking.last')}</button>
    </nav>
  );
}
