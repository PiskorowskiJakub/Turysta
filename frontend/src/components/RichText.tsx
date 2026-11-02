/**
 * Prosty tekst z paragrafami i listami (bez HTML): pusta linia oddziela akapity,
 * linie zaczynajace sie od "- " tworza liste, a "## " na poczatku akapitu to podtytul.
 */
export default function RichText({ text }: { text: string }) {
  const blocks = text.replace(/\r\n/g, '\n').split(/\n{2,}/).filter((b) => b.trim() !== '');
  const bullet = /^\s*[-•]\s+/;
  return (
    <>
      {blocks.map((block, i) => {
        const lines = block.split('\n');
        if (lines.every((l) => bullet.test(l))) {
          return <ul key={i}>{lines.map((l, j) => <li key={j}>{l.replace(bullet, '')}</li>)}</ul>;
        }
        if (/^##\s+/.test(block)) {
          return <h3 key={i}>{block.replace(/^##\s+/, '')}</h3>;
        }
        return (
          <p key={i}>
            {lines.map((l, j) => (
              <span key={j}>{j > 0 && <br />}{l}</span>
            ))}
          </p>
        );
      })}
    </>
  );
}
