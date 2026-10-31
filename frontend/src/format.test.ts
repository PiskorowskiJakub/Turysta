import { describe, expect, it } from 'vitest';
import { formatCoins, formatCoinsFull, formatDuration, formatEndTime, formatPoints, formatPointsFull } from './format';

describe('formatCoins', () => {
  const n = '\u00a0';
  it('do 100 dk pokazuje dwa miejsca po przecinku', () => {
    expect(formatCoins(0)).toBe('0,00');
    expect(formatCoins(3)).toBe('0,03');
    expect(formatCoins(12353)).toBe('123');
    expect(formatCoins(9999)).toBe('99,99');
  });
  it('od 100 dk pokazuje calosci (w dol) ze spacjami', () => {
    expect(formatCoins(10000)).toBe('100');
    expect(formatCoins(24799)).toBe('247');
    expect(formatCoins(123400)).toBe(`1${n}234`);
    expect(formatCoins(9999999)).toBe(`99${n}999`);
  });
  it('od 100 tys. dk skraca do tys., mln, mld', () => {
    expect(formatCoins(12345600)).toBe(`123,4${n}tys.`);
    expect(formatCoins(10000000)).toBe(`100${n}tys.`);
    expect(formatCoins(123456789)).toBe(`1,2${n}mln`);
    expect(formatCoins(350000000000)).toBe(`3,5${n}mld`);
  });
});

describe('formatCoinsFull', () => {
  it('pokazuje pelna wartosc ze separatorami', () => {
    const n = '\u00a0';
    expect(formatCoinsFull(3)).toBe('0,03');
    expect(formatCoinsFull(123456789)).toBe(`1${n}234${n}567,89`);
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

describe('formatPoints', () => {
  const n = '\u00a0';
  it('do 100 tys. pokazuje cale punkty ze spacjami', () => {
    expect(formatPoints(0)).toBe('0');
    expect(formatPoints(999)).toBe('999');
    expect(formatPoints(1234)).toBe(`1${n}234`);
    expect(formatPoints(99999)).toBe(`99${n}999`);
  });
  it('od 100 tys. skraca do tys., mln, mld (w dol)', () => {
    expect(formatPoints(123456)).toBe(`123,4${n}tys.`);
    expect(formatPoints(100000)).toBe(`100${n}tys.`);
    expect(formatPoints(1250000)).toBe(`1,2${n}mln`);
    expect(formatPoints(3500000000)).toBe(`3,5${n}mld`);
  });
  it('pelna wartosc ze spacjami', () => {
    expect(formatPointsFull(1234567)).toBe(`1${n}234${n}567`);
  });
});
