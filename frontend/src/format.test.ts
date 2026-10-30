import { describe, expect, it } from 'vitest';
import { formatCoins } from './format';

describe('formatCoins', () => {
  it('formatuje setne czesci jako monety z przecinkiem', () => {
    expect(formatCoins(12353)).toBe('123,53');
    expect(formatCoins(0)).toBe('0,00');
    expect(formatCoins(24700)).toBe('247,00');
  });
});
