import { afterEach, describe, expect, it, vi } from 'vitest';
import { api, ApiError, setCsrfToken } from './client';

function jsonResponse(status: number, body: unknown): Response {
  return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
}

afterEach(() => {
  vi.unstubAllGlobals();
  setCsrfToken(null);
});

describe('api client', () => {
  it('pobiera token CSRF przed zadaniem POST i dodaje naglowek', async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(jsonResponse(200, { csrf_token: 'abc' }))
      .mockResolvedValueOnce(jsonResponse(200, { ok: true }));
    vi.stubGlobal('fetch', fetchMock);

    await api('POST', '/auth/login', { email: 'a@b.pl' });

    expect(fetchMock).toHaveBeenCalledTimes(2);
    const init = fetchMock.mock.calls[1][1] as RequestInit;
    expect((init.headers as Record<string, string>)['X-CSRF-Token']).toBe('abc');
  });

  it('rzuca ApiError z kodem i polami', async () => {
    setCsrfToken('t');
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(
        jsonResponse(422, { error: { code: 'validation_failed', message: 'Popraw pola.', fields: { email: 'zle' } } }),
      ),
    );

    await expect(api('POST', '/auth/register', {})).rejects.toMatchObject({
      status: 422,
      code: 'validation_failed',
      fields: { email: 'zle' },
    });
    await expect(api('POST', '/auth/register', {})).rejects.toBeInstanceOf(ApiError);
  });
});
