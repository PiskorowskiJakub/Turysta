-- Nowa ekonomia: poczatkowe poziomy to kilka sekund i grosze, potem minuty, a dopiero potem godziny.
UPDATE skill_definitions SET base_cost_cents = 3, cost_growth = 1.2000, base_seconds = 5, time_growth = 1.1800;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 30 WHERE w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 4 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 4 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 3 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 30 WHERE w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 5 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 5 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 4 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 30 WHERE w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 7 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 6 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 5 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 32 WHERE w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 8 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 7 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 6 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 38 WHERE w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 9 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 9 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 6 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 44 WHERE w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 10 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 10 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 7 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 52 WHERE w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 11 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 11 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 8 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 65, c.duration_seconds = 72 WHERE w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 13 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 12 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 9 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 80, c.duration_seconds = 86 WHERE w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 14 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 13 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 10 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 95, c.duration_seconds = 102 WHERE w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 15 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 14 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 11 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 115, c.duration_seconds = 120 WHERE w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 16 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 15 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 12 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 138, c.duration_seconds = 142 WHERE w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 17 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 16 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 13 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 198, c.duration_seconds = 196 WHERE w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 19 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 17 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 14 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 238, c.duration_seconds = 232 WHERE w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 20 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 18 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 15 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 288, c.duration_seconds = 274 WHERE w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 21 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 19 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 16 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 345, c.duration_seconds = 324 WHERE w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 22 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 21 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 16 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 412, c.duration_seconds = 382 WHERE w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 23 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 22 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 17 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 595, c.duration_seconds = 532 WHERE w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 25 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 23 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 18 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 715, c.duration_seconds = 626 WHERE w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 26 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 24 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 19 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 858, c.duration_seconds = 740 WHERE w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 27 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 25 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 20 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 1030, c.duration_seconds = 872 WHERE w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 28 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 26 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 21 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 1235, c.duration_seconds = 1030 WHERE w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 29 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 27 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 22 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 1780, c.duration_seconds = 1434 WHERE w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 31 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 28 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 23 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 2135, c.duration_seconds = 1692 WHERE w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 32 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 29 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 24 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 2562, c.duration_seconds = 1996 WHERE w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 33 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 31 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 24 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 3075, c.duration_seconds = 2356 WHERE w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 34 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 32 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 25 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 3690, c.duration_seconds = 2780 WHERE w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 35 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 33 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 26 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 5315, c.duration_seconds = 3870 WHERE w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 37 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 34 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 27 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 6378, c.duration_seconds = 4568 WHERE w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 38 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 35 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 28 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 7655, c.duration_seconds = 5390 WHERE w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 39 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 36 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 29 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 9185, c.duration_seconds = 6360 WHERE w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 40 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 37 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 30 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 11022, c.duration_seconds = 7504 WHERE w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 41 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 38 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 31 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 15872, c.duration_seconds = 10448 WHERE w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 43 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 39 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 32 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 19048, c.duration_seconds = 12328 WHERE w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 44 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 40 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 33 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 22858, c.duration_seconds = 14548 WHERE w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 45 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 41 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 34 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 27428, c.duration_seconds = 17166 WHERE w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 46 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 43 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 34 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 32915, c.duration_seconds = 20256 WHERE w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 47 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 44 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 35 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 47398, c.duration_seconds = 28206 WHERE w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 49 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 45 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 36 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 56878, c.duration_seconds = 33282 WHERE w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 50 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 46 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 37 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 60358, c.duration_seconds = 34628 WHERE w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 51 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 47 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 38 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 63978, c.duration_seconds = 35998 WHERE w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 52 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 48 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 39 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 67740, c.duration_seconds = 37396 WHERE w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 53 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 49 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 40 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 75702, c.duration_seconds = 40272 WHERE w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 55 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 50 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 41 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 79908, c.duration_seconds = 41750 WHERE w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 56 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 51 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 42 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 84265, c.duration_seconds = 43254 WHERE w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 57 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 53 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 42 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 88778, c.duration_seconds = 44786 WHERE w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 58 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 54 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 43 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 93450, c.duration_seconds = 46342 WHERE w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 59 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 55 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 44 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 103280, c.duration_seconds = 49538 WHERE w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 61 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 56 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 45 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 108442, c.duration_seconds = 51176 WHERE w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 62 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 57 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 46 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 10;
