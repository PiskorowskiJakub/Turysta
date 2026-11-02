// Po buildzie Vite: renderuje strone glowna (wersja dla niezalogowanych) do statycznego HTML i zapisuje jako dist/landing.html.
// Wyszukiwarki i nowi gracze dostaja od razu tresc, tytul, opis, dane Open Graph i JSON-LD, a React podmienia ja po zaladowaniu.
// Zwykly dist/index.html (pusta powloka aplikacji) zostaje dla wszystkich pozostalych adresow.
import { readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const site = (process.env.SITE_URL ?? 'https://dwadukaty.pl').replace(/\/+$/, '');

const pl = JSON.parse(await readFile(path.join(root, 'src/locales/pl.json'), 'utf8'));
const seo = pl.seo;
if (!seo?.title || !seo?.description) throw new Error('Prerender: brak tekstow seo.title / seo.description w locales/pl.json');

const { render } = await import(pathToFileURL(path.join(root, 'dist-ssr/entry-server.js')).href);
const template = await readFile(path.join(root, 'dist/index.html'), 'utf8');

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const app = render('/');
for (const needle of ['<h1', 'class="landing"', 'href="/rejestracja"']) {
  if (!app.includes(needle)) throw new Error(`Prerender: w wyrenderowanej stronie brakuje: ${needle}`);
}

const patch = (html, pattern, replacement, what) => {
  if (!pattern.test(html)) throw new Error(`Prerender: nie znaleziono ${what} w dist/index.html`);
  return html.replace(pattern, () => replacement);
};

const jsonLd = {
  '@context': 'https://schema.org',
  '@type': 'VideoGame',
  name: 'Dwa Dukaty',
  url: `${site}/`,
  description: seo.description,
  inLanguage: 'pl',
  applicationCategory: 'GameApplication',
  operatingSystem: 'Dowolny (przeglądarka internetowa)',
  gamePlatform: 'Przeglądarka internetowa',
  genre: ['Gra przeglądarkowa', 'Gra przygodowa'],
  image: `${site}/img/og.png`,
  offers: { '@type': 'Offer', price: '0', priceCurrency: 'PLN' },
};

const headExtra = [
  `<link rel="canonical" href="${site}/" />`,
  '<meta name="robots" content="index, follow, max-image-preview:large" />',
  '<meta property="og:type" content="website" />',
  '<meta property="og:site_name" content="Dwa Dukaty" />',
  '<meta property="og:locale" content="pl_PL" />',
  `<meta property="og:title" content="${esc(seo.title)}" />`,
  `<meta property="og:description" content="${esc(seo.description)}" />`,
  `<meta property="og:url" content="${site}/" />`,
  `<meta property="og:image" content="${site}/img/og.png" />`,
  '<meta property="og:image:width" content="1200" />',
  '<meta property="og:image:height" content="630" />',
  `<meta property="og:image:alt" content="${esc(seo.imageAlt)}" />`,
  '<meta name="twitter:card" content="summary_large_image" />',
  `<meta name="twitter:title" content="${esc(seo.title)}" />`,
  `<meta name="twitter:description" content="${esc(seo.description)}" />`,
  `<meta name="twitter:image" content="${site}/img/og.png" />`,
  `<script type="application/ld+json">${JSON.stringify(jsonLd).replace(/</g, '\\u003c')}</script>`,
].join('\n    ');

let html = template;
html = patch(html, /<title>[^<]*<\/title>/, `<title>${esc(seo.title)}</title>`, '<title>');
html = patch(html, /<meta name="description"[^>]*>/, `<meta name="description" content="${esc(seo.description)}" />`, 'meta description');
html = patch(html, /<div id="root"><\/div>/, `<div id="root">${app}</div>`, 'kontenera #root');
html = patch(html, /<\/head>/, `    ${headExtra}\n  </head>`, '</head>');

await writeFile(path.join(root, 'dist/landing.html'), html);
console.log(`Prerender: dist/landing.html (${Math.round(html.length / 1024)} KB)`);
