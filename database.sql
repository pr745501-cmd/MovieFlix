-- ═══════════════════════════════════════════════════
--  MovieFlix Database Schema
--  Run this to set up a fresh install
-- ═══════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS movie_system;
USE movie_system;

-- ── Users ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) UNIQUE NOT NULL,
    email      VARCHAR(150) UNIQUE DEFAULT NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Movies ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS movies (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    director     VARCHAR(255) NOT NULL,
    release_year INT NOT NULL,
    genre        VARCHAR(100) NOT NULL,
    image        VARCHAR(255) DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Bookings ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    movie_id          INT NOT NULL,
    name              VARCHAR(255) NOT NULL,
    screen            VARCHAR(50) NOT NULL,
    seat_numbers      VARCHAR(255) NOT NULL,
    seats             INT NOT NULL,
    show_date         DATE NOT NULL,
    show_time         VARCHAR(20) NOT NULL,
    amount            INT DEFAULT 0,
    payment_intent_id VARCHAR(255),
    payment_status    VARCHAR(50) DEFAULT 'pending',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- ═══════════════════════════════════════════════════
--  IF YOU ALREADY HAVE THE DATABASE, run only these:
-- ═══════════════════════════════════════════════════

-- Add email column to existing users table (safe to run even if exists)
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(150) UNIQUE DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE movies ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
