-- ============================================================
-- Databáze pro projekt Casting (BVWA2)
-- Import:  phpMyAdmin -> Import, nebo  mysql -u root < database/schema.sql
-- POZOR: smaže a znovu vytvoří tabulky (jen pro vývoj).
-- ============================================================

CREATE DATABASE IF NOT EXISTS casting_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE casting_db;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS castings;
DROP TABLE IF EXISTS users;

-- Uživatelé a jejich role
CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email         VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name    VARCHAR(100) NOT NULL,
    last_name     VARCHAR(100) NOT NULL,
    phone         VARCHAR(30)  NULL,
    bio           TEXT         NULL,
    photo         VARCHAR(255) NULL,            -- jen název souboru v uploads/profile_photos/
    role          ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- Castingové projekty
CREATE TABLE castings (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title        VARCHAR(200) NOT NULL,
    description  TEXT         NOT NULL,
    location     VARCHAR(200) NULL,
    casting_date DATETIME     NULL,
    status       ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_by   INT UNSIGNED NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_castings_status (status),
    CONSTRAINT fk_castings_user FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Přihlášky uživatelů na castingy
CREATE TABLE applications (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    casting_id INT UNSIGNED NOT NULL,
    message    TEXT NULL,
    status     ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_application (user_id, casting_id),   -- jedna přihláška na casting
    CONSTRAINT fk_app_user    FOREIGN KEY (user_id)    REFERENCES users (id)    ON DELETE CASCADE,
    CONSTRAINT fk_app_casting FOREIGN KEY (casting_id) REFERENCES castings (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Interní zprávy mezi uživateli
CREATE TABLE messages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sender_id   INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    subject     VARCHAR(200) NULL,
    body        TEXT NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_inbox (receiver_id, is_read),
    CONSTRAINT fk_msg_sender   FOREIGN KEY (sender_id)   REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_receiver FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;
