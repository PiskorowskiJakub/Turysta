-- Brak przerwy po ukonczeniu pracy: rynek (8 h), dyzur w schronisku (12 h) i wyprawa (24 h).
-- Po ukonczeniu mozna od razu zaczac kolejna prace (przerwanie rynku tez nie uruchamia przerwy).
UPDATE job_definitions SET cooldown_seconds = 0 WHERE code IN ('market', 'hostel', 'expedition');
