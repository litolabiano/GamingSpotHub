-- ============================================================================
-- Good Spot Gaming Hub - Database Schema
-- Console Shop Management System
-- Don Placido Avenue, Dasmariñas
-- ============================================================================

CREATE DATABASE IF NOT EXISTS gamingspothub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gamingspothub;

-- ============================================================================
-- TABLE 1: users
-- All system users: Customer, Shopkeeper, Owner
-- ============================================================================

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role ENUM('customer', 'shopkeeper', 'owner') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;


-- ============================================================================
-- TABLE 2: consoles
-- PS5 and Xbox Series X units
-- ============================================================================

CREATE TABLE IF NOT EXISTS consoles (
    console_id INT AUTO_INCREMENT PRIMARY KEY,
    console_name VARCHAR(50) NOT NULL,
    console_type ENUM('PS5', 'Xbox Series X') NOT NULL,
    unit_number VARCHAR(10) NOT NULL UNIQUE,
    status ENUM('available', 'in_use', 'maintenance') NOT NULL DEFAULT 'available',
    hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_consoles_status (status),
    INDEX idx_consoles_type (console_type)
) ENGINE=InnoDB;


-- ============================================================================
-- TABLE 3: gaming_sessions
-- Rental sessions with time tracking (hourly / open_time / unlimited)
-- ============================================================================

CREATE TABLE IF NOT EXISTS gaming_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    console_id INT NOT NULL,
    rental_mode ENUM('hourly', 'open_time', 'unlimited') NOT NULL,
    start_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME DEFAULT NULL,
    duration_minutes INT DEFAULT NULL,
    hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_cost DECIMAL(10,2) DEFAULT NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_sessions_status (status),
    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_console (console_id),
    INDEX idx_sessions_start (start_time),

    CONSTRAINT fk_sessions_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_sessions_console
        FOREIGN KEY (console_id) REFERENCES consoles(console_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_sessions_created_by
        FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================================
-- TABLE 4: additional_requests
-- Extra hours, controller rentals, etc.
-- ============================================================================

CREATE TABLE IF NOT EXISTS additional_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    request_type ENUM('extra_hours', 'controller_rental', 'other') NOT NULL,
    description TEXT DEFAULT NULL,
    extra_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_addreq_session (session_id),
    INDEX idx_addreq_status (status),

    CONSTRAINT fk_addreq_session
        FOREIGN KEY (session_id) REFERENCES gaming_sessions(session_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================================
-- TABLE 5: transactions
-- Payment records
-- ============================================================================

CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash', 'gcash', 'credit_card') NOT NULL DEFAULT 'cash',
    payment_status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_trans_status (payment_status),
    INDEX idx_trans_date (transaction_date),
    INDEX idx_trans_user (user_id),

    CONSTRAINT fk_trans_session
        FOREIGN KEY (session_id) REFERENCES gaming_sessions(session_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_trans_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_trans_processed_by
        FOREIGN KEY (processed_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================================
-- TABLE 6: games
-- Game library for each console
-- ============================================================================

CREATE TABLE IF NOT EXISTS games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    game_name VARCHAR(150) NOT NULL,
    console_type ENUM('PS5', 'Xbox Series X', 'Both') NOT NULL,
    genre VARCHAR(50) DEFAULT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    is_new_release TINYINT(1) NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    added_date DATE NOT NULL DEFAULT (CURRENT_DATE),

    INDEX idx_games_console (console_type),
    INDEX idx_games_available (is_available),
    INDEX idx_games_genre (genre),
    INDEX idx_games_new (is_new_release)
) ENGINE=InnoDB;


-- ============================================================================
-- TABLE 7: game_requests
-- Customer game installation requests
-- ============================================================================

CREATE TABLE IF NOT EXISTS game_requests (
    gr_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_name VARCHAR(150) NOT NULL,
    console_type ENUM('PS5', 'Xbox Series X') NOT NULL,
    message TEXT DEFAULT NULL,
    status ENUM('pending', 'approved', 'installed', 'denied') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME DEFAULT NULL,

    INDEX idx_gr_user (user_id),
    INDEX idx_gr_status (status),

    CONSTRAINT fk_gr_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================================
-- TABLE 8: tournaments
-- Monthly tournament management
-- ============================================================================

CREATE TABLE IF NOT EXISTS tournaments (
    tournament_id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_name VARCHAR(150) NOT NULL,
    game_id INT NOT NULL,
    console_type ENUM('PS5', 'Xbox Series X') NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    entry_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    prize_pool DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    max_participants INT NOT NULL DEFAULT 16,
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
    announcement TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tourn_status (status),
    INDEX idx_tourn_start (start_date),

    CONSTRAINT fk_tourn_game
        FOREIGN KEY (game_id) REFERENCES games(game_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================================
-- TABLE 9: tournament_participants
-- Tournament registrations
-- ============================================================================

CREATE TABLE IF NOT EXISTS tournament_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
    placement INT DEFAULT NULL,
    prize_amount DECIMAL(10,2) DEFAULT NULL,

    INDEX idx_tp_tournament (tournament_id),
    INDEX idx_tp_user (user_id),
    INDEX idx_tp_payment (payment_status),

    UNIQUE KEY uk_tp_entry (tournament_id, user_id),

    CONSTRAINT fk_tp_tournament
        FOREIGN KEY (tournament_id) REFERENCES tournaments(tournament_id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_tp_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================================
-- TABLE 10: reports
-- Generated reports for owner
-- ============================================================================

CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('daily_sales', 'rental_records', 'console_usage', 'tournament') NOT NULL,
    generated_by INT NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_reports_type (report_type),
    INDEX idx_reports_date (created_at),

    CONSTRAINT fk_reports_user
        FOREIGN KEY (generated_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================================
-- TABLE 11: system_settings
-- Shop configuration
-- ============================================================================

CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
