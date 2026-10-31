import { describe, expect, it } from 'vitest';
import { formatCoins, formatDuration, formatEndTime } from './format';

describe('formatCoins', () => {
  it('formatuje setne czesci jako monety z przecinkiem', () => {
    expect(formatCoins(12353)).toBe('123,53');
    expect(formatCoins(0)).toBe('0,00');
    expect(formatCoins(24700)).toBe('247,00');
  });
});

describe('formatDuration', () => {
  it('formatuje czas jako g:mm:ss z dniami', () => {
    expect(formatDuration(0)).toBe('0:00:00');
    expect(formatDuration(1908)).toBe('0:31:48');
    expect(formatDuration(28800)).toBe('8:00:00');
    expect(formatDuration(86400)).toBe('24:00:00');
    expect(formatDuration(86401)).toBe('1 d 0:00:01');
    expect(formatDuration(90061)).toBe('1 d 1:01:01');
    expect(formatDuration(-5)).toBe('0:00:00');
  });
});

describe('formatEndTime', () => {
  const now = new Date(2026, 9, 31, 12, 0).getTime();
  it('pokazuje dzis, jutro i date', () => {
    expect(formatEndTime(new Date(2026, 9, 31, 14, 32).getTime(), now)).toBe('dziś 14:32');
    expect(formatEndTime(new Date(2026, 10, 1, 3, 5).getTime(), now)).toBe('jutro 03:05');
    expect(formatEndTime(new Date(2026, 10, 3, 9, 0).getTime(), now)).toBe('3 lis 09:00');
  });
});
