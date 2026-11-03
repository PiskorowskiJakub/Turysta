import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

// Dev: serwer Vite proxy'uje /api do backendu (to samo pochodzenie, wiec ciasteczka sesji dzialaja).
const apiTarget = process.env.API_PROXY_TARGET ?? 'http://localhost';

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    // Wiki gry importuje ../docs/README.md spoza katalogu frontendu (w kontenerze dev: /docs).
    fs: { allow: ['.', '../docs', '/docs'] },
    watch: { usePolling: process.env.VITE_POLLING === 'true' },
    proxy: { '/api': { target: apiTarget, changeOrigin: true } },
  },
  test: { environment: 'node' },
});
