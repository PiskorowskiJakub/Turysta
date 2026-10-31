-- Umiejetnosci swiatow: 6 na swiat po 33 poziomow (lacznie tyle samo pracy i kosztu co 10 x 20 w migracji 016).
-- Wygenerowane przez: python scripts/simulate_economy.py --sql

DELETE FROM training_queue WHERE skill_id IN (SELECT id FROM skill_definitions WHERE code IN ('w1_boots', 'w1_weather', 'w1_photo', 'w1_etiquette', 'w2_woodcutting', 'w2_stream_crossing', 'w2_insects', 'w2_silence', 'w3_skiing', 'w3_ice_reading', 'w3_snow_melting', 'w3_frostbite', 'w4_oasis', 'w4_dunes', 'w4_cold_nights', 'w4_dried_food', 'w5_rope_bridge', 'w5_rainwater', 'w5_jungle_listening', 'w5_local_speech'));

DELETE FROM user_skills WHERE skill_id IN (SELECT id FROM skill_definitions WHERE code IN ('w1_boots', 'w1_weather', 'w1_photo', 'w1_etiquette', 'w2_woodcutting', 'w2_stream_crossing', 'w2_insects', 'w2_silence', 'w3_skiing', 'w3_ice_reading', 'w3_snow_melting', 'w3_frostbite', 'w4_oasis', 'w4_dunes', 'w4_cold_nights', 'w4_dried_food', 'w5_rope_bridge', 'w5_rainwater', 'w5_jungle_listening', 'w5_local_speech'));

DELETE FROM skill_definitions WHERE code IN ('w1_boots', 'w1_weather', 'w1_photo', 'w1_etiquette', 'w2_woodcutting', 'w2_stream_crossing', 'w2_insects', 'w2_silence', 'w3_skiing', 'w3_ice_reading', 'w3_snow_melting', 'w3_frostbite', 'w4_oasis', 'w4_dunes', 'w4_cold_nights', 'w4_dried_food', 'w5_rope_bridge', 'w5_rainwater', 'w5_jungle_listening', 'w5_local_speech');

UPDATE skill_definitions SET max_level = 33 WHERE world_ordinal IS NOT NULL;

UPDATE skill_definitions SET base_cost_cents = 3, cost_growth = 1.1704, base_seconds = 5, time_growth = 1.1704 WHERE world_ordinal = 1;

UPDATE skill_definitions SET base_cost_cents = 1537, cost_growth = 1.0403, base_seconds = 2562, time_growth = 1.0403 WHERE world_ordinal = 2;

UPDATE skill_definitions SET base_cost_cents = 3194, cost_growth = 1.0307, base_seconds = 5324, time_growth = 1.0307 WHERE world_ordinal = 3;

UPDATE skill_definitions SET base_cost_cents = 4937, cost_growth = 1.0266, base_seconds = 8229, time_growth = 1.0266 WHERE world_ordinal = 4;

UPDATE skill_definitions SET base_cost_cents = 6720, cost_growth = 1.0238, base_seconds = 11200, time_growth = 1.0238 WHERE world_ordinal = 5;

