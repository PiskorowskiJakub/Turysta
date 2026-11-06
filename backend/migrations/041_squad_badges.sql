-- Odznaki oddzialu: najwyzszy zdobyty stopien (brazowy..rubinowy) z data. Stopnie tylko rosna.
CREATE TABLE squad_badges (
    squad_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(20) NOT NULL,
    tier TINYINT UNSIGNED NOT NULL,
    earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (squad_id, code),
    CONSTRAINT fk_squad_badges_squad FOREIGN KEY (squad_id) REFERENCES squads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
