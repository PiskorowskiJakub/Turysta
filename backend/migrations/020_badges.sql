-- Zdobyte odznaki z data zdobycia (user_badges). Definicje odznak: umiejetnosci (3 stopnie), seria dni (5), dni aktywnosci (3).
ALTER TABLE badge_definitions MODIFY tier ENUM('bronze','silver','gold','diamond','ruby','special') NOT NULL;

ALTER TABLE badge_definitions
    ADD COLUMN skill_code VARCHAR(60) NULL AFTER condition_params,
    ADD COLUMN threshold INT UNSIGNED NULL AFTER skill_code;

INSERT INTO badge_definitions (code, name, category, tier, condition_type, skill_code, threshold) VALUES
 ('fitness.bronze', 'Kondycja', 'skill', 'bronze', 'skill_level', 'fitness', 50),
 ('fitness.silver', 'Kondycja', 'skill', 'silver', 'skill_level', 'fitness', 100),
 ('fitness.gold', 'Kondycja', 'skill', 'gold', 'skill_level', 'fitness', 200),
 ('orientation.bronze', 'Orientacja w terenie', 'skill', 'bronze', 'skill_level', 'orientation', 50),
 ('orientation.silver', 'Orientacja w terenie', 'skill', 'silver', 'skill_level', 'orientation', 100),
 ('orientation.gold', 'Orientacja w terenie', 'skill', 'gold', 'skill_level', 'orientation', 200),
 ('trade.bronze', 'Handel', 'skill', 'bronze', 'skill_level', 'trade', 50),
 ('trade.silver', 'Handel', 'skill', 'silver', 'skill_level', 'trade', 100),
 ('trade.gold', 'Handel', 'skill', 'gold', 'skill_level', 'trade', 200),
 ('w1_packing.bronze', 'Pakowanie plecaka', 'skill', 'bronze', 'skill_level', 'w1_packing', 11),
 ('w1_packing.silver', 'Pakowanie plecaka', 'skill', 'silver', 'skill_level', 'w1_packing', 22),
 ('w1_packing.gold', 'Pakowanie plecaka', 'skill', 'gold', 'skill_level', 'w1_packing', 33),
 ('w1_campfire.bronze', 'Rozpalanie ogniska', 'skill', 'bronze', 'skill_level', 'w1_campfire', 11),
 ('w1_campfire.silver', 'Rozpalanie ogniska', 'skill', 'silver', 'skill_level', 'w1_campfire', 22),
 ('w1_campfire.gold', 'Rozpalanie ogniska', 'skill', 'gold', 'skill_level', 'w1_campfire', 33),
 ('w1_trail_signs.bronze', 'Znaki szlaków', 'skill', 'bronze', 'skill_level', 'w1_trail_signs', 11),
 ('w1_trail_signs.silver', 'Znaki szlaków', 'skill', 'silver', 'skill_level', 'w1_trail_signs', 22),
 ('w1_trail_signs.gold', 'Znaki szlaków', 'skill', 'gold', 'skill_level', 'w1_trail_signs', 33),
 ('w1_first_aid.bronze', 'Pierwsza pomoc', 'skill', 'bronze', 'skill_level', 'w1_first_aid', 11),
 ('w1_first_aid.silver', 'Pierwsza pomoc', 'skill', 'silver', 'skill_level', 'w1_first_aid', 22),
 ('w1_first_aid.gold', 'Pierwsza pomoc', 'skill', 'gold', 'skill_level', 'w1_first_aid', 33),
 ('w1_cooking.bronze', 'Kuchnia turystyczna', 'skill', 'bronze', 'skill_level', 'w1_cooking', 11),
 ('w1_cooking.silver', 'Kuchnia turystyczna', 'skill', 'silver', 'skill_level', 'w1_cooking', 22),
 ('w1_cooking.gold', 'Kuchnia turystyczna', 'skill', 'gold', 'skill_level', 'w1_cooking', 33),
 ('w1_tent.bronze', 'Rozbijanie namiotu', 'skill', 'bronze', 'skill_level', 'w1_tent', 11),
 ('w1_tent.silver', 'Rozbijanie namiotu', 'skill', 'silver', 'skill_level', 'w1_tent', 22),
 ('w1_tent.gold', 'Rozbijanie namiotu', 'skill', 'gold', 'skill_level', 'w1_tent', 33),
 ('w2_mushrooms.bronze', 'Rozpoznawanie grzybów', 'skill', 'bronze', 'skill_level', 'w2_mushrooms', 11),
 ('w2_mushrooms.silver', 'Rozpoznawanie grzybów', 'skill', 'silver', 'skill_level', 'w2_mushrooms', 22),
 ('w2_mushrooms.gold', 'Rozpoznawanie grzybów', 'skill', 'gold', 'skill_level', 'w2_mushrooms', 33),
 ('w2_herbs.bronze', 'Zbieranie ziół i jagód', 'skill', 'bronze', 'skill_level', 'w2_herbs', 11),
 ('w2_herbs.silver', 'Zbieranie ziół i jagód', 'skill', 'silver', 'skill_level', 'w2_herbs', 22),
 ('w2_herbs.gold', 'Zbieranie ziół i jagód', 'skill', 'gold', 'skill_level', 'w2_herbs', 33),
 ('w2_tracking.bronze', 'Tropienie zwierząt', 'skill', 'bronze', 'skill_level', 'w2_tracking', 11),
 ('w2_tracking.silver', 'Tropienie zwierząt', 'skill', 'silver', 'skill_level', 'w2_tracking', 22),
 ('w2_tracking.gold', 'Tropienie zwierząt', 'skill', 'gold', 'skill_level', 'w2_tracking', 33),
 ('w2_shelter.bronze', 'Budowa szałasu', 'skill', 'bronze', 'skill_level', 'w2_shelter', 11),
 ('w2_shelter.silver', 'Budowa szałasu', 'skill', 'silver', 'skill_level', 'w2_shelter', 22),
 ('w2_shelter.gold', 'Budowa szałasu', 'skill', 'gold', 'skill_level', 'w2_shelter', 33),
 ('w2_fishing.bronze', 'Wędkowanie w strumieniu', 'skill', 'bronze', 'skill_level', 'w2_fishing', 11),
 ('w2_fishing.silver', 'Wędkowanie w strumieniu', 'skill', 'silver', 'skill_level', 'w2_fishing', 22),
 ('w2_fishing.gold', 'Wędkowanie w strumieniu', 'skill', 'gold', 'skill_level', 'w2_fishing', 33),
 ('w2_night_march.bronze', 'Nocny marsz', 'skill', 'bronze', 'skill_level', 'w2_night_march', 11),
 ('w2_night_march.silver', 'Nocny marsz', 'skill', 'silver', 'skill_level', 'w2_night_march', 22),
 ('w2_night_march.gold', 'Nocny marsz', 'skill', 'gold', 'skill_level', 'w2_night_march', 33),
 ('w3_layers.bronze', 'Ubiór warstwowy', 'skill', 'bronze', 'skill_level', 'w3_layers', 11),
 ('w3_layers.silver', 'Ubiór warstwowy', 'skill', 'silver', 'skill_level', 'w3_layers', 22),
 ('w3_layers.gold', 'Ubiór warstwowy', 'skill', 'gold', 'skill_level', 'w3_layers', 33),
 ('w3_snow_shelter.bronze', 'Schronienie w śniegu', 'skill', 'bronze', 'skill_level', 'w3_snow_shelter', 11),
 ('w3_snow_shelter.silver', 'Schronienie w śniegu', 'skill', 'silver', 'skill_level', 'w3_snow_shelter', 22),
 ('w3_snow_shelter.gold', 'Schronienie w śniegu', 'skill', 'gold', 'skill_level', 'w3_snow_shelter', 33),
 ('w3_snowshoes.bronze', 'Rakiety śnieżne', 'skill', 'bronze', 'skill_level', 'w3_snowshoes', 11),
 ('w3_snowshoes.silver', 'Rakiety śnieżne', 'skill', 'silver', 'skill_level', 'w3_snowshoes', 22),
 ('w3_snowshoes.gold', 'Rakiety śnieżne', 'skill', 'gold', 'skill_level', 'w3_snowshoes', 33),
 ('w3_dog_sled.bronze', 'Zaprzęg psi', 'skill', 'bronze', 'skill_level', 'w3_dog_sled', 11),
 ('w3_dog_sled.silver', 'Zaprzęg psi', 'skill', 'silver', 'skill_level', 'w3_dog_sled', 22),
 ('w3_dog_sled.gold', 'Zaprzęg psi', 'skill', 'gold', 'skill_level', 'w3_dog_sled', 33),
 ('w3_ice_fishing.bronze', 'Połów w przeręblu', 'skill', 'bronze', 'skill_level', 'w3_ice_fishing', 11),
 ('w3_ice_fishing.silver', 'Połów w przeręblu', 'skill', 'silver', 'skill_level', 'w3_ice_fishing', 22),
 ('w3_ice_fishing.gold', 'Połów w przeręblu', 'skill', 'gold', 'skill_level', 'w3_ice_fishing', 33),
 ('w3_whiteout.bronze', 'Nawigacja w zamieci', 'skill', 'bronze', 'skill_level', 'w3_whiteout', 11),
 ('w3_whiteout.silver', 'Nawigacja w zamieci', 'skill', 'silver', 'skill_level', 'w3_whiteout', 22),
 ('w3_whiteout.gold', 'Nawigacja w zamieci', 'skill', 'gold', 'skill_level', 'w3_whiteout', 33),
 ('w4_water.bronze', 'Gospodarka wodą', 'skill', 'bronze', 'skill_level', 'w4_water', 11),
 ('w4_water.silver', 'Gospodarka wodą', 'skill', 'silver', 'skill_level', 'w4_water', 22),
 ('w4_water.gold', 'Gospodarka wodą', 'skill', 'gold', 'skill_level', 'w4_water', 33),
 ('w4_camel.bronze', 'Jazda na wielbłądzie', 'skill', 'bronze', 'skill_level', 'w4_camel', 11),
 ('w4_camel.silver', 'Jazda na wielbłądzie', 'skill', 'silver', 'skill_level', 'w4_camel', 22),
 ('w4_camel.gold', 'Jazda na wielbłądzie', 'skill', 'gold', 'skill_level', 'w4_camel', 33),
 ('w4_sun_protection.bronze', 'Ochrona przed słońcem', 'skill', 'bronze', 'skill_level', 'w4_sun_protection', 11),
 ('w4_sun_protection.silver', 'Ochrona przed słońcem', 'skill', 'silver', 'skill_level', 'w4_sun_protection', 22),
 ('w4_sun_protection.gold', 'Ochrona przed słońcem', 'skill', 'gold', 'skill_level', 'w4_sun_protection', 33),
 ('w4_star_navigation.bronze', 'Nawigacja po gwiazdach', 'skill', 'bronze', 'skill_level', 'w4_star_navigation', 11),
 ('w4_star_navigation.silver', 'Nawigacja po gwiazdach', 'skill', 'silver', 'skill_level', 'w4_star_navigation', 22),
 ('w4_star_navigation.gold', 'Nawigacja po gwiazdach', 'skill', 'gold', 'skill_level', 'w4_star_navigation', 33),
 ('w4_bazaar.bronze', 'Targowanie na bazarze', 'skill', 'bronze', 'skill_level', 'w4_bazaar', 11),
 ('w4_bazaar.silver', 'Targowanie na bazarze', 'skill', 'silver', 'skill_level', 'w4_bazaar', 22),
 ('w4_bazaar.gold', 'Targowanie na bazarze', 'skill', 'gold', 'skill_level', 'w4_bazaar', 33),
 ('w4_sandstorm.bronze', 'Przetrwanie burzy piaskowej', 'skill', 'bronze', 'skill_level', 'w4_sandstorm', 11),
 ('w4_sandstorm.silver', 'Przetrwanie burzy piaskowej', 'skill', 'silver', 'skill_level', 'w4_sandstorm', 22),
 ('w4_sandstorm.gold', 'Przetrwanie burzy piaskowej', 'skill', 'gold', 'skill_level', 'w4_sandstorm', 33),
 ('w5_machete.bronze', 'Torowanie drogi maczetą', 'skill', 'bronze', 'skill_level', 'w5_machete', 11),
 ('w5_machete.silver', 'Torowanie drogi maczetą', 'skill', 'silver', 'skill_level', 'w5_machete', 22),
 ('w5_machete.gold', 'Torowanie drogi maczetą', 'skill', 'gold', 'skill_level', 'w5_machete', 33),
 ('w5_liana_climbing.bronze', 'Wspinaczka po lianach', 'skill', 'bronze', 'skill_level', 'w5_liana_climbing', 11),
 ('w5_liana_climbing.silver', 'Wspinaczka po lianach', 'skill', 'silver', 'skill_level', 'w5_liana_climbing', 22),
 ('w5_liana_climbing.gold', 'Wspinaczka po lianach', 'skill', 'gold', 'skill_level', 'w5_liana_climbing', 33),
 ('w5_edible_plants.bronze', 'Rośliny jadalne', 'skill', 'bronze', 'skill_level', 'w5_edible_plants', 11),
 ('w5_edible_plants.silver', 'Rośliny jadalne', 'skill', 'silver', 'skill_level', 'w5_edible_plants', 22),
 ('w5_edible_plants.gold', 'Rośliny jadalne', 'skill', 'gold', 'skill_level', 'w5_edible_plants', 33),
 ('w5_tropical_health.bronze', 'Zdrowie w tropikach', 'skill', 'bronze', 'skill_level', 'w5_tropical_health', 11),
 ('w5_tropical_health.silver', 'Zdrowie w tropikach', 'skill', 'silver', 'skill_level', 'w5_tropical_health', 22),
 ('w5_tropical_health.gold', 'Zdrowie w tropikach', 'skill', 'gold', 'skill_level', 'w5_tropical_health', 33),
 ('w5_canoe.bronze', 'Spływ kajakiem', 'skill', 'bronze', 'skill_level', 'w5_canoe', 11),
 ('w5_canoe.silver', 'Spływ kajakiem', 'skill', 'silver', 'skill_level', 'w5_canoe', 22),
 ('w5_canoe.gold', 'Spływ kajakiem', 'skill', 'gold', 'skill_level', 'w5_canoe', 33),
 ('w5_hammock.bronze', 'Nocleg w hamaku', 'skill', 'bronze', 'skill_level', 'w5_hammock', 11),
 ('w5_hammock.silver', 'Nocleg w hamaku', 'skill', 'silver', 'skill_level', 'w5_hammock', 22),
 ('w5_hammock.gold', 'Nocleg w hamaku', 'skill', 'gold', 'skill_level', 'w5_hammock', 33),
 ('special_streak.bronze', 'Seria dni', 'special', 'bronze', 'streak_days', NULL, 7),
 ('special_streak.silver', 'Seria dni', 'special', 'silver', 'streak_days', NULL, 14),
 ('special_streak.gold', 'Seria dni', 'special', 'gold', 'streak_days', NULL, 30),
 ('special_streak.diamond', 'Seria dni', 'special', 'diamond', 'streak_days', NULL, 60),
 ('special_streak.ruby', 'Seria dni', 'special', 'ruby', 'streak_days', NULL, 90),
 ('special_activity.bronze', 'Dni aktywności', 'special', 'bronze', 'active_days', NULL, 30),
 ('special_activity.silver', 'Dni aktywności', 'special', 'silver', 'active_days', NULL, 100),
 ('special_activity.gold', 'Dni aktywności', 'special', 'gold', 'active_days', NULL, 365);

