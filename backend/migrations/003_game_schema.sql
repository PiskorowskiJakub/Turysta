CREATE TABLE worlds (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ordinal INT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    background_image VARCHAR(255) NULL,
    palette JSON NULL,
    UNIQUE KEY uq_worlds_ordinal (ordinal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chapters (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    world_id INT UNSIGNED NOT NULL,
    ordinal INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    story_text TEXT NULL,
    image VARCHAR(255) NULL,
    duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    cost_cents BIGINT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_chapters_order (world_id, ordinal),
    CONSTRAINT fk_chapters_world FOREIGN KEY (world_id) REFERENCES worlds (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE skill_definitions (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL,
    max_level INT UNSIGNED NULL,
    base_cost_cents BIGINT NOT NULL,
    cost_growth DECIMAL(6,4) NOT NULL,
    base_seconds INT UNSIGNED NOT NULL,
    time_growth DECIMAL(6,4) NOT NULL,
    icon VARCHAR(255) NULL,
    UNIQUE KEY uq_skill_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chapter_requirements (
    chapter_id INT UNSIGNED NOT NULL,
    skill_id SMALLINT UNSIGNED NOT NULL,
    level INT UNSIGNED NOT NULL,
    PRIMARY KEY (chapter_id, skill_id),
    CONSTRAINT fk_chapter_req_chapter FOREIGN KEY (chapter_id) REFERENCES chapters (id),
    CONSTRAINT fk_chapter_req_skill FOREIGN KEY (skill_id) REFERENCES skill_definitions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE player_progress (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    world_id INT UNSIGNED NOT NULL,
    chapter_id INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_progress_world FOREIGN KEY (world_id) REFERENCES worlds (id),
    CONSTRAINT fk_progress_chapter FOREIGN KEY (chapter_id) REFERENCES chapters (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chapter_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    chapter_id INT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    KEY idx_chapter_log_user (user_id),
    CONSTRAINT fk_chapter_log_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_chapter_log_chapter FOREIGN KEY (chapter_id) REFERENCES chapters (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_skills (
    user_id BIGINT UNSIGNED NOT NULL,
    skill_id SMALLINT UNSIGNED NOT NULL,
    level INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, skill_id),
    CONSTRAINT fk_user_skills_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_user_skills_skill FOREIGN KEY (skill_id) REFERENCES skill_definitions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE training_queue (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    skill_id SMALLINT UNSIGNED NOT NULL,
    target_level INT UNSIGNED NOT NULL,
    cost_cents BIGINT NOT NULL,
    status ENUM('queued','active','done','cancelled') NOT NULL DEFAULT 'queued',
    position INT UNSIGNED NOT NULL DEFAULT 0,
    started_at DATETIME NULL,
    ends_at DATETIME NULL,
    finished_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    active_flag TINYINT UNSIGNED GENERATED ALWAYS AS (IF(status = 'active', 1, NULL)) STORED,
    UNIQUE KEY uq_training_one_active (user_id, active_flag),
    KEY idx_training_user_status (user_id, status),
    KEY idx_training_ends (status, ends_at),
    CONSTRAINT fk_training_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_training_skill FOREIGN KEY (skill_id) REFERENCES skill_definitions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE job_definitions (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL,
    duration_seconds INT UNSIGNED NOT NULL,
    reward_cents BIGINT NOT NULL,
    cooldown_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    cancel_policy ENUM('proportional','none') NOT NULL DEFAULT 'proportional',
    image VARCHAR(255) NULL,
    UNIQUE KEY uq_job_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE job_runs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    job_id SMALLINT UNSIGNED NOT NULL,
    status ENUM('active','done','cancelled') NOT NULL DEFAULT 'active',
    started_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    payout_cents BIGINT NULL,
    active_flag TINYINT UNSIGNED GENERATED ALWAYS AS (IF(status = 'active', 1, NULL)) STORED,
    UNIQUE KEY uq_job_one_active (user_id, active_flag),
    KEY idx_job_user (user_id, started_at),
    KEY idx_job_ends (status, ends_at),
    CONSTRAINT fk_job_runs_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_job_runs_job FOREIGN KEY (job_id) REFERENCES job_definitions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE item_definitions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    slot ENUM('backpack','footwear','lodging','transport','extra') NOT NULL,
    price_cents BIGINT NOT NULL,
    required_level INT UNSIGNED NOT NULL DEFAULT 0,
    rarity VARCHAR(20) NOT NULL DEFAULT 'common',
    image VARCHAR(255) NULL,
    purchasable TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_item_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE item_stat_bonuses (
    item_id INT UNSIGNED NOT NULL,
    stat VARCHAR(40) NOT NULL,
    value INT NOT NULL,
    PRIMARY KEY (item_id, stat),
    CONSTRAINT fk_item_bonus_item FOREIGN KEY (item_id) REFERENCES item_definitions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    acquired_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    equipped_slot VARCHAR(20) NULL,
    UNIQUE KEY uq_user_items_slot (user_id, equipped_slot),
    KEY idx_user_items_user (user_id),
    CONSTRAINT fk_user_items_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_user_items_item FOREIGN KEY (item_id) REFERENCES item_definitions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE badge_definitions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    icon VARCHAR(255) NULL,
    category VARCHAR(40) NOT NULL,
    tier ENUM('bronze','silver','gold','special') NOT NULL,
    condition_type VARCHAR(40) NOT NULL,
    condition_params JSON NULL,
    UNIQUE KEY uq_badge_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_badges (
    user_id BIGINT UNSIGNED NOT NULL,
    badge_id INT UNSIGNED NOT NULL,
    earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id),
    CONSTRAINT fk_user_badges_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_user_badges_badge FOREIGN KEY (badge_id) REFERENCES badge_definitions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(40) NOT NULL,
    payload JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    KEY idx_notifications_user (user_id, read_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE daily_facts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    text VARCHAR(500) NOT NULL,
    world_id INT UNSIGNED NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_daily_facts_world FOREIGN KEY (world_id) REFERENCES worlds (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
