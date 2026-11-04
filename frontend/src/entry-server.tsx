import { renderToString } from 'react-dom/server';
import { StaticRouter } from 'react-router-dom/server';
import './i18n';
import App from './App';
import { AuthContext, type AuthState } from './auth/AuthContext';

/** Stan "niezalogowany" bez zadnych wywolan sieciowych: tak wyglada strona glowna dla wyszukiwarek i nowych graczy. */
const anon: AuthState = {
  status: 'anon',
  me: null,
  login: async () => {
    throw new Error('Niedostepne podczas prerenderowania');
  },
  register: async () => undefined,
  logout: async () => undefined,
  refresh: async () => undefined,
  saveProfile: async () => undefined,
  updateAccountProfile: async () => undefined,
  updatePrivacy: async () => undefined,
  changeName: async () => undefined,
  changeEmail: async () => undefined,
  changePassword: async () => undefined,
  resendVerification: async () => undefined,
  deleteAccount: async () => undefined,
};

/** Zwraca HTML aplikacji dla wskazanego adresu (uzywane tylko przy budowaniu, przez scripts/prerender.mjs). */
export function render(url: string): string {
  return renderToString(
    <StaticRouter location={url}>
      <AuthContext.Provider value={anon}>
        <App />
      </AuthContext.Provider>
    </StaticRouter>,
  );
}
