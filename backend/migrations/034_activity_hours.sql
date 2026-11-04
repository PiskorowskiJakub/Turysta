-- Aktywnosc graczy w rozbiciu na godziny (UTC): dane do wykresow w panelu administratora.
CREATE TABLE user_active_hours (
    user_id BIGINT UNSIGNED NOT NULL,
    hour DATETIME NOT NULL,
    PRIMARY KEY (user_id, hour),
    KEY idx_user_active_hours_hour (hour),
    CONSTRAINT fk_user_active_hours_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE user_stats ADD COLUMN last_active_hour DATETIME NULL;
