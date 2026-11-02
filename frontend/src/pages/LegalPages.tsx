import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

interface LegalDoc {
  title: string;
  date: string;
  sections: { h: string; p: string[] }[];
}

function LegalPage({ kind }: { kind: 'terms' | 'privacy' }) {
  const { t } = useTranslation();
  const doc = t(`legal.${kind}`, { returnObjects: true }) as unknown as LegalDoc;
  return (
    <article className="card legal">
      <h1>{doc.title}</h1>
      <p className="muted">{t('legal.updated', { date: doc.date })}</p>
      {doc.sections.map((s) => (
        <section key={s.h}>
          <h2>{s.h}</h2>
          {s.p.map((text) => <p key={text}>{text}</p>)}
        </section>
      ))}
      <p><Link to="/">{t('legal.backHome')}</Link></p>
    </article>
  );
}

export function TermsPage() {
  return <LegalPage kind="terms" />;
}

export function PrivacyPage() {
  return <LegalPage kind="privacy" />;
}
