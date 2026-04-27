-- ============================================================
-- migration_cancel_reason.sql
-- Adds cancel_reason_type + cancel_reason_detail to reservations.
-- Run once against the `gamingspothub` database.
-- ============================================================

ALTER TABLE `reservations`
    ADD COLUMN `cancel_reason_type`
        ENUM('schedule_change','found_alternative','budget_issue','technical_issue','emergency','other')
        NULL DEFAULT NULL
        AFTER `refund_issued`,

    ADD COLUMN `cancel_reason_detail`
        TEXT NULL
        AFTER `cancel_reason_type`;

-- Remove the inconvenience_fee setting — no refunds policy means it is unused.
DELETE FROM `system_settings` WHERE `setting_key` = 'inconvenience_fee';
