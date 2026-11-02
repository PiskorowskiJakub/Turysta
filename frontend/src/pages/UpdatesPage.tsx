import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { UpdateEntry } from '../api/types';
import RichText from '../components/RichText';
import { formatDate } from '../format';

/** Aktualizacje gry (dziennik zmian). Adres /aktualizacje/:id przewija do wybranego wpisu i go podswietla. */
export default function UpdatesPage() {
  const { t } = useTranslation();
  const { id } = useParams();
  const [items, setItems] = useState<UpdateEntry[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api<{ updates: UpdateEntry[] }>('GET', '/updates')
      .then((r) => setItems(r.updates))
      .catch((e) => setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network')));
  }, [t]);

  useEffect(() => {
    if (!items || !id) return;
    document.getElementById(`update-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, [items, id]);

  return (
    <>
      <section className="panel">
        <h1>{t('updates.title')}</h1>
        <p>{t('updates.intro')}</p>
        {id && <p><Link to="/aktualizacje">{t('updates.all')}</Link></p>}
      </section>
      {error && <p className="form-error" role="alert">{error}</p>}
      {!items && !error && <p className="muted">{t('loading')}</p>}
      {items && items.length === 0 && <p className="muted">{t('updates.empty')}</p>}
      {items?.map((u) => (
        <article key={u.id} id={`update-${u.id}`} className={`panel update${String(u.id) === id ? ' update-target' : ''}`}>
          <h2>{u.title}</h2>
          <time dateTime={u.released_on}>{formatDate(`${u.released_on}T12:00:00`)}</time>
          <div className="update-body"><RichText text={u.body} /></div>
        </article>
      ))}
    </>
  );
}
