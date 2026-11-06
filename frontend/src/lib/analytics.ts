/** Google Analytics 4 ladowany dopiero po zgodzie uzytkownika (RODO / prawo telekomunikacyjne). */
export const GA_ID = 'G-YQLWF6WMEH';
const KEY = 'turysta_analytics';

export type Consent = 'granted' | 'denied' | null;

declare global {
  interface Window {
    dataLayer?: unknown[];
    gtag?: (...args: unknown[]) => void;
  }
}

function today(): string {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/** Zgoda jest zapamietana na stale; odmowa tylko do konca dnia, potem pytamy ponownie. */
export function getConsent(): Consent {
  try {
    const v = window.localStorage.getItem(KEY);
    if (v === 'granted') return 'granted';
    if (v === `denied:${today()}`) return 'denied';
    return null;
  } catch {
    return null;
  }
}

export function setConsent(value: Exclude<Consent, null>): void {
  try {
    window.localStorage.setItem(KEY, value === 'granted' ? 'granted' : `denied:${today()}`);
  } catch {
    /* brak dostepu do pamieci przegladarki - zgoda dziala tylko do konca karty */
  }
  if (value === 'granted') {
    loadAnalytics();
  } else {
    window.gtag?.('consent', 'update', { analytics_storage: 'denied' });
    disableAnalytics();
  }
}

let loaded = false;

/** Dolacza skrypt gtag.js i konfiguruje GA bez automatycznego page_view (wysylamy go sami przy zmianie trasy). */
export function loadAnalytics(): void {
  if (loaded || typeof document === 'undefined') return;
  loaded = true;
  window.dataLayer = window.dataLayer || [];
  // gtag.js rozpoznaje polecenia tylko wtedy, gdy do dataLayer trafia obiekt `arguments` (nie zwykla tablica).
  window.gtag = function gtag() {
    // eslint-disable-next-line prefer-rest-params
    window.dataLayer?.push(arguments);
  };
  window.gtag('js', new Date());
  window.gtag('consent', 'default', { analytics_storage: 'granted', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });
  window.gtag('config', GA_ID, { send_page_view: false });
  const s = document.createElement('script');
  s.async = true;
  s.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
  document.head.appendChild(s);
}

function disableAnalytics(): void {
  (window as unknown as Record<string, unknown>)[`ga-disable-${GA_ID}`] = true;
}

export function trackPageview(path: string): void {
  if (getConsent() !== 'granted') return;
  loadAnalytics();
  window.gtag?.('event', 'page_view', { page_path: path, page_location: window.location.origin + path, page_title: document.title });
}
