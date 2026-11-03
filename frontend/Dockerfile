# syntax=docker/dockerfile:1

# Kontekst budowania to katalog glowny repozytorium (docker-compose: context: ., dockerfile: frontend/Dockerfile),
# bo wiki gry czyta docs/README.md. Co wchodzi do kontekstu, okresla frontend/Dockerfile.dockerignore.

# ---- Etap 1: zaleznosci ----
FROM node:20-alpine AS deps
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# ---- Etap 2: build (Vite) ----
FROM node:20-alpine AS build
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY frontend/ ./
# docs/ leży obok katalogu aplikacji tak samo jak w repozytorium: /app/src/pages/../../../docs = /docs
COPY docs /docs
RUN npm run typecheck && npm run build

# ---- Etap 3: produkcja (nginx serwuje statyczny build) ----
FROM nginx:1.27-alpine AS production
COPY frontend/nginx.conf /etc/nginx/conf.d/default.conf
COPY frontend/security-headers.inc /etc/nginx/security-headers.inc
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
HEALTHCHECK --interval=10s --timeout=3s --retries=3 --start-period=5s --start-interval=2s \
  CMD wget -qO- http://127.0.0.1/ >/dev/null || exit 1
CMD ["nginx", "-g", "daemon off;"]
