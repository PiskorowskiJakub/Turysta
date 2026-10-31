-- Po 10 rozdzialow w kazdym swiecie; przeliczone koszty, czasy i wymagania (idempotentne).

INSERT INTO chapters (world_id, ordinal, name, story_text, duration_seconds, cost_cents) VALUES
 ((SELECT id FROM worlds WHERE ordinal = 0), 2, 'Pierwsza ścieżka', 'Rozdział „Pierwsza ścieżka”: kolejny odcinek Twojej wyprawy po łagodnych łąkach i pierwszych szlakach. Przed Tobą nowe szlaki i nowe wyzwania.', 300, 13500),
 ((SELECT id FROM worlds WHERE ordinal = 0), 3, 'Przy ognisku', 'Rozdział „Przy ognisku”: kolejny odcinek Twojej wyprawy po łagodnych łąkach i pierwszych szlakach. Przed Tobą nowe szlaki i nowe wyzwania.', 330, 15255),
 ((SELECT id FROM worlds WHERE ordinal = 0), 4, 'Mapa i kompas', 'Rozdział „Mapa i kompas”: kolejny odcinek Twojej wyprawy po łagodnych łąkach i pierwszych szlakach. Przed Tobą nowe szlaki i nowe wyzwania.', 363, 17238),
 ((SELECT id FROM worlds WHERE ordinal = 0), 5, 'Kamienisty szlak', 'Rozdział „Kamienisty szlak”: kolejny odcinek Twojej wyprawy po łagodnych łąkach i pierwszych szlakach. Przed Tobą nowe szlaki i nowe wyzwania.', 399, 19479),
 ((SELECT id FROM worlds WHERE ordinal = 0), 6, 'Wschód na przełęczy', 'Rozdział „Wschód na przełęczy”: kolejny odcinek Twojej wyprawy po łagodnych łąkach i pierwszych szlakach. Przed Tobą nowe szlaki i nowe wyzwania.', 439, 22011),
 ((SELECT id FROM worlds WHERE ordinal = 0), 7, 'Schronisko na polanie', 'Rozdział „Schronisko na polanie”: kolejny odcinek Twojej wyprawy po łagodnych łąkach i pierwszych szlakach. Przed Tobą nowe szlaki i nowe wyzwania.', 483, 24873),
 ((SELECT id FROM worlds WHERE ordinal = 0), 8, 'Wędrowiec', 'Rozdział „Wędrowiec”: kolejny odcinek Twojej wyprawy po łagodnych łąkach i pierwszych szlakach. Przed Tobą nowe szlaki i nowe wyzwania.', 531, 28106),
 ((SELECT id FROM worlds WHERE ordinal = 0), 9, 'Przełęcz wiatrów', 'Rozdział „Przełęcz wiatrów”: kolejny odcinek Twojej wyprawy po łagodnych łąkach i pierwszych szlakach. Przed Tobą nowe szlaki i nowe wyzwania.', 585, 31760),
 ((SELECT id FROM worlds WHERE ordinal = 0), 10, 'Pierwszy szczyt', 'Rozdział „Pierwszy szczyt”: kolejny odcinek Twojej wyprawy po łagodnych łąkach i pierwszych szlakach. Przed Tobą nowe szlaki i nowe wyzwania.', 643, 35889),
 ((SELECT id FROM worlds WHERE ordinal = 1), 1, 'Leśna ścieżka', 'Rozdział „Leśna ścieżka”: kolejny odcinek Twojej wyprawy po szumiących lasach. Przed Tobą nowe szlaki i nowe wyzwania.', 707, 40555),
 ((SELECT id FROM worlds WHERE ordinal = 1), 2, 'Mech i paprocie', 'Rozdział „Mech i paprocie”: kolejny odcinek Twojej wyprawy po szumiących lasach. Przed Tobą nowe szlaki i nowe wyzwania.', 778, 45827),
 ((SELECT id FROM worlds WHERE ordinal = 1), 3, 'Polana grzybiarzy', 'Rozdział „Polana grzybiarzy”: kolejny odcinek Twojej wyprawy po szumiących lasach. Przed Tobą nowe szlaki i nowe wyzwania.', 856, 51784),
 ((SELECT id FROM worlds WHERE ordinal = 1), 4, 'Strumień w jarze', 'Rozdział „Strumień w jarze”: kolejny odcinek Twojej wyprawy po szumiących lasach. Przed Tobą nowe szlaki i nowe wyzwania.', 942, 58516),
 ((SELECT id FROM worlds WHERE ordinal = 1), 5, 'Stara bukowa knieja', 'Rozdział „Stara bukowa knieja”: kolejny odcinek Twojej wyprawy po szumiących lasach. Przed Tobą nowe szlaki i nowe wyzwania.', 1036, 66123),
 ((SELECT id FROM worlds WHERE ordinal = 1), 6, 'Noc pod gwiazdami', 'Rozdział „Noc pod gwiazdami”: kolejny odcinek Twojej wyprawy po szumiących lasach. Przed Tobą nowe szlaki i nowe wyzwania.', 1139, 74719),
 ((SELECT id FROM worlds WHERE ordinal = 1), 7, 'Wilczy trakt', 'Rozdział „Wilczy trakt”: kolejny odcinek Twojej wyprawy po szumiących lasach. Przed Tobą nowe szlaki i nowe wyzwania.', 1253, 84433),
 ((SELECT id FROM worlds WHERE ordinal = 1), 8, 'Serce lasu', 'Rozdział „Serce lasu”: kolejny odcinek Twojej wyprawy po szumiących lasach. Przed Tobą nowe szlaki i nowe wyzwania.', 1378, 95409),
 ((SELECT id FROM worlds WHERE ordinal = 1), 9, 'Wąwóz mgieł', 'Rozdział „Wąwóz mgieł”: kolejny odcinek Twojej wyprawy po szumiących lasach. Przed Tobą nowe szlaki i nowe wyzwania.', 1516, 107812),
 ((SELECT id FROM worlds WHERE ordinal = 1), 10, 'Strażnik puszczy', 'Rozdział „Strażnik puszczy”: kolejny odcinek Twojej wyprawy po szumiących lasach. Przed Tobą nowe szlaki i nowe wyzwania.', 1668, 121828),
 ((SELECT id FROM worlds WHERE ordinal = 2), 1, 'Mroźny wiatr', 'Rozdział „Mroźny wiatr”: kolejny odcinek Twojej wyprawy po mroźnej tundrze. Przed Tobą nowe szlaki i nowe wyzwania.', 1835, 137665),
 ((SELECT id FROM worlds WHERE ordinal = 2), 2, 'Śnieżny szlak', 'Rozdział „Śnieżny szlak”: kolejny odcinek Twojej wyprawy po mroźnej tundrze. Przed Tobą nowe szlaki i nowe wyzwania.', 2018, 155562),
 ((SELECT id FROM worlds WHERE ordinal = 2), 3, 'Renifery na horyzoncie', 'Rozdział „Renifery na horyzoncie”: kolejny odcinek Twojej wyprawy po mroźnej tundrze. Przed Tobą nowe szlaki i nowe wyzwania.', 2220, 175785),
 ((SELECT id FROM worlds WHERE ordinal = 2), 4, 'Zorza polarna', 'Rozdział „Zorza polarna”: kolejny odcinek Twojej wyprawy po mroźnej tundrze. Przed Tobą nowe szlaki i nowe wyzwania.', 2442, 198637),
 ((SELECT id FROM worlds WHERE ordinal = 2), 5, 'Lodowa rzeka', 'Rozdział „Lodowa rzeka”: kolejny odcinek Twojej wyprawy po mroźnej tundrze. Przed Tobą nowe szlaki i nowe wyzwania.', 2686, 224459),
 ((SELECT id FROM worlds WHERE ordinal = 2), 6, 'Obóz w zaspach', 'Rozdział „Obóz w zaspach”: kolejny odcinek Twojej wyprawy po mroźnej tundrze. Przed Tobą nowe szlaki i nowe wyzwania.', 2955, 253639),
 ((SELECT id FROM worlds WHERE ordinal = 2), 7, 'Wicher na przełęczy', 'Rozdział „Wicher na przełęczy”: kolejny odcinek Twojej wyprawy po mroźnej tundrze. Przed Tobą nowe szlaki i nowe wyzwania.', 3250, 286612),
 ((SELECT id FROM worlds WHERE ordinal = 2), 8, 'Biała cisza', 'Rozdział „Biała cisza”: kolejny odcinek Twojej wyprawy po mroźnej tundrze. Przed Tobą nowe szlaki i nowe wyzwania.', 3575, 323872),
 ((SELECT id FROM worlds WHERE ordinal = 2), 9, 'Szron i cisza', 'Rozdział „Szron i cisza”: kolejny odcinek Twojej wyprawy po mroźnej tundrze. Przed Tobą nowe szlaki i nowe wyzwania.', 3933, 365975),
 ((SELECT id FROM worlds WHERE ordinal = 2), 10, 'Koło podbiegunowe', 'Rozdział „Koło podbiegunowe”: kolejny odcinek Twojej wyprawy po mroźnej tundrze. Przed Tobą nowe szlaki i nowe wyzwania.', 4326, 413552),
 ((SELECT id FROM worlds WHERE ordinal = 3), 1, 'Wydmy o świcie', 'Rozdział „Wydmy o świcie”: kolejny odcinek Twojej wyprawy po rozpalonej pustyni. Przed Tobą nowe szlaki i nowe wyzwania.', 4759, 467314),
 ((SELECT id FROM worlds WHERE ordinal = 3), 2, 'Oaza', 'Rozdział „Oaza”: kolejny odcinek Twojej wyprawy po rozpalonej pustyni. Przed Tobą nowe szlaki i nowe wyzwania.', 5235, 528065),
 ((SELECT id FROM worlds WHERE ordinal = 3), 3, 'Karawana', 'Rozdział „Karawana”: kolejny odcinek Twojej wyprawy po rozpalonej pustyni. Przed Tobą nowe szlaki i nowe wyzwania.', 5758, 596713),
 ((SELECT id FROM worlds WHERE ordinal = 3), 4, 'Skwar południa', 'Rozdział „Skwar południa”: kolejny odcinek Twojej wyprawy po rozpalonej pustyni. Przed Tobą nowe szlaki i nowe wyzwania.', 6334, 674286),
 ((SELECT id FROM worlds WHERE ordinal = 3), 5, 'Burza piaskowa', 'Rozdział „Burza piaskowa”: kolejny odcinek Twojej wyprawy po rozpalonej pustyni. Przed Tobą nowe szlaki i nowe wyzwania.', 6968, 761943),
 ((SELECT id FROM worlds WHERE ordinal = 3), 6, 'Ruiny w piaskach', 'Rozdział „Ruiny w piaskach”: kolejny odcinek Twojej wyprawy po rozpalonej pustyni. Przed Tobą nowe szlaki i nowe wyzwania.', 7664, 860995),
 ((SELECT id FROM worlds WHERE ordinal = 3), 7, 'Nocny chłód', 'Rozdział „Nocny chłód”: kolejny odcinek Twojej wyprawy po rozpalonej pustyni. Przed Tobą nowe szlaki i nowe wyzwania.', 8431, 972925),
 ((SELECT id FROM worlds WHERE ordinal = 3), 8, 'Morze piasku', 'Rozdział „Morze piasku”: kolejny odcinek Twojej wyprawy po rozpalonej pustyni. Przed Tobą nowe szlaki i nowe wyzwania.', 9274, 1099405),
 ((SELECT id FROM worlds WHERE ordinal = 3), 9, 'Miraż', 'Rozdział „Miraż”: kolejny odcinek Twojej wyprawy po rozpalonej pustyni. Przed Tobą nowe szlaki i nowe wyzwania.', 10201, 1242328),
 ((SELECT id FROM worlds WHERE ordinal = 3), 10, 'Złote wydmy', 'Rozdział „Złote wydmy”: kolejny odcinek Twojej wyprawy po rozpalonej pustyni. Przed Tobą nowe szlaki i nowe wyzwania.', 11221, 1403830),
 ((SELECT id FROM worlds WHERE ordinal = 4), 1, 'Gęsta zieleń', 'Rozdział „Gęsta zieleń”: kolejny odcinek Twojej wyprawy po gęstej dżungli. Przed Tobą nowe szlaki i nowe wyzwania.', 12343, 1586328),
 ((SELECT id FROM worlds WHERE ordinal = 4), 2, 'Most z lian', 'Rozdział „Most z lian”: kolejny odcinek Twojej wyprawy po gęstej dżungli. Przed Tobą nowe szlaki i nowe wyzwania.', 13578, 1792551),
 ((SELECT id FROM worlds WHERE ordinal = 4), 3, 'Rzeka krokodyli', 'Rozdział „Rzeka krokodyli”: kolejny odcinek Twojej wyprawy po gęstej dżungli. Przed Tobą nowe szlaki i nowe wyzwania.', 14936, 2025583),
 ((SELECT id FROM worlds WHERE ordinal = 4), 4, 'Wodospad', 'Rozdział „Wodospad”: kolejny odcinek Twojej wyprawy po gęstej dżungli. Przed Tobą nowe szlaki i nowe wyzwania.', 16429, 2288908),
 ((SELECT id FROM worlds WHERE ordinal = 4), 5, 'Świątynia w gąszczu', 'Rozdział „Świątynia w gąszczu”: kolejny odcinek Twojej wyprawy po gęstej dżungli. Przed Tobą nowe szlaki i nowe wyzwania.', 18072, 2586466),
 ((SELECT id FROM worlds WHERE ordinal = 4), 6, 'Deszcz tropikalny', 'Rozdział „Deszcz tropikalny”: kolejny odcinek Twojej wyprawy po gęstej dżungli. Przed Tobą nowe szlaki i nowe wyzwania.', 19879, 2922707),
 ((SELECT id FROM worlds WHERE ordinal = 4), 7, 'Ścieżka wśród paproci', 'Rozdział „Ścieżka wśród paproci”: kolejny odcinek Twojej wyprawy po gęstej dżungli. Przed Tobą nowe szlaki i nowe wyzwania.', 21867, 3302659),
 ((SELECT id FROM worlds WHERE ordinal = 4), 8, 'Serce dżungli', 'Rozdział „Serce dżungli”: kolejny odcinek Twojej wyprawy po gęstej dżungli. Przed Tobą nowe szlaki i nowe wyzwania.', 24054, 3732005),
 ((SELECT id FROM worlds WHERE ordinal = 4), 9, 'Liany i mgła', 'Rozdział „Liany i mgła”: kolejny odcinek Twojej wyprawy po gęstej dżungli. Przed Tobą nowe szlaki i nowe wyzwania.', 26459, 4217165),
 ((SELECT id FROM worlds WHERE ordinal = 4), 10, 'Zaginione miasto', 'Rozdział „Zaginione miasto”: kolejny odcinek Twojej wyprawy po gęstej dżungli. Przed Tobą nowe szlaki i nowe wyzwania.', 29105, 4765397)
