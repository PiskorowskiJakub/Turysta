import { useCallback, useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Link, Navigate, Route, Routes, useNavigate, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import type { TFunction } from 'i18next';
import { api, ApiError } from '../api/client';
import type { SquadCard, SquadCatalog, SquadMeta, SquadMine, SquadProfile } from '../api/types';
import Pager from '../components/Pager';
import Tabs, { type TabItem } from '../components/Tabs';
import SquadFields, { type SquadDraft } from '../components/squad/SquadFields';
import SquadPanel, { type SquadSection } from '../components/squad/SquadPanel';
import SquadEmblem from '../components/squad/SquadEmblem';
import SquadPublic from '../components/squad/SquadPublic';
import SquadShelterPanel from '../components/squad/SquadShelterPanel';
import SquadTrailPanel, { type TrailSection } from '../components/squad/SquadTrailPanel';
import { formatDate } from '../format';

function errText(t: TFunction, e: unknown): string {
  return e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network');
}

/** Strona "Oddzial": zakladki Moj oddzial i Katalog oraz publiczny profil dowolnego oddzialu. */
export default function SquadPage() {
  const { t } = useTranslation();
  const [mine, setMine] = useState<SquadMine | null>(null);
  const [squad, setSquad] = useState<SquadProfile | null>(null);
  const [error, setError] = useState<string | null>(null);

  const reload = useCallback(async () => {
    try {
      setMine(await api<SquadMine>('GET', '/squads/mine'));
      setError(null);
    } catch (e) {
      setError(errText(t, e));
    }
  }, [t]);

  useEffect(() => {
    void reload();
  }, [reload]);

  useEffect(() => setSquad(mine?.squad ?? null), [mine]);

  if (!mine) return <p className="panel page-message">{error ?? t('loading')}</p>;

  const waiting = squad?.requests?.requests.length ?? 0;
  const mineChildren: TabItem[] | undefined = squad
    ? [
        { path: 'przeglad', label: t('squad.tabs.overview') },
        { path: 'czlonkowie', label: t('squad.tabs.members') },
        ...(squad.me.can_recruit
          ? [{
              path: 'rekrutacja',
              label: t('squad.tabs.recruitment'),
              children: [
                { path: 'zaproszeni', label: t('squad.tabs.invited') },
                { path: 'oczekujacy', label: waiting > 0 ? t('squad.tabs.waitingCount', { n: waiting }) : t('squad.tabs.waiting') },
              ],
            }]
          : []),
        { path: 'ustawienia', label: t('squad.tabs.settings') },
        { path: 'odznaki', label: t('squad.tabs.badges') },
        { path: 'ksiega', label: t('squad.tabs.book') },
        { path: 'kronika', label: t('squad.tabs.chronicle') },
      ]
    : undefined;

  return (
    <>
      <Tabs
        base="/oddzial"
        items={[
          { path: 'moj', label: t('squad.tabs.mine'), children: mineChildren },
          ...(squad
            ? [{
                path: 'szlaki',
                label: t('squad.tabs.trails'),
                children: [
                  { path: 'wyprawa', label: t('squad.tabs.trailNow') },
                  { path: 'historia', label: t('squad.tabs.trailHistory') },
                ],
              }]
            : []),
          ...(squad ? [{ path: 'schronisko', label: t('squad.tabs.shelter') }] : []),
          { path: 'katalog', label: t('squad.tabs.catalog') },
        ]}
      />
      <Routes>
        <Route index element={<Navigate to="moj" replace />} />
        <Route path="moj/*" element={<MineTab mine={mine} squad={squad} setSquad={setSquad} setMine={setMine} reload={reload} />} />
        {squad ? (
          <>
            <Route path="szlaki" element={<Navigate to="wyprawa" replace />} />
            {(['wyprawa', 'historia'] as TrailSection[]).map((s) => (
              <Route key={s} path={`szlaki/${s}`} element={<SquadTrailPanel squadId={squad.id} section={s} />} />
            ))}
          </>
        ) : (
          <Route path="szlaki/*" element={<Navigate to="/oddzial/moj" replace />} />
        )}
        {squad ? <Route path="schronisko" element={<SquadShelterPanel squadId={squad.id} />} /> : <Route path="schronisko" element={<Navigate to="/oddzial/moj" replace />} />}
        <Route path="katalog" element={<CatalogTab meta={mine.meta} />} />
        <Route path=":id" element={<ProfileTab mine={mine} reload={reload} />} />
      </Routes>
    </>
  );
}

function MineTab({ mine, squad, setSquad, setMine, reload }: { mine: SquadMine; squad: SquadProfile | null; setSquad: (s: SquadProfile) => void; setMine: (m: SquadMine) => void; reload: () => Promise<void> }) {
  const { t } = useTranslation();
  if (!squad) return <Onboarding mine={mine} reload={reload} title={t('squad.onboarding.title')} />;

  const panel = (section: SquadSection) => <SquadPanel section={section} squad={squad} meta={mine.meta} onSquad={setSquad} onMine={setMine} />;
  return (
    <Routes>
      <Route index element={<Navigate to="przeglad" replace />} />
      <Route path="przeglad" element={panel('przeglad')} />
      <Route path="czlonkowie" element={panel('czlonkowie')} />
      {squad.me.can_recruit ? (
        <>
          <Route path="rekrutacja" element={<Navigate to="zaproszeni" replace />} />
          <Route path="rekrutacja/zaproszeni" element={panel('zaproszeni')} />
          <Route path="rekrutacja/oczekujacy" element={panel('oczekujacy')} />
        </>
      ) : null}
      <Route path="ustawienia" element={panel('ustawienia')} />
      <Route path="odznaki" element={panel('odznaki')} />
      <Route path="ksiega" element={panel('ksiega')} />
      <Route path="kronika" element={panel('kronika')} />
      <Route path="*" element={<Navigate to="/oddzial/moj/przeglad" replace />} />
    </Routes>
  );
}

/** Gracz bez oddzialu: zaproszenia, wlasne prosby i formularz zalozenia. */
function Onboarding({ mine, reload, title }: { mine: SquadMine; reload: () => Promise<void>; title: string }) {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [draft, setDraft] = useState<SquadDraft>({ name: '', specialization: 'general', join_mode: 'request' });
  const [fields, setFields] = useState<Record<string, string>>({});
  const [message, setMessage] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const el = mine.eligibility;

  const create = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setFields({});
    setMessage(null);
    try {
      await api<SquadMine & { id: number }>('POST', '/squads', draft);
      await reload();
      navigate('/oddzial/moj');
    } catch (err) {
      if (err instanceof ApiError && Object.keys(err.fields).length > 0) setFields(err.fields);
      else setMessage(errText(t, err));
    } finally {
      setBusy(false);
    }
  };

  const act = async (action: () => Promise<unknown>) => {
    setBusy(true);
    setMessage(null);
    try {
      await action();
      await reload();
    } catch (err) {
      setMessage(errText(t, err));
    } finally {
      setBusy(false);
    }
  };

  const cardRow = (c: SquadCard, actions: ReactNode) => (
    <li key={c.id}>
      <SquadEmblem emblem={c.emblem} frame={c.frame} size={24} />
      <Link to={`/oddzial/${c.id}`}>{c.display_name}</Link>
      <span className="muted"> · {c.members}/{c.max_members}</span>
      <span className="actions">{actions}</span>
    </li>
  );

  return (
    <>
      <section className="panel">
        <h2>{title}</h2>
        <p>{t('squad.onboarding.intro', { max: mine.meta.limits.max_members })}</p>
        {!el.ok && el.reason && (
          <p className="form-error" role="status">
            {t(`squad.eligibility.${el.reason}`, { until: el.until ? formatDate(el.until) : '', days: mine.meta.limits.min_account_days })}
          </p>
        )}
        {message && <p className="form-error" role="alert">{message}</p>}
        <p><Link to="/oddzial/katalog">{t('squad.onboarding.browse')}</Link></p>
      </section>

      {mine.invites.length > 0 && (
        <section className="panel">
          <h2>{t('squad.onboarding.invites', { n: mine.invites.length })}</h2>
          <ul className="admin-list">
            {mine.invites.map((c) =>
              cardRow(c, (
                <>
                  <button type="button" className="button small" disabled={busy || !el.ok} onClick={() => void act(() => api('POST', `/squads/${c.id}/invitation/accept`))}>{t('squad.public.acceptInvite')}</button>
                  <button type="button" className="button small secondary" disabled={busy} onClick={() => void act(() => api('DELETE', `/squads/${c.id}/request`))}>{t('squad.onboarding.declineInvite')}</button>
                </>
              )),
            )}
          </ul>
        </section>
      )}

      {mine.requests.length > 0 && (
        <section className="panel">
          <h2>{t('squad.onboarding.requests', { n: mine.requests.length })}</h2>
          <ul className="admin-list">
            {mine.requests.map((c) =>
              cardRow(c, <button type="button" className="button small secondary" disabled={busy} onClick={() => void act(() => api('DELETE', `/squads/${c.id}/request`))}>{t('squad.public.dropRequest')}</button>),
            )}
          </ul>
        </section>
      )}

      {el.ok && (
        <form className="panel" onSubmit={(e) => void create(e)} noValidate>
          <h2>{t('squad.create.title')}</h2>
          <SquadFields meta={mine.meta} value={draft} onChange={setDraft} errors={fields} disabled={busy} idPrefix="squad-new" />
          <button className="button" type="submit" disabled={busy || draft.name.trim() === ''}>{t('squad.create.submit')}</button>
          <p className="field-hint">{t('squad.create.hint')}</p>
        </form>
      )}
    </>
  );
}

