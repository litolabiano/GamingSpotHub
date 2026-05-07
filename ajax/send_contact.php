<?php
/**
 * AJAX - Send Contact Form
 * Handles form validation, admin notification, and user confirmation email.
 */
require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$message = '';
$messageType = '';
if (!verifyCsrf($message, $messageType)) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// ── Collect and Sanitize ───────────────────────────────────────────────────
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$phone   = trim($_POST['phone']   ?? '');
$msgBody = trim($_POST['message'] ?? '');

if (!$name || !$email || !$msgBody) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

// ── Configuration ─────────────────────────────────────────────────────────
$contact_email = getSetting('contact_email')   ?? 'goodspotgaminghub@gmail.com';
$shop_name     = getSetting('shop_name')       ?? 'Good Spot Gaming Hub';

$brevo_api_key = getSetting('brevo_api_key');
$sender_email  = getSetting('sender_email')    ?? 'goodspotgaminghub@gmail.com';

/**
 * Send email using Brevo (formerly Sendinblue) Transactional Email API v3
 */
function sendEmailViaBrevo($to, $subject, $content, $replyTo = null) {
    global $brevo_api_key, $sender_email, $shop_name;

    if (!$brevo_api_key) return false;

    $url = "https://api.brevo.com/v3/smtp/email";
    $data = [
        "sender"      => ["name" => $shop_name, "email" => $sender_email],
        "to"          => [["email" => $to]],
        "subject"     => $subject,
        "textContent" => $content
    ];

    if ($replyTo) {
        $data["replyTo"] = ["email" => $replyTo];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "api-key: " . $brevo_api_key,
        "content-type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    } else {
        $respObj = json_decode($response, true);
        $errMsg = $respObj['message'] ?? 'Unknown Error';
        error_log("Brevo API Error ($httpCode): " . $response);
        
        // If it's a 401 with "Key not found", we give a hint to the admin
        if ($httpCode == 401 && stripos($errMsg, 'Key not found') !== false) {
             return "INVALID_KEY";
        }
        return false;
    }
}

// ── 1. Admin Notification ──────────────────────────────────────────────────
$admin_subject = "New Inquiry: " . $name;
$admin_body    = "You have received a new message from the contact form.\n\n" .
                 "--------------------------------------------------\n" .
                 "Name:    $name\n" .
                 "Email:   $email\n" .
                 "Phone:   " . ($phone ?: 'Not provided') . "\n" .
                 "--------------------------------------------------\n\n" .
                 "Message:\n$msgBody\n\n" .
                 "--\nSent via $shop_name Website";

$sent_admin = sendEmailViaBrevo($contact_email, $admin_subject, $admin_body, $email);

// ── 2. User Confirmation ───────────────────────────────────────────────────
$user_subject = "We've received your message - $shop_name";
$user_body    = "Hello $name,\n\n" .
                "Thank you for reaching out to $shop_name. We have received your inquiry and will get back to you as soon as possible.\n\n" .
                "Your Message Summary:\n" .
                "\"$msgBody\"\n\n" .
                "Best regards,\n" .
                "The $shop_name Team";

$sent_user = sendEmailViaBrevo($email, $user_subject, $user_body);

if ($sent_admin === true) {
    echo json_encode([
        'success' => true, 
        'message' => 'Message sent! We\'ll get back to you soon.'
    ]);
} else {
    $errorMsg = 'The system could not send the email. Please ensure your Brevo API Key is configured in the admin panel.';
    if ($sent_admin === "INVALID_KEY") {
        $errorMsg = 'Brevo rejected the API Key. Please ensure you are using a valid v3 API Key (starting with xkeysib-) and NOT the SMTP Key (xsmtpsib-).';
    }
    echo json_encode([
        'success' => false,
        'message' => $errorMsg
    ]);
}
