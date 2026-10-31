-- Ekonomia v4: wolniejsze tempo (ok. roku na 5 swiatow), koszt po poziomie 50 rosnie jak czas (kwadrat),
-- umiejetnosci swiatow startuja od ulamka czasu podstawowych na poziomie wejscia do swiata.
-- Wygenerowane przez: python scripts/simulate_economy.py --sql

UPDATE skill_definitions SET base_cost_cents = 3, cost_growth = 1.2500, base_seconds = 5, time_growth = 1.2500 WHERE world_ordinal = 1;

UPDATE skill_definitions SET base_cost_cents = 2100, cost_growth = 1.0956, base_seconds = 3500, time_growth = 1.0956 WHERE world_ordinal = 2;

UPDATE skill_definitions SET base_cost_cents = 4449, cost_growth = 1.0838, base_seconds = 7415, time_growth = 1.0838 WHERE world_ordinal = 3;

UPDATE skill_definitions SET base_cost_cents = 6888, cost_growth = 1.0789, base_seconds = 11480, time_growth = 1.0789 WHERE world_ordinal = 4;

UPDATE skill_definitions SET base_cost_cents = 9359, cost_growth = 1.0757, base_seconds = 15598, time_growth = 1.0757 WHERE world_ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 412, c.duration_seconds = 550 WHERE w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 28 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 26 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 22 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 2110, c.duration_seconds = 2814 WHERE w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 39 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 37 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 31 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 8028, c.duration_seconds = 10704 WHERE w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 48 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 46 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 38 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 13548, c.duration_seconds = 18066 WHERE w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 56 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 53 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 44 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 17145, c.duration_seconds = 22866 WHERE w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 63 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 60 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 50 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 20568, c.duration_seconds = 27428 WHERE w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 69 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 66 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 55 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 24300, c.duration_seconds = 32406 WHERE w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 75 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 71 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 59 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 27648, c.duration_seconds = 36870 WHERE w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 80 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 76 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 64 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 31210, c.duration_seconds = 41624 WHERE w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 85 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 81 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 68 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 34990, c.duration_seconds = 46664 WHERE w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 90 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 86 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 72 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 38988, c.duration_seconds = 51994 WHERE w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 95 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 90 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 76 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 42340, c.duration_seconds = 56464 WHERE w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 99 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 95 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 79 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 46725, c.duration_seconds = 62312 WHERE w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 104 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 99 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 83 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50388, c.duration_seconds = 67196 WHERE w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 108 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 103 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 86 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 54190, c.duration_seconds = 72266 WHERE w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 112 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 107 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 89 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 58128, c.duration_seconds = 77520 WHERE w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 116 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 110 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 92 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 62208, c.duration_seconds = 82960 WHERE w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 120 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 114 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 95 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 65355, c.duration_seconds = 87158 WHERE w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 123 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 117 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 98 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 69675, c.duration_seconds = 92920 WHERE w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 127 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 121 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 101 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 74135, c.duration_seconds = 98866 WHERE w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 131 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 124 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 104 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 77568, c.duration_seconds = 103446 WHERE w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 134 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 128 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 107 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 82270, c.duration_seconds = 109714 WHERE w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 138 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 131 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 110 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 85885, c.duration_seconds = 114536 WHERE w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 141 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 134 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 112 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 89578, c.duration_seconds = 119462 WHERE w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 144 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 137 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 115 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 93350, c.duration_seconds = 124490 WHERE w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 147 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 140 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 117 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 97200, c.duration_seconds = 129624 WHERE w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 150 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 143 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 120 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 102452, c.duration_seconds = 136630 WHERE w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 154 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 146 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 122 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 106482, c.duration_seconds = 142004 WHERE w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 157 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 149 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 125 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 110590, c.duration_seconds = 147482 WHERE w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 160 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 152 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 127 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 114778, c.duration_seconds = 153066 WHERE w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 163 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 155 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 130 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 117610, c.duration_seconds = 156844 WHERE w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 165 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 157 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 132 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 121928, c.duration_seconds = 162600 WHERE w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 168 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 160 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 134 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 126320, c.duration_seconds = 168458 WHERE w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 171 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 163 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 137 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 130790, c.duration_seconds = 174422 WHERE w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 174 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 165 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 139 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 135340, c.duration_seconds = 180488 WHERE w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 177 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 168 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 141 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 138415, c.duration_seconds = 184590 WHERE w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 179 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 171 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 143 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 143095, c.duration_seconds = 190828 WHERE w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 182 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 173 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 145 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 147850, c.duration_seconds = 197172 WHERE w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 185 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 176 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 147 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 151065, c.duration_seconds = 201458 WHERE w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 187 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 178 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 149 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 155950, c.duration_seconds = 207974 WHERE w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 190 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 181 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 152 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 159250, c.duration_seconds = 212376 WHERE w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 192 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 183 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 154 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 164268, c.duration_seconds = 219064 WHERE w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 195 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 185 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 156 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 169360, c.duration_seconds = 225856 WHERE w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 198 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 188 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 158 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 172800, c.duration_seconds = 230442 WHERE w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 200 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 190 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 160 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 176272, c.duration_seconds = 235074 WHERE w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 202 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 192 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 162 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 181548, c.duration_seconds = 242108 WHERE w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 205 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 195 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 163 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 185108, c.duration_seconds = 246856 WHERE w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 207 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 197 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 165 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 190510, c.duration_seconds = 254062 WHERE w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 210 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 199 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 167 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 194158, c.duration_seconds = 258924 WHERE w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 212 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 202 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 169 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 10;

