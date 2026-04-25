// SAVE SETTINGS
elseif ($action === 'save_settings') {
if ($user['role'] !== 'owner') {
$message = 'Only owners can update system settings.';
$messageType = 'error';
} else {
$saveable = [
'ps5_hourly_rate' => 'float',
'xbox_hourly_rate' => 'float',
'unlimited_rate' => 'float',
'controller_rental_fee' => 'float',
'extension_rate' => 'float',
'session_min_charge' => 'float',
'bonus_paid_minutes' => 'int',
'bonus_free_minutes' => 'int',
'max_hourly_minutes' => 'int',
'business_hours_open' => 'string',
'business_hours_close' => 'string',
'shop_phone' => 'string',
];
$saved = 0; $errors = [];
foreach ($saveable as $key => $type) {
if (!isset($_POST[$key])) continue;
$raw = trim($_POST[$key]);
if ($type === 'float' && (!is_numeric($raw) || (float)$raw < 0)) { $errors[]="Invalid value for $key." ; continue; }
    if ($type==='int' && (!is_numeric($raw) || (int)$raw <=0)) { $errors[]="Invalid value for $key." ; continue; }
    $val=match($type) { 'float'=> number_format((float)$raw, 2, '.', ''),
    'int' => (string)(int)$raw,
    default => $raw,
    };
    updateSetting($key, $val);
    $saved++;
    }
    global $_pricingRulesCache;
    $_pricingRulesCache = null; // bust cache — fresh values used immediately
    if ($errors) {
    $message = 'Some settings could not be saved: ' . implode(', ', $errors);
    $messageType = 'warning';
    } else {
    $message = "{$saved} setting(s) saved successfully.";
    $messageType = 'success';
    }
    }
    }