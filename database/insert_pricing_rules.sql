INSERT INTO system_settings (setting_key, setting_value, description)
VALUES
    ('bonus_paid_minutes', '120', 'Every X paid minutes earns free bonus time'),
    ('bonus_free_minutes', '30',  'Free minutes awarded per bonus cycle'),
    ('max_hourly_minutes', '240', 'Maximum bookable paid minutes for hourly session')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    description   = VALUES(description);

SELECT setting_key, setting_value FROM system_settings
WHERE setting_key IN ('bonus_paid_minutes','bonus_free_minutes','max_hourly_minutes');
