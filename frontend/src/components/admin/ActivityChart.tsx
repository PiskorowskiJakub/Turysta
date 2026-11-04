import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../../api/client';
import type { ActivitySeries } from '../../api/types';
import { formatDate } from '../../format';

type Metric = 'active' | 'logins' | 'registrations';
type Group = 'day' | 'week' | 'month';

interface Props {
  /** hours: co godzine, days: co dobe. */
  view: 'hours' | 'days';
  spans: number[];
  defaultSpan: number;
}

const METRICS: Metric[] = ['active', 'logins', 'registrations'];
const GROUPS: Group[] = ['day', 'week', 'month'];

/** Domyslne grupowanie: przy duzych zakresach tygodnie, zeby wykres byl czytelny. */
const defaultGroup = (span: number): Group => (span >= 180 ? 'week' : 'day');

/** Interaktywny wykres slupkowy aktywnosci: wybor zakresu i miary, linie siatki, podsumowanie oraz podpowiedz po najechaniu. */
export default function ActivityChart({ view, spans, defaultSpan }: Props) {
  const { t } = useTranslation();
  const [span, setSpan] = useState(defaultSpan);
  const [group, setGroup] = useState<Group>(defaultGroup(defaultSpan));
  const [metric, setMetric] = useState<Metric>('active');
  const [data, setData] = useState<ActivitySeries | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [hover, setHover] = useState<number | null>(null);

  useEffect(() => {
    let alive = true;
    setError(null);
    api<ActivitySeries>('GET', `/admin/activity?view=${view}&span=${span}${view === 'days' ? `&group=${group}` : ''}`)
      .then((d) => alive && setData(d))
      .catch((e) => alive && setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network')));
    return () => {
      alive = false;
    };
  }, [view, span, group, t]);

  const label = (key: string, long: boolean): string => {
    if (view === 'days') {
      if (group === 'month') return key.slice(0, 7);
      if (group === 'week') return long ? t('admin.stats.chart.weekFrom', { date: formatDate(key) }) : key.slice(5);
      return long ? formatDate(key) : key.slice(5);
    }
    const d = new Date(key);
    const time = d.toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
    return long ? `${d.toLocaleDateString('pl-PL', { day: '2-digit', month: '2-digit' })} ${time}` : time;
  };

  const stats = useMemo(() => {
    if (!data) return null;
    const values = data.points.map((p) => p[metric]);
    const max = Math.max(1, ...values);
    const total = values.reduce((s, v) => s + v, 0);
    const peakIdx = values.indexOf(Math.max(...values));
    return { values, max, total, avg: total / Math.max(1, values.length), peakIdx };
  }, [data, metric]);

  const trackingNote = data?.tracking_since && data.points.length > 0 && data.points[0].key < data.tracking_since
    ? t('admin.stats.chart.since', { date: view === 'days' ? formatDate(data.tracking_since) : label(data.tracking_since, true) })
    : null;

  const spanLabel = (n: number) => (view === 'hours' ? t('admin.stats.chart.hours', { n }) : t('admin.stats.chart.days', { n }));
  const shown = hover ?? stats?.peakIdx ?? null;
  const ticks = [1, 0.5, 0];

  return (
    <figure className="activity-chart">
      <div className="activity-controls">
        <div className="segmented" role="group" aria-label={t('admin.stats.chart.range')}>
          {spans.map((n) => (
            <button key={n} type="button" className={'seg' + (n === span ? ' active' : '')} aria-pressed={n === span} onClick={() => { setSpan(n); setGroup(defaultGroup(n)); }}>{spanLabel(n)}</button>
          ))}
        </div>
        {view === 'days' && (
          <div className="segmented" role="group" aria-label={t('admin.stats.chart.group')}>
            {GROUPS.map((g) => (
              <button key={g} type="button" className={'seg' + (g === group ? ' active' : '')} aria-pressed={g === group} onClick={() => setGroup(g)}>{t(`admin.stats.chart.groups.${g}`)}</button>
            ))}
          </div>
        )}
        <div className="segmented" role="group" aria-label={t('admin.stats.chart.metric')}>
          {METRICS.map((m) => (
            <button key={m} type="button" className={'seg' + (m === metric ? ' active' : '')} aria-pressed={m === metric} onClick={() => setMetric(m)}>{t(`admin.stats.chart.metrics.${m}`)}</button>
          ))}
        </div>
      </div>

      {error && <p className="form-error" role="alert">{error}</p>}
      {!data && !error && <p className="muted">{t('loading')}</p>}

      {data && stats && (
        <>
          <p className="activity-summary">
            {shown !== null && data.points[shown] ? (
              <><b>{label(data.points[shown].key, true)}</b>: {data.points[shown][metric]}</>
            ) : null}
            <span className="muted"> · {t('admin.stats.chart.sum', { n: stats.total })} · {t('admin.stats.chart.avg', { n: stats.avg.toFixed(1) })} · {t('admin.stats.chart.max', { n: stats.max })}</span>
          </p>
          <div className="activity-plot">
            <div className="activity-yaxis" aria-hidden="true">
              {ticks.map((f) => <span key={f}>{Math.round(stats.max * f)}</span>)}
            </div>
            <div className="activity-bars" style={{ gap: data.points.length > 120 ? 0 : data.points.length > 60 ? 1 : undefined }} role="img" aria-label={`${t(`admin.stats.chart.metrics.${metric}`)}: ${stats.total}`} onMouseLeave={() => setHover(null)}>
              {data.points.map((p, i) => (
                <span
                  key={p.key}
                  className="activity-col"
                  onMouseEnter={() => setHover(i)}
                  title={`${label(p.key, true)}: ${p[metric]}`}
                >
                  <i className={'chart-bar' + (i === shown ? ' hot' : '')} style={{ height: `${Math.max(p[metric] > 0 ? 3 : 1, (p[metric] / stats.max) * 100)}%` }} />
                </span>
              ))}
            </div>
          </div>
          <div className="bars-axis activity-xaxis">
            <span>{label(data.points[0].key, false)}</span>
            <span>{label(data.points[Math.floor(data.points.length / 2)].key, false)}</span>
            <span>{label(data.points[data.points.length - 1].key, false)}</span>
          </div>
          {trackingNote && <p className="field-hint">{trackingNote}</p>}
        </>
      )}
    </figure>
  );
}
