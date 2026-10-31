import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';

/** Pobiera dane z API panelu admina; zmiana sciezki (np. strony lub filtra) pobiera je ponownie. */
export function useAdminData<T>(path: string | null) {
  const { t } = useTranslation();
  const [data, setData] = useState<T | null>(null);
  const [error, setError] = useState<string | null>(null);

  const reload = useCallback(async () => {
    if (path === null) return;
    try {
      setData(await api<T>('GET', path));
      setError(null);
    } catch (e) {
      setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
    }
  }, [path, t]);

  useEffect(() => {
    setData(null);
    void reload();
  }, [reload]);

  return { data, error, reload, setData };
}
