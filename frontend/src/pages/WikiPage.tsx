import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
// Jedno zrodlo prawdy: ten sam plik czyta GitHub i ta strona (docs/README.md), wiec zasady gry nie rozjada sie z wiki.
import wikiSource from '../../../docs/README.md?raw';
import Markdown from '../lib/Markdown';

/** Wiki gry: tresc generowana z docs/README.md przy budowaniu strony. */
export default function WikiPage() {
  const { t } = useTranslation();
  const { hash } = useLocation();

  // Przejscie z linku z hashem (np. /wiki#odznaki) do wlasciwego naglowka po wyrenderowaniu tresci.
  useEffect(() => {
    if (!hash) return;
    const el = document.getElementById(decodeURIComponent(hash.slice(1)));
    el?.scrollIntoView();
  }, [hash]);

  return (
    <article className="panel wiki" aria-label={t('wiki.title')}>
      <Markdown source={wikiSource} />
    </article>
  );
}
