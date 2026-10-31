-- Ekonomia v3: koszt proporcjonalny do czasu (H = G), wolniejszy wzrost, wymagania awansow dopasowane
-- do symulacji 30 dni (scripts/simulate_economy.py, opis w README).
UPDATE skill_definitions SET base_cost_cents = 3, cost_growth = 1.1600, base_seconds = 5, time_growth = 1.1600;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 30 WHERE w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 5 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 5 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 4 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 30 WHERE w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 7 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 7 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 5 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 32 WHERE w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 9 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 9 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 7 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 44 WHERE w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 11 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 11 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 8 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 50, c.duration_seconds = 60 WHERE w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 13 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 13 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 10 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 58, c.duration_seconds = 80 WHERE w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 15 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 14 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 12 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 80, c.duration_seconds = 108 WHERE w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 17 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 16 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 13 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 108, c.duration_seconds = 144 WHERE w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 19 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 18 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 15 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 145, c.duration_seconds = 194 WHERE w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 21 WHERE s.code = 'fitness' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 20 WHERE s.code = 'orientation' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 16 WHERE s.code = 'trade' AND w.ordinal = 1 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 195, c.duration_seconds = 262 WHERE w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 23 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 22 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 18 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 262, c.duration_seconds = 352 WHERE w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 25 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 24 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 20 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 355, c.duration_seconds = 474 WHERE w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 27 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 26 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 21 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 478, c.duration_seconds = 638 WHERE w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 29 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 28 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 23 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 642, c.duration_seconds = 858 WHERE w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 31 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 30 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 24 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 865, c.duration_seconds = 1156 WHERE w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 33 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 31 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 26 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 1165, c.duration_seconds = 1554 WHERE w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 35 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 33 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 28 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 1568, c.duration_seconds = 2092 WHERE w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 37 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 35 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 29 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 2110, c.duration_seconds = 2814 WHERE w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 39 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 37 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 31 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 2840, c.duration_seconds = 3788 WHERE w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 41 WHERE s.code = 'fitness' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 39 WHERE s.code = 'orientation' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 32 WHERE s.code = 'trade' AND w.ordinal = 2 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 3820, c.duration_seconds = 5096 WHERE w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 43 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 41 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 34 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 5142, c.duration_seconds = 6858 WHERE w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 45 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 43 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 36 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 6920, c.duration_seconds = 9228 WHERE w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 47 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 45 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 37 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 9310, c.duration_seconds = 12416 WHERE w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 49 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 47 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 39 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 11460, c.duration_seconds = 14984 WHERE w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 51 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 49 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 40 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 12862, c.duration_seconds = 16182 WHERE w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 53 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 51 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 42 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 14372, c.duration_seconds = 17428 WHERE w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 55 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 52 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 44 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 16000, c.duration_seconds = 18718 WHERE w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 57 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 54 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 45 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 17742, c.duration_seconds = 20054 WHERE w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 59 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 56 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 47 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 19610, c.duration_seconds = 21436 WHERE w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 61 WHERE s.code = 'fitness' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 58 WHERE s.code = 'orientation' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 48 WHERE s.code = 'trade' AND w.ordinal = 3 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 21602, c.duration_seconds = 22866 WHERE w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 63 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 60 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 50 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 23728, c.duration_seconds = 24340 WHERE w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 65 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 62 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 52 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 25985, c.duration_seconds = 25862 WHERE w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 67 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 64 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 53 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 28382, c.duration_seconds = 27428 WHERE w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 69 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 66 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 55 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 30922, c.duration_seconds = 29042 WHERE w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 71 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 68 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 56 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 33610, c.duration_seconds = 30700 WHERE w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 73 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 69 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 58 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 36450, c.duration_seconds = 32406 WHERE w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 75 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 71 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 60 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 39442, c.duration_seconds = 34158 WHERE w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 77 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 73 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 61 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 42598, c.duration_seconds = 35954 WHERE w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 79 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 75 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 63 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 45915, c.duration_seconds = 37798 WHERE w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 81 WHERE s.code = 'fitness' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 77 WHERE s.code = 'orientation' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 64 WHERE s.code = 'trade' AND w.ordinal = 4 AND c.ordinal = 10;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 49400, c.duration_seconds = 39688 WHERE w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 83 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 79 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 66 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 1;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 53060, c.duration_seconds = 41624 WHERE w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 85 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 81 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 68 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 2;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 56892, c.duration_seconds = 43606 WHERE w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 87 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 83 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 69 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 3;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 60908, c.duration_seconds = 45634 WHERE w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 89 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 85 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 71 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 4;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 65108, c.duration_seconds = 47708 WHERE w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 91 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 87 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 72 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 5;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 69495, c.duration_seconds = 49828 WHERE w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 93 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 89 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 74 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 6;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 74075, c.duration_seconds = 51994 WHERE w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 95 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 90 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 76 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 7;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 78852, c.duration_seconds = 54206 WHERE w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 97 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 92 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 77 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 8;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 83832, c.duration_seconds = 56464 WHERE w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 99 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 94 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 79 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 9;

UPDATE chapters c JOIN worlds w ON w.id = c.world_id SET c.cost_cents = 89018, c.duration_seconds = 58768 WHERE w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 101 WHERE s.code = 'fitness' AND w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 96 WHERE s.code = 'orientation' AND w.ordinal = 5 AND c.ordinal = 10;

UPDATE chapter_requirements r JOIN chapters c ON c.id = r.chapter_id JOIN worlds w ON w.id = c.world_id JOIN skill_definitions s ON s.id = r.skill_id SET r.level = 80 WHERE s.code = 'trade' AND w.ordinal = 5 AND c.ordinal = 10;
