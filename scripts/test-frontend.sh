#!/usr/bin/env bash
# Typecheck i testy frontendu. Zaleznosci instalujemy tylko, gdy ich brakuje.
set -euo pipefail
cd "$(dirname "$0")/../frontend"

[ -x node_modules/.bin/tsc ] || npm ci
npm run typecheck
npm test
