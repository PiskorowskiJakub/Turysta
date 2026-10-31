ALTER TABLE training_queue ADD COLUMN duration_seconds INT UNSIGNED NOT NULL DEFAULT 0 AFTER cost_cents;

INSERT INTO job_definitions (code, name, description, duration_seconds, reward_cents, cooldown_seconds, cancel_policy) VALUES
 ('hostel', 'Dyżur w schronisku', 'Dyżur w schronisku: 12 godzin, zarobek 420 monet. Przerwanie oznacza brak wypłaty.', 43200, 42000, 7200, 'none'),
 ('expedition', 'Całodobowa wyprawa', 'Wyprawa przez cały dzień i noc: 24 godziny, zarobek 900 monet. Przerwanie oznacza brak wypłaty.', 86400, 90000, 14400, 'none');
