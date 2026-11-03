import { useCallback, useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import type { BadgesResponse, EarnedBadge, SkillsResponse } from '../api/types';
import { TIER_ORDER } from '../badges';
import { usePolling } from './usePolling';

export interface Highest extends EarnedBadge {
  count: number;
}

/** Wspolne wyliczenia dla odznak (wlasnych i cudzych): najwyzszy stopien, podsumowanie wg stopni i nazwy odznak. */
export function useBadgeView(skills: SkillsResponse | null, earned: EarnedBadge[] | null) {
  const { t } = useTranslation();
  const highest = useMemo(() => {
    const map = new Map<string, Highest>();
    for (const b of earned ?? []) {
      const cur = map.get(b.group);
      const count = (cur?.count ?? 0) + 1;
      if (!cur || TIER_ORDER.indexOf(b.tier) > TIER_ORDER.indexOf(cur.tier)) map.set(b.group, { ...b, count });
      else cur.count = count;
    }
    return map;
  }, [earned]);

  // Ile odznak jest w kazdym stopniu; kazda odznaka liczy sie tylko raz, w swoim najwyzszym zdobytym stopniu.
  const tally = useMemo(() => {
    const out: Record<string, number> = {};
    for (const h of highest.values()) out[h.tier] = (out[h.tier] ?? 0) + 1;
    return out;
  }, [highest]);

  /** Nazwa i rodzaj progu (poziom umiejetnosci albo liczba dni) odznaki o danej grupie. */
  const meta = useCallback(
    (group: string): { name: string; unit: 'level' | 'days' | 'friends' | 'referrals' } => {
      const sk = skills?.skills.find((x) => x.code === group);
      if (sk) return { name: t(`skills.${sk.code}`, { defaultValue: sk.name }), unit: 'level' };
      const unit = group === 'special_friends' ? 'friends' : group === 'special_referrals' ? 'referrals' : 'days';
      return { name: t(`badges.${group}`), unit };
    },
    [skills, t],
  );

  return { highest, tally, meta };
}

/** Zdobyte odznaki gracza: dla kazdej umiejetnosci/odznaki specjalnej najwyzszy stopien, podsumowanie wg stopni i wyroznione. */
export function useBadges() {
  const { t } = useTranslation();
  const [skills, setSkills] = useState<SkillsResponse | null>(null);
  const [earned, setEarned] = useState<EarnedBadge[] | null>(null);
  const [featured, setFeatured] = useState<string[]>([]);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const [s, b] = await Promise.all([api<SkillsResponse>('GET', '/skills'), api<BadgesResponse>('GET', '/badges')]);
      setSkills(s);
      setEarned(b.badges);
      setFeatured(b.featured);
    } catch (e) {
      setError(e instanceof ApiError ? t(`errors.${e.code}`, { defaultValue: e.message }) : t('errors.network'));
    }
  }, [t]);

  useEffect(() => {
    void load();
  }, [load]);
  usePolling(() => void load(), 30000);

  const view = useBadgeView(skills, earned);

  return { ...view, ready: skills !== null && earned !== null, error, setError, skills, earned, featured, setFeatured };
}

export type BadgesData = ReturnType<typeof useBadges>;
