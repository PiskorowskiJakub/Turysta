-- Prezenty miedzy znajomymi, aktywnosc znajomych (feed na stronie glownej) i ustawienie prywatnosci.
ALTER TABLE users ADD COLUMN share_activity TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE friend_gifts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sender_id BIGINT UNSIGNED NOT NULL,
    recipient_id BIGINT UNSIGNED NOT NULL,
    cents BIGINT NOT NULL,
    bonus_cents BIGINT NOT NULL,
    game_day DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_gift_pair_day (sender_id, recipient_id, game_day),
    KEY idx_gift_sender_day (sender_id, game_day),
    KEY idx_gift_recipient_day (recipient_id, game_day),
    CONSTRAINT fk_gift_sender FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_gift_recipient FOREIGN KEY (recipient_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(20) NOT NULL,
    ref_key VARCHAR(60) NOT NULL,
    payload JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_activity_ref (user_id, type, ref_key),
    KEY idx_activity_user (user_id, created_at),
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
