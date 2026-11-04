import { describe, expect, it } from 'vitest';
import { renderToStaticMarkup } from 'react-dom/server';
import Markdown, { slugify } from './Markdown';

const html = (md: string) => renderToStaticMarkup(<Markdown source={md} />);

describe('Markdown (wiki)', () => {
  it('buduje identyfikatory naglowkow jak GitHub', () => {
    expect(slugify('Tabela awansów (wymagania, czas i koszt)')).toBe('tabela-awansów-wymagania-czas-i-koszt');
  });

  it('renderuje naglowki, listy i pogrubienie', () => {
    const out = html('## Odznaki\n\n- **Brąz** i `kod`\n- drugi\n');
    expect(out).toContain('<h2 id="odznaki">Odznaki</h2>');
    expect(out).toContain('<li><strong>Brąz</strong> i <code>kod</code></li>');
  });

  it('renderuje listy zagniezdzone', () => {
    const out = html('- Oddziały\n  - Role\n  - Ognisko\n- Kronika\n');
    expect(out).toContain('<li>Oddziały<ul><li>Role</li><li>Ognisko</li></ul></li>');
    expect(out).toContain('<li>Kronika</li>');
  });

  it('renderuje tabele z wyrownaniem', () => {
    const out = html('| A | B |\n|---|--:|\n| x | 5 |\n');
    expect(out).toContain('<th style="text-align:left">A</th>');
    expect(out).toContain('<td style="text-align:right">5</td>');
  });

  it('nie wstawia HTML z tekstu i zamienia linki wzgledne na tekst', () => {
    const out = html('<script>x</script> [plik](../README.md#a) [www](https://example.com)');
    expect(out).not.toContain('<script>');
    expect(out).toContain('plik');
    expect(out).not.toContain('../README.md');
    expect(out).toContain('href="https://example.com"');
  });
});
