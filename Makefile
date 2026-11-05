.PHONY: test test-backend test-frontend squad-grant

test: test-backend test-frontend

test-backend:
	scripts/test-backend.sh

test-frontend:
	scripts/test-frontend.sh

# Lokalnie: dosyp skladniki schroniska oddzialowi, np. make squad-grant SQUAD=1 EMBER=5000 STAMPS=50 COINS=20000
squad-grant:
	scripts/dev-grant-squad.sh $(SQUAD) --ember=$(or $(EMBER),0) --stamps=$(or $(STAMPS),0) --coins=$(or $(COINS),0)
