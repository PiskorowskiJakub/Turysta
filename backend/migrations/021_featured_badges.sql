-- Wyroznione odznaki gracza (do 5), wybierane z juz zdobytych. Wyrozniana jest cala umiejetnosc/odznaka (grupa),
-- a pokazywany jest najwyzszy zdobyty stopien.
CREATE TABLE user_featured_badges (
    user_id BIGINT UNSIGNED NOT NULL,
    position TINYINT UNSIGNED NOT NULL,
    badge_group VARCHAR(60) NOT NULL,
    PRIMARY KEY (user_id, position),
    UNIQUE KEY uq_featured_group (user_id, badge_group),
    CONSTRAINT fk_featured_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