ON DUPLICATE KEY UPDATE name = VALUES(name), story_text = VALUES(story_text), duration_seconds = VALUES(duration_seconds), cost_cents = VALUES(cost_cents);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 4 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 0 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 4 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 0 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 2 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 0 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 6 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 0 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 5 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 0 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 4 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 0 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 7 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 0 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 7 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 0 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 5 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 0 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 9 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 0 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 8 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 0 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 6 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 0 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 11 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 0 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 10 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 0 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 7 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 0 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 13 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 0 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 12 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 0 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 9 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 0 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 15 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 0 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 13 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 0 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 10 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 0 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 16 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 0 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 15 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 0 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 11 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 0 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 18 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 0 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 16 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 0 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 13 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 0 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 20 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 1 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 18 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 1 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 14 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 1 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 22 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 1 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 20 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 1 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 15 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 1 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 24 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 1 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 21 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 1 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 17 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 1 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 25 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 1 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 23 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 1 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 18 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 1 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 27 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 1 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 24 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 1 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 19 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 1 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 29 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 1 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 26 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 1 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 21 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 1 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 31 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 1 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 28 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 1 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 22 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 1 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 33 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 1 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 29 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 1 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 23 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 1 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 34 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 1 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 31 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 1 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 24 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 1 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 36 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 1 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 32 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 1 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 26 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 1 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 38 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 2 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 34 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 2 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 27 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 2 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 40 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 2 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 36 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 2 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 28 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 2 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 42 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 2 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 37 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 2 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 30 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 2 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 43 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 2 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 39 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 2 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 31 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 2 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 45 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 2 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 40 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 2 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 32 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 2 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 47 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 2 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 42 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 2 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 33 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 2 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 49 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 2 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 44 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 2 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 35 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 2 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 51 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 2 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 45 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 2 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 36 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 2 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 52 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 2 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 47 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 2 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 37 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 2 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 54 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 2 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 48 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 2 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 39 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 2 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 56 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 3 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 50 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 3 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 40 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 3 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 58 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 3 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 52 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 3 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 41 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 3 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 60 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 3 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 53 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 3 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 43 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 3 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 61 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 3 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 55 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 3 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 44 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 3 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 63 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 3 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 56 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 3 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 45 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 3 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 65 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 3 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 58 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 3 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 47 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 3 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 67 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 3 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 60 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 3 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 48 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 3 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 69 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 3 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 61 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 3 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 49 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 3 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 70 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 3 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 63 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 3 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 50 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 3 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 72 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 3 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 64 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 3 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 52 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 3 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 74 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 4 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 66 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 4 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 53 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 4 AND c.ordinal = 1 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 76 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 4 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 68 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 4 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 54 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 4 AND c.ordinal = 2 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 78 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 4 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 69 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 4 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 56 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 4 AND c.ordinal = 3 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 79 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 4 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 71 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 4 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 57 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 4 AND c.ordinal = 4 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 81 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 4 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 72 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 4 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 58 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 4 AND c.ordinal = 5 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 83 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 4 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 74 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 4 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 59 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 4 AND c.ordinal = 6 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 85 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 4 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 76 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 4 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 61 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 4 AND c.ordinal = 7 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 87 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 4 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 77 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 4 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 62 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 4 AND c.ordinal = 8 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 88 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 4 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 79 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 4 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 63 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 4 AND c.ordinal = 9 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 90 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'fitness' WHERE w.ordinal = 4 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 80 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'orientation' WHERE w.ordinal = 4 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);

INSERT INTO chapter_requirements (chapter_id, skill_id, level) SELECT c.id, s.id, 65 FROM chapters c JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.code = 'trade' WHERE w.ordinal = 4 AND c.ordinal = 10 ON DUPLICATE KEY UPDATE level = VALUES(level);
