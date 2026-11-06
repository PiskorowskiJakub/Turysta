/**
 * Emblematy oddzialu (Izba pamieci): wewnetrzne znaczniki SVG w siatce 64x64, rysowane kolorem currentColor.
 * Im wyzszy poziom odblokowujacy, tym bardziej szczegolowy rysunek.
 */
export const EMBLEM_CODES = ['compass', 'flame', 'peak', 'tent', 'pine', 'lantern', 'boot', 'eagle', 'scroll', 'stag', 'tower', 'phoenix'] as const;
export type EmblemCode = (typeof EMBLEM_CODES)[number];

const S = 'fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"';
const F = 'fill="currentColor" stroke="none"';
const FA = 'fill="currentColor" fill-opacity=".28" stroke="none"';

export const EMBLEM_SVG: Record<EmblemCode, string> = {
  // --- poziom 1: proste znaki
  compass: `<g ${S}><circle cx="32" cy="32" r="21"/></g><path ${F} d="M32 15 38 32 32 49 26 32Z" fill-opacity=".9"/><circle cx="32" cy="32" r="2.6" fill="var(--emblem-bg,#102018)"/>`,
  flame: `<path ${S} d="M32 10C34 22 46 27 45 40c-1 8-7 13-13 13s-12-5-13-13c-1-9 6-13 8-20 2 3 3 5 4 7 2-4 3-9 1-17Z"/>`,
  peak: `<g ${S}><path d="M8 50 25 21l9 15 6-9 16 23Z"/></g>`,

  // --- poziom 3: wiecej elementow
  tent: `<g ${S}><path d="M8 50 32 16l24 34Z"/><path d="M32 16v34M32 50 24 50 32 34 40 50"/><path d="M5 50h54"/></g><path ${FA} d="M32 34 40 50H24Z"/>`,
  pine: `<g ${S}><path d="M32 8 20 26h8L16 40h10L12 54h40L38 40h10L36 26h8Z"/><path d="M32 54v5"/></g><path ${FA} d="M32 8 44 26h-8l12 14H38l14 14H32Z"/>`,

  // --- poziom 6: detale i swiatlo
  lantern: `<g ${S}><path d="M26 14c0-4 12-4 12 0"/><path d="M22 18h20l3 6H19Z"/><path d="M19 24h26v22H19Z"/><path d="M22 50h20l2 5H20Z"/><path d="M25 24v22M39 24v22"/></g><path ${F} d="M32 29c3 4 6 6 5 10-1 3-3 4-5 4s-4-1-5-4c-1-4 2-6 5-10Z"/><g ${S} stroke-width="1.8"><path d="M9 28l5 2M9 38l5-2M55 28l-5 2M55 38l-5-2"/></g>`,
  boot: `<g ${S}><path d="M22 10h14l1 24c0 4 5 6 10 8 6 2 9 4 9 9v3H14v-8Z"/><path d="M14 47h42M22 18h14M22 25h14M22 32h12"/><path d="M14 40l8-4"/></g><path ${FA} d="M14 47h42v5H14Z"/><g ${F}><circle cx="24" cy="50" r="1.1"/><circle cx="31" cy="50" r="1.1"/><circle cx="38" cy="50" r="1.1"/><circle cx="45" cy="50" r="1.1"/></g>`,

  // --- poziom 10: bogate rysunki
  eagle: `<g ${S}><path d="M28 28C20 22 11 22 5 29l5 1-3 5 6-1-2 5 7-3-1 5 7-5 4-3"/><path d="M36 28C44 22 53 22 59 29l-5 1 3 5-6-1 2 5-7-3 1 5-7-5-4-3"/><path d="M28 27h8l1 9-5 8-5-8Z"/><path d="M27 44l-3 12 8-4 8 4-3-12"/><circle cx="32" cy="20" r="5"/><path d="M36.500 19.500 42 22l-5.500 2.200"/></g><path ${FA} d="M28 27h8l1 9-5 8-5-8Z"/><circle cx="33.500" cy="19" r="1.200" ${F}/><g ${S} stroke-width="1.5"><path d="M32 31v8M29 36l3 4 3-4M30 50l2 2 2-2"/></g>`,
  scroll: `<g ${S}><path d="M14 14h36v36H14Z"/><path d="M10 14a4 4 0 0 1 8 0M46 14a4 4 0 0 1 8 0M10 50a4 4 0 0 0 8 0M46 50a4 4 0 0 0 8 0"/><path d="M10 14v36M54 14v36"/></g><path ${FA} d="M14 14h36v36H14Z"/><g ${S} stroke-width="1.8"><path d="M18 42l8-10 5 6 6-8 9 12"/><path d="M20 24c6 2 8-4 14-2s6 6 12 4" stroke-dasharray="1 4"/></g><g ${S} stroke-width="2.2"><path d="M41 22l6 6M47 22l-6 6"/></g>`,

  // --- poziom 15: bardzo szczegolowe
  stag: `<g ${S}><path d="M26 30c-2-4-4-6-4-10M22 20l-5-3M22 20l1-7M24 25l-6 0M38 30c2-4 4-6 4-10M42 20l5-3M42 20l-1-7M40 25l6 0"/><path d="M26 30c0 2 1 4 2 4h8c1 0 2-2 2-4"/><path d="M28 34l-2 8c0 6 3 10 6 12 3-2 6-6 6-12l-2-8"/><path d="M26 31l-6-2 2 5 5 1M38 31l6-2-2 5-5 1"/></g><circle cx="29" cy="39" r="1.3" ${F}/><circle cx="35" cy="39" r="1.3" ${F}/><path ${F} d="M30 48h4l-2 3Z"/><path ${FA} d="M28 34h8l2 8c0 6-3 10-6 12-3-2-6-6-6-12Z"/><g ${S} stroke-width="1.6"><path d="M18 17l-3-1M17 22l-4 0M46 17l3-1M47 22l4 0"/></g>`,
  tower: `<g ${S}><path d="M20 54V24h24v30Z"/><path d="M16 24v-8h6v4h4v-4h6v4h4v-4h6v4h4v-4h6v8Z" transform="translate(0 0)"/><path d="M16 24h32"/><path d="M12 54h40"/><path d="M28 54V44a4 4 0 0 1 8 0v10"/><path d="M32 16V6l9 3-9 3"/></g><path ${FA} d="M20 24h24v30H20Z"/><g ${S} stroke-width="1.6"><path d="M25 31v5M39 31v5M32 29v6"/><path d="M20 40h24M20 47h4M28 47h6M38 47h6M24 40v7M34 40v7"/></g>`,

  // --- poziom 20: najbogatszy
  phoenix: `<g ${S}><path d="M29 36C21 36 11 30 7 14c6 4 10 4 13 4-2 4 2 7 6 7-2 4 1 7 4 7"/><path d="M35 36c8 0 18-6 22-22-6 4-10 4-13 4 2 4-2 7-6 7 2 4-1 7-4 7"/><path d="M29 24c0-5 6-5 6 0l1 12-4 7-4-7Z"/><path d="M32 42c-1 7-5 11-10 16M32 42v16M32 42c1 7 5 11 10 16"/><path d="M32 4c1 4 4 5 3 9-1 2-2 3-3 3s-2-1-3-3c-1-4 2-5 3-9Z"/></g><path ${FA} d="M29 36C21 36 11 30 7 14c6 4 10 4 13 4-2 4 2 7 6 7-2 4 1 7 4 7ZM35 36c8 0 18-6 22-22-6 4-10 4-13 4 2 4-2 7-6 7 2 4-1 7-4 7Z"/><path ${FA} d="M29 24c0-5 6-5 6 0l1 12-4 7-4-7Z"/><circle cx="32" cy="22" r="1.100" ${F}/><g ${S} stroke-width="1.5"><path d="M12 8l2 3M52 8l-2 3M20 4l1 3M44 4l-1 3M32 28v8M30 33l2 3 2-3"/></g>`,
};