function CatalogTab({ meta }: { meta: SquadMeta }) {
  const { t } = useTranslation();
  const [spec, setSpec] = useState('');
  const [q, setQ] = useState('');
  const [query, setQuery] = useState('');
  const [page, setPage] = useState(1);
  const [data, setData] = useState<SquadCatalog | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const params = new URLSearchParams();
    if (spec) params.set('spec', spec);
    if (query) params.set('q', query);
    params.set('page', String(page));
    let alive = true;
    api<SquadCatalog>('GET', `/squads?${params.toString()}`)
      .then((d) => alive && setData(d))
      .catch((e) => alive && setError(errText(t, e)));
    return () => {
      alive = false;
    };
  }, [spec, query, page, t]);

  const onSearch = (e: FormEvent) => {
    e.preventDefault();
    setPage(1);
    setQuery(q.trim());
  };

  return (
    <section className="panel">
      <h2>{t('squad.catalog.title')}</h2>
      <form className="squad-filter" onSubmit={onSearch}>
        <label className="sr-only" htmlFor="squad-q">{t('squad.catalog.search')}</label>
        <input id="squad-q" value={q} placeholder={t('squad.catalog.search')} maxLength={40} onChange={(e) => setQ(e.target.value)} />
        <label className="sr-only" htmlFor="squad-spec">{t('squad.fields.specialization')}</label>
        <select id="squad-spec" value={spec} onChange={(e) => { setPage(1); setSpec(e.target.value); }}>
          <option value="">{t('squad.catalog.allSpecs')}</option>
          {meta.specializations.map((s) => (
            <option key={s.code} value={s.code}>{s.label}</option>
          ))}
        </select>
        <button className="button small" type="submit">{t('squad.catalog.searchButton')}</button>
      </form>
      {error && <p className="form-error" role="alert">{error}</p>}
      {!data ? (
        <p>{t('loading')}</p>
      ) : data.squads.length === 0 ? (
        <p className="muted">{t('squad.catalog.empty')}</p>
      ) : (
        <>
          <div className="table-scroll">
            <table className="ranking-table">
              <thead>
                <tr>
                  <th>{t('squad.catalog.name')}</th>
                  <th className="num">{t('squad.catalog.members')}</th>
                  <th>{t('squad.catalog.fire')}</th>
                  <th>{t('squad.fields.joinMode')}</th>
                </tr>
              </thead>
              <tbody>
                {data.squads.map((c) => (
                  <tr key={c.id}>
                    <td><span className="squad-title"><SquadEmblem emblem={c.emblem} frame={c.frame} size={28} /><Link to={`/oddzial/${c.id}`}>{c.display_name}</Link></span></td>
                    <td className="num" data-label={t('squad.catalog.members')}>{c.members}/{c.max_members}</td>
                    <td data-label={t('squad.catalog.fire')}>{t(`squad.flame.${c.flame_level}`)}</td>
                    <td data-label={t('squad.fields.joinMode')}>{t(`squad.modes.${c.join_mode}`)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pager page={data.page} pages={data.pages} total={data.total} onPage={setPage} />
        </>
      )}
    </section>
  );
}

function ProfileTab({ mine, reload }: { mine: SquadMine; reload: () => Promise<void> }) {
  const { t } = useTranslation();
  const { id } = useParams();
  const [squad, setSquad] = useState<SquadProfile | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      setSquad(await api<SquadProfile>('GET', `/squads/${id}`));
      setError(null);
    } catch (e) {
      setError(errText(t, e));
    }
  }, [id, t]);

  useEffect(() => {
    setSquad(null);
    void load();
  }, [load]);

  if (mine.squad && String(mine.squad.id) === id) return <Navigate to="/oddzial/moj" replace />;
  if (!squad) return <p className="panel page-message">{error ?? t('loading')}</p>;
  return (
    <SquadPublic
      squad={squad}
      meta={mine.meta}
      onChanged={() => {
        void load();
        void reload();
      }}
    />
  );
}
