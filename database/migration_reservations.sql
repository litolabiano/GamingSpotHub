-- ============================================================================
-- Good Spot Gaming Hub — Reservations Migration
-- Run this against the gamingspothub database to add the reservations table.
-- ============================================================================

USE gamingspothub;

CREATE TABLE IF NOT EXISTS reservations (
    reservation_id      INT AUTO_INCREMENT PRIMARY KEY,

    -- Who is booking
    user_id             INT NOT NULL,

    -- Which console (nullable: customer picks type, staff assigns exact unit)
    console_id          INT DEFAULT NULL,
    console_type        ENUM('PS5', 'Xbox Series X') NOT NULL,

    -- Session details
    rental_mode         ENUM('hourly', 'open_time', 'unlimited') NOT NULL DEFAULT 'hourly',
    planned_minutes     INT DEFAULT NULL,          -- only for hourly mode

    -- When
    reserved_date       DATE NOT NULL,
    reserved_time       TIME NOT NULL,

    -- Optional notes from customer
    notes               TEXT DEFAULT NULL,

    -- Downpayment (optional)
    downpayment_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    downpayment_method  ENUM('cash', 'gcash', 'credit_card') DEFAULT NULL,
    downpayment_paid    TINYINT(1) NOT NULL DEFAULT 0,

    -- Lifecycle
    status              ENUM('pending', 'confirmed', 'converted', 'cancelled', 'no_show')
                        NOT NULL DEFAULT 'pending',

    -- Audit
    created_by          INT NOT NULL,              -- user_id of whoever submitted (customer = themselves)
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_res_status      (status),
    INDEX idx_res_date        (reserved_date),
    INDEX idx_res_user        (user_id),
    INDEX idx_res_console     (console_id),
    INDEX idx_res_console_type(console_type),

    -- Foreign keys
    CONSTRAINT fk_res_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_res_console
        FOREIGN KEY (console_id) REFERENCES consoles(console_id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_res_created_by
        FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT

) ENGINE=InnoDB;
