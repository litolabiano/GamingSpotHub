-- ============================================================================
-- Good Spot Gaming Hub - Auth Migration
-- Adds email verification and password reset columns to users table
-- ============================================================================

USE gamingspothub;

ALTER TABLE users
    ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN verification_token VARCHAR(64) DEFAULT NULL AFTER email_verified,
    ADD COLUMN verification_expires DATETIME DEFAULT NULL AFTER verification_token,
    ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL AFTER verification_expires,
    ADD COLUMN reset_expires DATETIME DEFAULT NULL AFTER reset_token;

-- Update existing users to be verified
UPDATE users SET email_verified = 1 WHERE user_id > 0;
