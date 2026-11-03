-- Usuwamy odznake "Rywalizacja" (nic nie dawala) i dodajemy odznake za polecenia: 5 stopni wg liczby aktywnych poleconych graczy
-- (potwierdzony e-mail i co najmniej 7 dni aktywnosci). Za kazdy stopien gracz dostaje tez dukaty (patrz BadgeService i Economy).
DELETE FROM user_badges WHERE badge_id IN (SELECT id FROM badge_definitions WHERE code LIKE 'special\_rival.%');
DELETE FROM user_featured_badges WHERE badge_group = 'special_rival';
DELETE FROM activity_events WHERE ref_key LIKE 'special\_rival.%';
DELETE FROM badge_definitions WHERE code LIKE 'special\_rival.%';

INSERT INTO badge_definitions (code, name, category, tier, condition_type, skill_code, threshold) VALUES
 ('special_referrals.bronze', 'Polecenia', 'special', 'bronze', 'referrals_active', NULL, 1),
 ('special_referrals.silver', 'Polecenia', 'special', 'silver', 'referrals_active', NULL, 3),
 ('special_referrals.gold', 'Polecenia', 'special', 'gold', 'referrals_active', NULL, 5),
 ('special_referrals.diamond', 'Polecenia', 'special', 'diamond', 'referrals_active', NULL, 10),
 ('special_referrals.ruby', 'Polecenia', 'special', 'ruby', 'referrals_active', NULL, 25);
