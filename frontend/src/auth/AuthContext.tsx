import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { api, ApiError, setCsrfToken } from '../api/client';
import type { Gender, LoginResponse, Me, RegisterInput } from '../api/types';

type Status = 'loading' | 'anon' | 'authed';

export interface AuthState {
  status: Status;
  me: Me | null;
  login: (email: string, password: string) => Promise<LoginResponse['user']>;
  register: (input: RegisterInput) => Promise<void>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
  saveProfile: (gender: Gender, avatarId: number) => Promise<void>;
  updateAccountProfile: (gender: Gender, avatarId: number) => Promise<void>;
  updatePrivacy: (patch: { share_activity?: boolean; show_squad_title?: boolean }) => Promise<void>;
  changeEmail: (email: string, currentPassword: string) => Promise<void>;
  changePassword: (currentPassword: string, password: string, passwordConfirm: string) => Promise<void>;
  resendVerification: () => Promise<void>;
  deleteAccount: (currentPassword: string) => Promise<void>;
}

export const AuthContext = createContext<AuthState | null>(null);

const SESSION_HINT = 'dd_session';

/** Podpowiedz "ta przegladarka miala sesje"; pozwala od razu pokazac wlasciwy ekran startowy. Nie jest zadnym zabezpieczeniem. */
function setSessionHint(on: boolean): void {
  try {
    if (on) localStorage.setItem(SESSION_HINT, '1');
    else localStorage.removeItem(SESSION_HINT);
  } catch {
    // Brak dostepu do localStorage (tryb prywatny) - nic sie nie dzieje.
  }
}

function hasSessionHint(): boolean {
  try {
    return localStorage.getItem(SESSION_HINT) === '1';
  } catch {
    return false;
  }
}

/**
 * Na stronie glownej (jest prerenderowana jako wersja dla niezalogowanych) zaczynamy od 'anon', zeby nie bylo migniecia
 * ekranem ladowania. Wszedzie indziej i u osob z zapamietana sesja czekamy na odpowiedz serwera.
 */
function initialStatus(): Status {
  if (typeof window !== 'undefined' && window.location.pathname === '/' && !hasSessionHint()) return 'anon';
  return 'loading';
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<Status>(initialStatus);
  const [me, setMe] = useState<Me | null>(null);

  const refresh = useCallback(async () => {
    try {
      const data = await api<Me>('GET', '/me');
      setMe(data);
      setStatus('authed');
      setSessionHint(true);
    } catch (e) {
      setMe(null);
      setStatus('anon');
      setSessionHint(false);
      if (!(e instanceof ApiError && e.status === 401)) console.error(e);
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const login = useCallback(
    async (email: string, password: string) => {
      const res = await api<LoginResponse>('POST', '/auth/login', { email, password });
      setCsrfToken(res.csrf_token);
      await refresh();
      return res.user;
    },
    [refresh],
  );

  const register = useCallback(async (input: RegisterInput) => {
    await api('POST', '/auth/register', input);
  }, []);

  const logout = useCallback(async () => {
    try {
      await api('POST', '/auth/logout');
    } finally {
      setCsrfToken(null);
      setMe(null);
      setStatus('anon');
      setSessionHint(false);
    }
  }, []);

  const saveProfile = useCallback(async (gender: Gender, avatarId: number) => {
    const data = await api<Me>('PATCH', '/me', { gender, avatar_id: avatarId });
    setMe(data);
  }, []);

  const updateAccountProfile = useCallback(async (gender: Gender, avatarId: number) => {
    setMe(await api<Me>('PATCH', '/account/profile', { gender, avatar_id: avatarId }));
  }, []);

  const updatePrivacy = useCallback(async (patch: { share_activity?: boolean; show_squad_title?: boolean }) => {
    setMe(await api<Me>('PATCH', '/account/privacy', patch));
  }, []);

  const changeEmail = useCallback(async (email: string, currentPassword: string) => {
    setMe(await api<Me>('PUT', '/account/email', { email, current_password: currentPassword }));
  }, []);

  const changePassword = useCallback(async (currentPassword: string, password: string, passwordConfirm: string) => {
    await api('PUT', '/account/password', { current_password: currentPassword, password, password_confirm: passwordConfirm });
  }, []);

  const resendVerification = useCallback(async () => {
    await api('POST', '/account/resend-verification');
  }, []);

  const deleteAccount = useCallback(async (currentPassword: string) => {
    await api('POST', '/account/delete', { current_password: currentPassword });
    setCsrfToken(null);
    setMe(null);
    setStatus('anon');
    setSessionHint(false);
  }, []);

  const value = useMemo<AuthState>(
    () => ({ status, me, login, register, logout, refresh, saveProfile, updateAccountProfile, updatePrivacy, changeEmail, changePassword, resendVerification, deleteAccount }),
    [status, me, login, register, logout, refresh, saveProfile, updateAccountProfile, updatePrivacy, changeEmail, changePassword, resendVerification, deleteAccount],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth wymaga AuthProvider');
  return ctx;
}
