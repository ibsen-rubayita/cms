-- HVU Voice Desk database schema
-- Import this file into an EMPTY database (phpMyAdmin > select database > Import).
-- Warning: it drops and recreates all six tables, so existing data is replaced.

DROP TABLE IF EXISTS responses;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS complaints;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    phone       VARCHAR(30) NULL,
    bio         VARCHAR(300) NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    last_login  DATETIME NULL DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL UNIQUE,
    description  VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE complaints (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_no  VARCHAR(20) NULL UNIQUE,
    user_id       INT UNSIGNED NOT NULL,
    category_id   INT UNSIGNED NOT NULL,
    title         VARCHAR(150) NOT NULL,
    description   TEXT NOT NULL,
    is_anonymous  TINYINT(1) NOT NULL DEFAULT 0,
    priority      ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'low',
    attachment_name VARCHAR(255) NULL,
    attachment_file VARCHAR(255) NULL,
    rating        TINYINT UNSIGNED NULL CHECK (rating IS NULL OR rating BETWEEN 1 AND 5),
    feedback_comment TEXT NULL,
    status        ENUM('pending', 'in_progress', 'resolved') NOT NULL DEFAULT 'pending',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at   DATETIME NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE responses (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_id  INT UNSIGNED NOT NULL,
    admin_id      INT UNSIGNED NOT NULL,
    message       TEXT NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE announcements (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(150) NOT NULL,
    tag           VARCHAR(50) NULL,
    body          TEXT NOT NULL,
    is_published  TINYINT(1) NOT NULL DEFAULT 0,
    author_id     INT UNSIGNED NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(150) NOT NULL,
    ip_address    VARCHAR(45) NOT NULL,
    attempted_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_email_time (email, attempted_at)
) ENGINE=InnoDB;

INSERT INTO categories (name, description) VALUES
    ('Academic Matters', 'Lectures, exams, grading, course content'),
    ('Fees and Payments', 'Tuition, receipts, billing errors'),
    ('Staff Conduct', 'Behaviour of lecturers or administrative staff'),
    ('Facilities and Hostel', 'Classrooms, labs, library, hostel, water, electricity'),
    ('Student Services', 'Registration, admissions, student records'),
    ('Other', 'Anything that does not fit the categories above');

-- Pre-created admin account. Email: admin@cms.com   Password: Admin@123
INSERT INTO users (full_name, email, password, role) VALUES
    ('System Admin', 'admin@cms.com', '$2y$10$odTAyArovrCI2sdGRCrZIOTuJvfipk8zgARdGMTiaaYiVBX7/If42', 'admin');
