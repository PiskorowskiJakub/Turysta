export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    message: string,
    public readonly fields: Record<string, string> = {},
  ) {
    super(message);
  }
}

const BASE = '/api/v1';
let csrfToken: string | null = null;

export function setCsrfToken(token: string | null): void {
  csrfToken = token;
}

interface ErrorBody {
  error?: { code?: string; message?: string; fields?: Record<string, string> };
}

async function send<T>(method: string, path: string, body?: unknown): Promise<T> {
  const headers: Record<string, string> = {};
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  if (method !== 'GET' && csrfToken) headers['X-CSRF-Token'] = csrfToken;

  const res = await fetch(BASE + path, {
    method,
    credentials: 'same-origin',
    headers,
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  let data: unknown = null;
  try {
    data = await res.json();
  } catch {
    data = null;
  }

  if (!res.ok) {
    const err = (data as ErrorBody | null)?.error;
    throw new ApiError(res.status, err?.code ?? 'unknown', err?.message ?? 'Wystąpił błąd.', err?.fields ?? {});
  }
  return data as T;
}

async function ensureCsrf(): Promise<void> {
  if (csrfToken) return;
  const r = await send<{ csrf_token: string }>('GET', '/auth/csrf');
  csrfToken = r.csrf_token;
}

/** Wywolanie API. Dla zadan zmieniajacych dane pobiera token CSRF i ponawia raz, gdy wygasl. */
export async function api<T>(method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE', path: string, body?: unknown): Promise<T> {
  if (method !== 'GET') await ensureCsrf();
  try {
    return await send<T>(method, path, body);
  } catch (e) {
    if (e instanceof ApiError && e.code === 'csrf_invalid') {
      csrfToken = null;
      await ensureCsrf();
      return send<T>(method, path, body);
    }
    throw e;
  }
}
