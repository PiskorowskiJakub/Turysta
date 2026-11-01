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
  updateAccountProfile: (gender: Gender, avatarId: number) => Promise<void>;
  changeEmail: (email: string, currentPassword: string) => Promise<void>;
  changePassword: (currentPassword: string, password: string, passwordConfirm: string) => Promise<void>;
  resendVerification: () => Promise<void>;
  deleteAccount: (currentPassword: string) => Promise<void>;
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

  const updateAccountProfile = useCallback(async (gender: Gender, avatarId: number) => {
    setMe(await api<Me>('PATCH', '/account/profile', { gender, avatar_id: avatarId }));
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
  }, []);

  const value = useMemo<AuthState>(
    () => ({ status, me, login, register, logout, refresh, saveProfile, updateAccountProfile, changeEmail, changePassword, resendVerification, deleteAccount }),
    [status, me, login, register, logout, refresh, saveProfile, updateAccountProfile, changeEmail, changePassword, resendVerification, deleteAccount],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth wymaga AuthProvider');
  return ctx;
}
