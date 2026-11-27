-- Upgrade an existing Voice Desk database without dropping user data.
-- Import cms.sql only for a new/empty database; use this file for an existing install.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL AFTER email,
    ADD COLUMN IF NOT EXISTS bio VARCHAR(300) NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
    ADD COLUMN IF NOT EXISTS last_login DATETIME NULL DEFAULT NULL AFTER is_active;

ALTER TABLE complaints
    ADD COLUMN IF NOT EXISTS priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'low' AFTER is_anonymous,
    ADD COLUMN IF NOT EXISTS attachment_name VARCHAR(255) NULL AFTER priority,
    ADD COLUMN IF NOT EXISTS attachment_file VARCHAR(255) NULL AFTER attachment_name,
    ADD COLUMN IF NOT EXISTS rating TINYINT UNSIGNED NULL AFTER attachment_file,
    ADD COLUMN IF NOT EXISTS feedback_comment TEXT NULL AFTER rating,
    ADD COLUMN IF NOT EXISTS resolved_at DATETIME NULL DEFAULT NULL AFTER updated_at;

CREATE TABLE IF NOT EXISTS announcements (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(150) NOT NULL,
    tag           VARCHAR(50) NULL,
    body          TEXT NOT NULL,
    is_published  TINYINT(1) NOT NULL DEFAULT 0,
    author_id     INT UNSIGNED NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(150) NOT NULL,
    ip_address    VARCHAR(45) NOT NULL,
    attempted_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_email_time (email, attempted_at)
) ENGINE=InnoDB;