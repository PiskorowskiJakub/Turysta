INSERT INTO avatars (name, gender, image_url) VALUES
 ('Wędrowniczka', 'f', '/img/avatars/f1.png'),
 ('Podróżniczka', 'f', '/img/avatars/f2.png'),
 ('Wędrowiec', 'm', '/img/avatars/m1.png'),
 ('Podróżnik', 'm', '/img/avatars/m2.png'),
 ('Turysta', 'x', '/img/avatars/x1.png'),
 ('Turystka', 'x', '/img/avatars/x2.png');

INSERT INTO skill_definitions (code, name, description, base_cost_cents, cost_growth, base_seconds, time_growth) VALUES
 ('fitness', 'Kondycja', 'Wytrzymałość na długich trasach.', 619, 1.1800, 1908, 1.1200),
 ('orientation', 'Orientacja w terenie', 'Czytanie map i szukanie drogi.', 619, 1.1800, 1908, 1.1200),
 ('trade', 'Handel', 'Targowanie się i dobre zakupy.', 619, 1.1800, 1908, 1.1200);

INSERT INTO job_definitions (code, name, description, duration_seconds, reward_cents, cooldown_seconds, cancel_policy) VALUES
 ('market', 'Rynek', 'Praca na rynku: 8 godzin, zarobek 247 monet.', 28800, 24700, 3600, 'proportional');

INSERT INTO worlds (ordinal, name) VALUES (1, 'Nowy Początek');
INSERT INTO chapters (world_id, ordinal, name, story_text) VALUES
 (1, 1, 'Pierwsze kroki', 'Twoja pierwsza wyprawa dopiero się zaczyna.');

INSERT INTO daily_facts (text) VALUES
 ('Najdłuższy szlak turystyczny w Polsce, Główny Szlak Beskidzki, liczy ponad 500 km.');
