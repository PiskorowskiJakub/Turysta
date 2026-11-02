-- Odznaki specjalne za znajomych: liczba znajomych (5 stopni, do 50) oraz liczba znajomych, ktorych wyprzedziles w punktach (3 stopnie).
INSERT INTO badge_definitions (code, name, category, tier, condition_type, skill_code, threshold) VALUES
 ('special_friends.bronze', 'Znajomi', 'special', 'bronze', 'friends_count', NULL, 1),
 ('special_friends.silver', 'Znajomi', 'special', 'silver', 'friends_count', NULL, 5),
 ('special_friends.gold', 'Znajomi', 'special', 'gold', 'friends_count', NULL, 10),
 ('special_friends.diamond', 'Znajomi', 'special', 'diamond', 'friends_count', NULL, 25),
 ('special_friends.ruby', 'Znajomi', 'special', 'ruby', 'friends_count', NULL, 50),
 ('special_rival.bronze', 'Rywalizacja', 'special', 'bronze', 'friends_beaten', NULL, 1),
 ('special_rival.silver', 'Rywalizacja', 'special', 'silver', 'friends_beaten', NULL, 3),
 ('special_rival.gold', 'Rywalizacja', 'special', 'gold', 'friends_beaten', NULL, 10);

-- Odznaki za znajomych, ktorych gracze juz maja.
INSERT IGNORE INTO user_badges (user_id, badge_id, earned_at)
SELECT c.user_id, b.id, UTC_TIMESTAMP()
FROM (
    SELECT user_a AS user_id, COUNT(*) AS n FROM (
        SELECT user_a, user_b FROM friendships WHERE status = 'accepted'
        UNION ALL
        SELECT user_b, user_a FROM friendships WHERE status = 'accepted'
    ) x GROUP BY user_a
) c
JOIN badge_definitions b ON b.condition_type = 'friends_count' AND b.threshold <= c.n;
