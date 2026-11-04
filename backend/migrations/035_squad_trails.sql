-- Szlaki oddzialow (Etap 2): tygodniowa wyprawa, wpisowe, codzienny Marsz, pieczatki na wspolna pule oddzialu i odznaka "Szlakowiec".
ALTER TABLE squads
    ADD COLUMN stamps INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN trails_done INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE squad_trails (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    squad_id BIGINT UNSIGNED NOT NULL,
    -- Poniedzialek tygodnia gry (Europe/Warsaw), w ktorym wyprawa sie zaczyna; konczy sie w niedziele.
    week_start DATE NOT NULL,
    difficulty ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
    theme VARCHAR(40) NOT NULL,
    status ENUM('active','done') NOT NULL DEFAULT 'active',
    percent DECIMAL(5,2) NULL,
    stamps TINYINT UNSIGNED NULL,
    participants SMALLINT UNSIGNED NULL,
    eligible SMALLINT UNSIGNED NULL,
    changed_by BIGINT UNSIGNED NULL,
    finalized_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_trail_week (squad_id, week_start),
    KEY idx_trail_status (status, week_start),
    CONSTRAINT fk_trail_squad FOREIGN KEY (squad_id) REFERENCES squads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE squad_trail_members (
    trail_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    joined_day DATE NOT NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_cents INT UNSIGNED NOT NULL DEFAULT 0,
    -- Wypelniane przy zamknieciu wyprawy.
    eligible TINYINT(1) NULL,
    active_days SMALLINT UNSIGNED NULL,
    march_days SMALLINT UNSIGNED NULL,
    reward_cents INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (trail_id, user_id),
    KEY idx_trail_member_user (user_id, eligible),
    CONSTRAINT fk_trail_member_trail FOREIGN KEY (trail_id) REFERENCES squad_trails (id) ON DELETE CASCADE,
    CONSTRAINT fk_trail_member_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE squad_trail_marches (
    trail_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    day DATE NOT NULL,
    PRIMARY KEY (trail_id, user_id, day),
    CONSTRAINT fk_trail_march_trail FOREIGN KEY (trail_id) REFERENCES squad_trails (id) ON DELETE CASCADE,
    CONSTRAINT fk_trail_march_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Odznaka "Szlakowiec": liczba ukonczonych wypraw (oddzial zdobyl co najmniej 1 pieczatke, a gracz byl uczestnikiem z min. 3 dniami aktywnosci).
INSERT INTO badge_definitions (code, name, category, tier, condition_type, skill_code, threshold) VALUES
 ('special_trails.bronze', 'Szlakowiec', 'special', 'bronze', 'squad_trails', NULL, 4),
 ('special_trails.silver', 'Szlakowiec', 'special', 'silver', 'squad_trails', NULL, 12),
 ('special_trails.gold', 'Szlakowiec', 'special', 'gold', 'squad_trails', NULL, 26),
 ('special_trails.diamond', 'Szlakowiec', 'special', 'diamond', 'squad_trails', NULL, 52),
 ('special_trails.ruby', 'Szlakowiec', 'special', 'ruby', 'squad_trails', NULL, 104);
