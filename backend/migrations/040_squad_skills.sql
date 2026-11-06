-- Umiejetnosci oddzialowe (etap 4): osobny slot treningowy, poziomy gracza i tarcza ogniska.
CREATE TABLE user_squad_skills (
    user_id BIGINT UNSIGNED NOT NULL,
    skill VARCHAR(20) NOT NULL,
    level INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, skill),
    CONSTRAINT fk_user_squad_skills_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE squad_skill_queue (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    skill VARCHAR(20) NOT NULL,
    target_level INT UNSIGNED NOT NULL,
    cost_cents BIGINT NOT NULL,
    duration_seconds INT UNSIGNED NOT NULL,
    status ENUM('queued','active','done','cancelled') NOT NULL DEFAULT 'queued',
    position INT UNSIGNED NOT NULL DEFAULT 0,
    started_at DATETIME NULL,
    ends_at DATETIME NULL,
    finished_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    active_flag TINYINT UNSIGNED GENERATED ALWAYS AS (IF(status = 'active', 1, NULL)) STORED,
    UNIQUE KEY uq_squad_skill_one_active (user_id, active_flag),
    KEY idx_squad_skill_user_status (user_id, status),
    KEY idx_squad_skill_ends (status, ends_at),
    CONSTRAINT fk_squad_skill_queue_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarcza ogniska: liczba ladunkow i dzien, w ktorym wroci kolejny.
ALTER TABLE squads
    ADD COLUMN shield_charges TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN shield_next_day DATE NULL;

-- Nowe trudnosci tras odblokowuje Kartografia.
ALTER TABLE squad_trails MODIFY difficulty ENUM('easy','medium','hard','expert','legend') NOT NULL DEFAULT 'medium';
