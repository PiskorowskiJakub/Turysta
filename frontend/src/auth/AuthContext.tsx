import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { api, ApiError, setCsrfToken } from '../api/client';
import type { Gender, LoginResponse, Me, RegisterInput } from '../api/types';

type Status = 'loading' | 'anon' | 'authed';

interface AuthState {
  status: Status;
  me: Me | null;
  login: (email: string, password: string) => Promise<LoginResponse['user']>;
  register: (input: RegisterInput) => Promise<void>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
  saveProfile: (gender: Gender, avatarId: number) => Promise<void>;
}

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<Status>('loading');
  const [me, setMe] = useState<Me | null>(null);

  const refresh = useCallback(async () => {
    try {
      const data = await api<Me>('GET', '/me');
      setMe(data);
      setStatus('authed');
    } catch (e) {
      setMe(null);
      setStatus('anon');
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
    }
  }, []);

  const saveProfile = useCallback(async (gender: Gender, avatarId: number) => {
    const data = await api<Me>('PATCH', '/me', { gender, avatar_id: avatarId });
    setMe(data);
  }, []);

  const value = useMemo<AuthState>(
    () => ({ status, me, login, register, logout, refresh, saveProfile }),
    [status, me, login, register, logout, refresh, saveProfile],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth wymaga AuthProvider');
  return ctx;
}
