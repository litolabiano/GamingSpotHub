-- Migration: link gaming_sessions back to their source reservation
-- Run once via phpMyAdmin or MySQL CLI

ALTER TABLE gaming_sessions
  ADD COLUMN IF NOT EXISTS source_reservation_id INT DEFAULT NULL
    AFTER planned_minutes,
  ADD CONSTRAINT fk_gs_source_reservation
    FOREIGN KEY (source_reservation_id)
    REFERENCES reservations(reservation_id)
    ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_gs_source_res
  ON gaming_sessions (source_reservation_id);
