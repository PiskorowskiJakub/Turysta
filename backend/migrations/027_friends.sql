-- Znajomi: para graczy (user_a < user_b), kto wyslal zaproszenie i czy zostalo przyjete.
CREATE TABLE friendships (
    user_a BIGINT UNSIGNED NOT NULL,
    user_b BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    PRIMARY KEY (user_a, user_b),
    KEY idx_friendships_b (user_b, status),
    KEY idx_friendships_requester (requested_by, status),
    CONSTRAINT fk_friendships_a FOREIGN KEY (user_a) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_friendships_b FOREIGN KEY (user_b) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_friendships_requester FOREIGN KEY (requested_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
