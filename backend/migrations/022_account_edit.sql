-- Zmiana plci postaci jest ograniczona do raz na 30 dni; zapisujemy chwile ostatniej zmiany (NULL = jeszcze nie zmieniano po samouczku).
ALTER TABLE users ADD COLUMN gender_changed_at DATETIME NULL AFTER gender;
