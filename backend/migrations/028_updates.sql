-- Aktualizacje gry (dziennik zmian) i powiazanie z ogloszeniami: ogloszenie podaje ogolnik, szczegoly sa w aktualizacji.
CREATE TABLE updates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    body TEXT NOT NULL,
    released_on DATE NOT NULL,
    published TINYINT(1) NOT NULL DEFAULT 1,
    author_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_updates_list (published, released_on, id),
    CONSTRAINT fk_updates_author FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE announcements
    ADD COLUMN update_id BIGINT UNSIGNED NULL AFTER pinned,
    ADD CONSTRAINT fk_announcements_update FOREIGN KEY (update_id) REFERENCES updates (id) ON DELETE SET NULL;
