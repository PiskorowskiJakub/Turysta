-- Statystyki gracza: zarobki wg zrodla (np. job:market) oraz dni aktywnosci i serie.
CREATE TABLE user_earnings (
    user_id BIGINT UNSIGNED NOT NULL,
    source VARCHAR(60) NOT NULL,
    total_cents BIGINT NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, source),
    CONSTRAINT fk_user_earnings_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_stats (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    last_active_day DATE NULL,
    active_days INT UNSIGNED NOT NULL DEFAULT 0,
    current_streak INT UNSIGNED NOT NULL DEFAULT 0,
    longest_streak INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_user_stats_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO user_earnings (user_id, source, total_cents)
SELECT r.user_id, CONCAT('job:', d.code), SUM(r.payout_cents)
FROM job_runs r JOIN job_definitions d ON d.id = r.job_id
WHERE r.payout_cents > 0
GROUP BY r.user_id, d.code;

INSERT INTO user_stats (user_id, last_active_day, active_days, current_streak, longest_streak)
SELECT id, DATE(COALESCE(last_login_at, created_at)), 1, 1, 1 FROM users;
