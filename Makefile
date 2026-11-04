.PHONY: test test-backend test-frontend

test: test-backend test-frontend

test-backend:
	scripts/test-backend.sh

test-frontend:
	scripts/test-frontend.sh
