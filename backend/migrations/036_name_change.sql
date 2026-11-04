-- Zmiana nazwy postaci (raz na pol roku) i historia zmian. Stara nazwa jest zarezerwowana na kilka miesiecy, zeby nikt jej nie przejal.
ALTER TABLE users ADD COLUMN name_changed_at DATETIME NULL;

CREATE TABLE user_name_changes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    old_name VARCHAR(40) NOT NULL,
    new_name VARCHAR(40) NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_name_changes_user (user_id, changed_at),
    KEY idx_name_changes_old (old_name, changed_at),
    CONSTRAINT fk_name_changes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
