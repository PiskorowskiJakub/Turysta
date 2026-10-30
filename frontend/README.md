# Turysta

Aplikacja webowa gry Turysta (React 18, Create React App, React Router 6, SASS).

## Uruchomienie w Dockerze

Wymagany jest Docker z pluginem Compose. W folderze `Web/Turysta`:

```bash
# Produkcja (nginx) -> http://localhost:8080
docker compose up --build

# Development z hot reload -> http://localhost:3000
docker compose --profile dev up --build web-dev
```

Porty można zmienić w pliku `.env` (wzór: `.env.example`).

Samo budowanie obrazu produkcyjnego:

```bash
docker build -t turysta-web .
docker run --rm -p 8080:80 turysta-web
```

## Uruchomienie bez Dockera

Wymagany Node.js 20 (min. 18).

```bash
npm ci
npm start       # http://localhost:3000
npm test
npm run build   # wynik w folderze build/
```

## Struktura

```
src/
  App.js, index.js
  pages/
    Layout.js            routing i układ strony
    Page*.js             podstrony (Home, Blogs, Contact, 404)
    Component/           komponenty (Info, Profil)
    styles/              SCSS (Layout, Responsive, _base ze zmiennymi)
    img/layout/          grafiki układu
```

Style SCSS są importowane bezpośrednio w `Layout.js` i kompilowane przez
Create React App - nie trzeba osobnego kroku kompilacji.
