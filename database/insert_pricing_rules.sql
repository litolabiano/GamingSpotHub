INSERT INTO system_settings (setting_key, setting_value, description)
VALUES ('session_min_charge', '50', 'Minimum charge (₱) for sessions up to 30 minutes')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description);

SELECT setting_key, setting_value FROM system_settings
WHERE setting_key IN ('session_min_charge','ps5_hourly_rate','xbox_hourly_rate','bonus_paid_minutes','bonus_free_minutes','max_hourly_minutes');
