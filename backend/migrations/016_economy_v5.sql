-- Ekonomia v5: umiejetnosci swiata wymagane do awansow (stopniowo, a do nowego swiata wszystkie),
-- awans do nowego swiata jako wydarzenie (x20 koszt, x6 czas); umiejetnosci swiatow: max 20 poziomow, start od ulamka czasu podstawowych na poziomie wejscia do swiata.
-- Wygenerowane przez: python scripts/simulate_economy.py --sql

UPDATE skill_definitions SET max_level = 20 WHERE world_ordinal IS NOT NULL;

UPDATE skill_definitions SET base_cost_cents = 3, cost_growth = 1.3000, base_seconds = 5, time_growth = 1.3000 WHERE world_ordinal = 1;

UPDATE skill_definitions SET base_cost_cents = 1537, cost_growth = 1.0676, base_seconds = 2562, time_growth = 1.0676 WHERE world_ordinal = 2;

UPDATE skill_definitions SET base_cost_cents = 3194, cost_growth = 1.0511, base_seconds = 5324, time_growth = 1.0511 WHERE world_ordinal = 3;

UPDATE skill_definitions SET base_cost_cents = 4937, cost_growth = 1.0441, base_seconds = 8229, time_growth = 1.0441 WHERE world_ordinal = 4;

UPDATE skill_definitions SET base_cost_cents = 6720, cost_growth = 1.0394, base_seconds = 11200, time_growth = 1.0394 WHERE world_ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 228, c.duration_seconds = 304 WHERE w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 24 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 23 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 19 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 865, c.duration_seconds = 1156 WHERE w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 33 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 32 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 26 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 2840, c.duration_seconds = 3788 WHERE w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 41 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 39 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 32 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 8028, c.duration_seconds = 10704 WHERE w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 48 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 45 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 38 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 12132, c.duration_seconds = 16182 WHERE w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 53 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 51 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 42 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 15038, c.duration_seconds = 20054 WHERE w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 59 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 56 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 47 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 17692, c.duration_seconds = 23598 WHERE w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 64 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 61 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 51 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 19975, c.duration_seconds = 26640 WHERE w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 68 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 65 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 54 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 23020, c.duration_seconds = 30700 WHERE w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 73 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 69 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 58 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 512240, c.duration_seconds = 204948 WHERE w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 77 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 73 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 61 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 28342, c.duration_seconds = 37798 WHERE w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 81 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 77 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 64 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 31210, c.duration_seconds = 41624 WHERE w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 85 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 81 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 67 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 33452, c.duration_seconds = 44614 WHERE w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 88 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 84 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 70 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 36562, c.duration_seconds = 48762 WHERE w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 92 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 87 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 73 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 38988, c.duration_seconds = 51994 WHERE w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 95 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 91 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 76 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 42340, c.duration_seconds = 56464 WHERE w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 99 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 94 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 79 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 44945, c.duration_seconds = 59938 WHERE w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 102 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 97 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 81 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 47628, c.duration_seconds = 63516 WHERE w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 105 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 100 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 84 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50388, c.duration_seconds = 67196 WHERE w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 108 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 103 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 86 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 1064500, c.duration_seconds = 425892 WHERE w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 111 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 106 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 89 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 56142, c.duration_seconds = 74870 WHERE w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 114 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 109 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 91 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 59135, c.duration_seconds = 78862 WHERE w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 117 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 111 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 93 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 62208, c.duration_seconds = 82960 WHERE w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 120 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 114 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 95 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 65355, c.duration_seconds = 87158 WHERE w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 123 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 117 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 98 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 67500, c.duration_seconds = 90016 WHERE w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 125 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 119 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 100 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 70778, c.duration_seconds = 94390 WHERE w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 128 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 122 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 102 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 74135, c.duration_seconds = 98866 WHERE w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 131 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 124 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 104 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 76415, c.duration_seconds = 101908 WHERE w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 133 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 127 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 106 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 79902, c.duration_seconds = 106556 WHERE w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 136 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 129 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 108 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 1645400, c.duration_seconds = 658284 WHERE w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 138 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 131 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 110 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 85885, c.duration_seconds = 114536 WHERE w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 141 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 134 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 112 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 88338, c.duration_seconds = 117808 WHERE w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 143 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 136 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 114 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 90828, c.duration_seconds = 121126 WHERE w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 145 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 138 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 116 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 94625, c.duration_seconds = 126190 WHERE w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 148 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 141 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 118 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 97200, c.duration_seconds = 129624 WHERE w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 150 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 143 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 120 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 99808, c.duration_seconds = 133104 WHERE w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 152 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 145 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 122 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 103788, c.duration_seconds = 138410 WHERE w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 155 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 147 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 123 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 106482, c.duration_seconds = 142004 WHERE w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 157 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 149 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 125 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 109212, c.duration_seconds = 145646 WHERE w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 159 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 151 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 127 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 2239560, c.duration_seconds = 895992 WHERE w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 161 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 153 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 129 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 114778, c.duration_seconds = 153066 WHERE w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 163 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 155 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 130 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 119040, c.duration_seconds = 158752 WHERE w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 166 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 157 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 132 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 121928, c.duration_seconds = 162600 WHERE w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 168 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 159 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 134 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 124848, c.duration_seconds = 166494 WHERE w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 170 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 161 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 135 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 127802, c.duration_seconds = 170434 WHERE w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 172 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 163 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 137 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 130790, c.duration_seconds = 174422 WHERE w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 174 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 165 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 139 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 133815, c.duration_seconds = 178454 WHERE w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 176 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 167 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 140 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 136872, c.duration_seconds = 182534 WHERE w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 178 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 169 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 142 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 139968, c.duration_seconds = 186658 WHERE w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 180 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 171 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 144 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 10;

