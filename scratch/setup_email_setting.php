<?php
require 'includes/db_config.php';
$res = $conn->query("SELECT * FROM system_settings WHERE setting_key = 'contact_email'");
if($res->num_rows == 0) {
    $conn->query("INSERT INTO system_settings (setting_key, setting_value) VALUES ('contact_email', 'goodspotgaminghub@gmail.com')");
    echo "Created contact_email setting.";
} else {
    echo "contact_email setting exists.";
}
