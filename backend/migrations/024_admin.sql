ALTER TABLE users
    ADD COLUMN suspended_until DATETIME NULL AFTER status,
    ADD COLUMN status_reason VARCHAR(255) NULL AFTER suspended_until;

ALTER TABLE login_events ADD KEY idx_login_events_time (logged_in_at);

CREATE TABLE admin_audit (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(40) NOT NULL,
    target_user_id BIGINT UNSIGNED NULL,
    details TEXT NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admin_audit_time (created_at),
    KEY idx_admin_audit_target (target_user_id, created_at),
    KEY idx_admin_audit_admin (admin_id, created_at),
    CONSTRAINT fk_admin_audit_admin FOREIGN KEY (admin_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_admin_audit_target FOREIGN KEY (target_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
