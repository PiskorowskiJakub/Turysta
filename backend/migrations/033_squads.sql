-- Oddzialy (Etap 1): oddzial, czlonkowie z rolami, zaproszenia i zgloszenia, ognisko z zarem, kronika, moderacja nazw.

-- Dni aktywnosci gracza (jeden wiersz na dzien gry): potrzebne, by policzyc, ilu czlonkow oddzialu bylo aktywnych danego dnia.
CREATE TABLE user_active_days (
    user_id BIGINT UNSIGNED NOT NULL,
    day DATE NOT NULL,
    PRIMARY KEY (user_id, day),
    KEY idx_active_days_day (day),
    CONSTRAINT fk_active_days_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO user_active_days (user_id, day)
SELECT user_id, last_active_day FROM user_stats WHERE last_active_day IS NOT NULL;

ALTER TABLE users
    ADD COLUMN show_squad_title TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN squad_left_at DATETIME NULL;

CREATE TABLE squads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(40) NOT NULL,
    -- Klucz unikalnosci: nazwa bez wielkosci liter, polskich znakow i spacji. NULL po archiwizacji (nazwa wraca do puli).
    name_key VARCHAR(60) NULL,
    specialization VARCHAR(20) NOT NULL DEFAULT 'ogolna',
    join_mode ENUM('open','request','invite') NOT NULL DEFAULT 'open',
    created_by BIGINT UNSIGNED NULL,
    ember BIGINT UNSIGNED NOT NULL DEFAULT 0,
    flame_streak INT UNSIGNED NOT NULL DEFAULT 0,
    best_streak INT UNSIGNED NOT NULL DEFAULT 0,
    lit_days INT UNSIGNED NOT NULL DEFAULT 0,
    last_fire_day DATE NULL,
    name_changed_at DATETIME NULL,
    spec_changed_at DATETIME NULL,
    name_locked TINYINT(1) NOT NULL DEFAULT 0,
    name_forced TINYINT(1) NOT NULL DEFAULT 0,
    archived_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_squads_name_key (name_key),
    KEY idx_squads_archived (archived_at, specialization),
    CONSTRAINT fk_squads_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jeden gracz w jednym oddziale (klucz glowny po user_id).
CREATE TABLE squad_members (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    squad_id BIGINT UNSIGNED NOT NULL,
    role ENUM('leader','deputy','guide','quartermaster','chronicler','recruiter','member') NOT NULL DEFAULT 'member',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_squad_members_squad (squad_id, role),
    CONSTRAINT fk_squad_members_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_squad_members_squad FOREIGN KEY (squad_id) REFERENCES squads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zgloszenia do oddzialu (od gracza) i zaproszenia (od oddzialu).
CREATE TABLE squad_requests (
    squad_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    kind ENUM('request','invite') NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (squad_id, user_id),
    KEY idx_squad_requests_user (user_id, kind),
    CONSTRAINT fk_squad_requests_squad FOREIGN KEY (squad_id) REFERENCES squads (id) ON DELETE CASCADE,
    CONSTRAINT fk_squad_requests_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ognisko: wynik kazdego rozliczonego dnia gry.
CREATE TABLE squad_fire_days (
    squad_id BIGINT UNSIGNED NOT NULL,
    day DATE NOT NULL,
    members INT UNSIGNED NOT NULL,
    active INT UNSIGNED NOT NULL,
    needed INT UNSIGNED NOT NULL,
    lit TINYINT(1) NOT NULL,
    streak INT UNSIGNED NOT NULL,
    ember INT UNSIGNED NOT NULL,
    PRIMARY KEY (squad_id, day),
    CONSTRAINT fk_fire_days_squad FOREIGN KEY (squad_id) REFERENCES squads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kronika: wylacznie automatyczne wpisy. Nazwy graczy sa doczytywane przy wyswietlaniu (prywatnosc i zmiana nazwy postaci).
CREATE TABLE squad_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    squad_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(20) NOT NULL,
    ref_key VARCHAR(60) NOT NULL,
    payload JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_squad_event_ref (squad_id, type, ref_key),
    KEY idx_squad_events_list (squad_id, created_at, id),
    CONSTRAINT fk_squad_events_squad FOREIGN KEY (squad_id) REFERENCES squads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historia nazw (dla administratora) i zgloszenia nazw od graczy.
CREATE TABLE squad_name_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    squad_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(40) NOT NULL,
    changed_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_name_history_squad (squad_id, id),
    CONSTRAINT fk_name_history_squad FOREIGN KEY (squad_id) REFERENCES squads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE squad_name_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    squad_id BIGINT UNSIGNED NOT NULL,
    reporter_id BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(20) NOT NULL,
    reported_name VARCHAR(40) NOT NULL,
    status ENUM('new','resolved','dismissed') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_name_report (squad_id, reporter_id),
    KEY idx_name_reports_status (status, created_at),
    CONSTRAINT fk_name_reports_squad FOREIGN KEY (squad_id) REFERENCES squads (id) ON DELETE CASCADE,
    CONSTRAINT fk_name_reports_user FOREIGN KEY (reporter_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Slowa zakazane w nazwach oddzialow (klucz juz znormalizowany: male litery, bez polskich znakow i spacji).
CREATE TABLE banned_words (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    word_key VARCHAR(40) NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_banned_word (word_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
