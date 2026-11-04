import { Fragment, type ReactNode } from 'react';

/**
 * Maly renderer Markdownu dla wiki gry (docs/README.md): naglowki #-###, akapity, listy (- i 1., takze zagniezdzone wcieciem), tabele z wyrownaniem,
 * pogrubienie, kod w odwrotnych apostrofach i linki. Buduje elementy Reacta (bez HTML z tekstu), wiec jest bezpieczny.
 * Linki wzgledne do plikow repozytorium (np. ../README.md) pokazujemy jako zwykly tekst.
 */

type Align = 'left' | 'right' | 'center';

/** Identyfikator naglowka jak na GitHubie: male litery, bez znakow specjalnych, spacje na myslniki. */
export function slugify(text: string): string {
  return text
    .toLowerCase()
    .replace(/[^\p{L}\p{N}\s-]/gu, '')
    .trim()
    .replace(/\s+/g, '-');
}

const INLINE = /(\*\*[^*]+\*\*|`[^`]+`|\[[^\]]+\]\([^)]+\))/g;

function inline(text: string, keyBase: string): ReactNode[] {
  return text.split(INLINE).map((part, i) => {
    const key = `${keyBase}-${i}`;
    if (part.startsWith('**') && part.endsWith('**') && part.length > 4) return <strong key={key}>{inline(part.slice(2, -2), key)}</strong>;
    if (part.startsWith('`') && part.endsWith('`') && part.length > 2) return <code key={key}>{part.slice(1, -1)}</code>;
    const link = /^\[([^\]]+)\]\(([^)]+)\)$/.exec(part);
    if (link) {
      const [, label, href] = link;
      if (href.startsWith('#')) return <a key={key} href={href}>{label}</a>;
      if (/^https?:\/\//.test(href)) return <a key={key} href={href} target="_blank" rel="noopener noreferrer">{label}</a>;
      return <Fragment key={key}>{label}</Fragment>;
    }
    return <Fragment key={key}>{part}</Fragment>;
  });
}

function cells(line: string): string[] {
  return line.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map((c) => c.trim());
}

function alignOf(sep: string): Align {
  const s = sep.trim();
  if (s.startsWith(':') && s.endsWith(':')) return 'center';
  return s.endsWith(':') ? 'right' : 'left';
}

const isTableSep = (line: string | undefined): boolean => line !== undefined && /^\s*\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)*\|?\s*$/.test(line) && line.includes('|');

const unordered = /^\s*[-*]\s+/;
const ordered = /^\s*\d+\.\s+/;

interface ListItem {
  indent: number;
  ordered: boolean;
  text: string;
  children: ListItem[];
}

/** Lista z zagniezdzeniem (wciecie podpunktow); rodzaj listy (punktowana/numerowana) wynika z pierwszego elementu. */
function renderList(items: ListItem[], keyBase: string): ReactNode {
  const li = items.map((it, k) => (
    <li key={k}>
      {inline(it.text, `${keyBase}i${k}`)}
      {it.children.length > 0 && renderList(it.children, `${keyBase}i${k}c`)}
    </li>
  ));
  return items[0].ordered ? <ol key={keyBase}>{li}</ol> : <ul key={keyBase}>{li}</ul>;
}

export function parseMarkdown(source: string): ReactNode[] {
  const lines = source.replace(/\r\n/g, '\n').split('\n');
  const out: ReactNode[] = [];
  let i = 0;
  let n = 0;
  const key = () => `b${n++}`;

  while (i < lines.length) {
    const line = lines[i];
    if (line.trim() === '') {
      i += 1;
      continue;
    }

    const heading = /^(#{1,3})\s+(.*)$/.exec(line);
    if (heading) {
      const level = heading[1].length;
      const text = heading[2].trim();
      const id = slugify(text);
      const content = inline(text, `h${n}`);
      const Tag = (`h${level}`) as 'h1' | 'h2' | 'h3';
      out.push(<Tag key={key()} id={id}>{content}</Tag>);
      i += 1;
      continue;
    }

    if (line.trim().startsWith('```')) {
      const code: string[] = [];
      i += 1;
      while (i < lines.length && !lines[i].trim().startsWith('```')) {
        code.push(lines[i]);
        i += 1;
      }
      i += 1;
      out.push(<pre key={key()}><code>{code.join('\n')}</code></pre>);
      continue;
    }

    if (line.trim().startsWith('|') && isTableSep(lines[i + 1])) {
      const head = cells(line);
      const aligns = cells(lines[i + 1]).map(alignOf);
      const rows: string[][] = [];
      i += 2;
      while (i < lines.length && lines[i].trim().startsWith('|')) {
        rows.push(cells(lines[i]));
        i += 1;
      }
      const tk = key();
      out.push(
        <div className="table-scroll" key={tk}>
          <table className="ranking-table wiki-table">
            <thead>
              <tr>{head.map((h, c) => <th key={c} style={{ textAlign: aligns[c] }}>{inline(h, `${tk}h${c}`)}</th>)}</tr>
            </thead>
            <tbody>
              {rows.map((r, ri) => (
                <tr key={ri}>{head.map((_, c) => <td key={c} style={{ textAlign: aligns[c] }}>{inline(r[c] ?? '', `${tk}r${ri}c${c}`)}</td>)}</tr>
              ))}
            </tbody>
          </table>
        </div>,
      );
      continue;
    }

    if (unordered.test(line) || ordered.test(line)) {
      const roots: ListItem[] = [];
      const stack: ListItem[] = [];
      while (i < lines.length && (unordered.test(lines[i]) || ordered.test(lines[i]))) {
        const raw = lines[i];
        const isOrdered = ordered.test(raw);
        const item: ListItem = { indent: raw.length - raw.trimStart().length, ordered: isOrdered, text: raw.replace(isOrdered ? ordered : unordered, ''), children: [] };
        while (stack.length > 0 && stack[stack.length - 1].indent >= item.indent) stack.pop();
        if (stack.length > 0) stack[stack.length - 1].children.push(item);
        else roots.push(item);
        stack.push(item);
        i += 1;
      }
      out.push(renderList(roots, key()));
      continue;
    }

    // Akapit: kolejne niepuste linie, ktore nie zaczynaja innego bloku.
    const para: string[] = [line.trim()];
    i += 1;
    while (i < lines.length && lines[i].trim() !== '' && !/^(#{1,3}\s|```)/.test(lines[i]) && !lines[i].trim().startsWith('|') && !unordered.test(lines[i]) && !ordered.test(lines[i])) {
      para.push(lines[i].trim());
      i += 1;
    }
    const pk = key();
    out.push(<p key={pk}>{inline(para.join(' '), pk)}</p>);
  }
  return out;
}

export default function Markdown({ source }: { source: string }) {
  return <>{parseMarkdown(source)}</>;
}