-- Odznaki juz zdobyte: data = ukonczenie szkolenia danego poziomu (a gdy jej nie ma, chwila migracji).
INSERT IGNORE INTO user_badges (user_id, badge_id, earned_at)
SELECT us.user_id, b.id,
       COALESCE((SELECT MIN(q.finished_at) FROM training_queue q
                 WHERE q.user_id = us.user_id AND q.skill_id = us.skill_id AND q.status = 'done' AND q.target_level = b.threshold),
                UTC_TIMESTAMP())
FROM badge_definitions b
JOIN skill_definitions sd ON sd.code = b.skill_code
JOIN user_skills us ON us.skill_id = sd.id AND us.level >= b.threshold
WHERE b.condition_type = 'skill_level';

INSERT IGNORE INTO user_badges (user_id, badge_id, earned_at)
SELECT st.user_id, b.id, UTC_TIMESTAMP()
FROM badge_definitions b
JOIN user_stats st ON st.longest_streak >= b.threshold
WHERE b.condition_type = 'streak_days';

INSERT IGNORE INTO user_badges (user_id, badge_id, earned_at)
SELECT st.user_id, b.id, UTC_TIMESTAMP()
FROM badge_definitions b
JOIN user_stats st ON st.active_days >= b.threshold
WHERE b.condition_type = 'active_days';
