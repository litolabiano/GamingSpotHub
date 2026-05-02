-- PayMongo integration: add source/payment tracking columns to reservations
-- Run this once in phpMyAdmin or via MySQL CLI.

ALTER TABLE reservations
    ADD COLUMN paymongo_source_id  VARCHAR(60)  DEFAULT NULL AFTER payment_proof_status,
    ADD COLUMN paymongo_payment_id VARCHAR(60)  DEFAULT NULL AFTER paymongo_source_id,
    ADD COLUMN paymongo_status     VARCHAR(30)  DEFAULT NULL AFTER paymongo_payment_id;

-- Optional index for fast webhook lookups
CREATE INDEX idx_res_paymongo_src ON reservations (paymongo_source_id);
