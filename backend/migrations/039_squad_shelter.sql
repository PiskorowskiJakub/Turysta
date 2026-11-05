-- Schronisko oddzialu (Etap 3): magazyn skladnikow (zar, pieczatki, dukaty), pomieszczenia bez maksymalnego poziomu i wplaty czlonkow.
ALTER TABLE squads
    ADD COLUMN ember_spent BIGINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN vault_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN stamp_carry DECIMAL(6,4) NOT NULL DEFAULT 0,
    ADD COLUMN deposit_record_cents BIGINT UNSIGNED NOT NULL DEFAULT 0;

-- Aktualny poziom pomieszczenia (brak wiersza = poziom 0).
CREATE TABLE squad_rooms (
    squad_id BIGINT UNSIGNED NOT NULL,
    room VARCHAR(20) NOT NULL,
    level INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (squad_id, room),
    CONSTRAINT fk_squad_rooms_squad FOREIGN KEY (squad_id) REFERENCES squads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rozbudowy: skladniki schodza z magazynu w chwili rozpoczecia, a poziom przybywa po zakonczeniu budowy.
-- active = 1 tylko dla trwajacej rozbudowy (unikalny klucz pilnuje, zeby oddzial budowal jedno pomieszczenie naraz).
CREATE TABLE squad_builds (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    squad_id BIGINT UNSIGNED NOT NULL,
    room VARCHAR(20) NOT NULL,
    level INT UNSIGNED NOT NULL,
    ember BIGINT UNSIGNED NOT NULL,
    stamps INT UNSIGNED NOT NULL,
    cents BIGINT UNSIGNED NOT NULL,
    started_by BIGINT UNSIGNED NULL,
    started_at DATETIME NOT NULL,
    finish_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    active TINYINT NULL DEFAULT 1,
    UNIQUE KEY uq_build_active (squad_id, active),
    KEY idx_builds_due (active, finish_at),
    KEY idx_builds_squad (squad_id, id),
    CONSTRAINT fk_builds_squad FOREIGN KEY (squad_id) REFERENCES squads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wplaty dukatow do magazynu (do dziennego limitu na osobe i rekordu wplat oddzialu).
CREATE TABLE squad_deposits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    squad_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    day DATE NOT NULL,
    cents BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_deposits_user_day (user_id, day),
    KEY idx_deposits_squad_day (squad_id, day),
    CONSTRAINT fk_deposits_squad FOREIGN KEY (squad_id) REFERENCES squads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
