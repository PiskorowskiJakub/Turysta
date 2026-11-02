-- Zgoda na regulamin i politykę prywatności, moment od którego adres e-mail czeka na potwierdzenie,
-- oraz zgłoszenia błędów od graczy.
ALTER TABLE users
    ADD COLUMN terms_accepted_at DATETIME NULL AFTER email_verified_at,
    ADD COLUMN email_unverified_since DATETIME NULL AFTER terms_accepted_at;

CREATE TABLE bug_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    page VARCHAR(255) NULL,
    user_agent VARCHAR(255) NULL,
    status ENUM('new','in_progress','resolved') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_bug_reports_status (status, created_at),
    CONSTRAINT fk_bug_reports_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
