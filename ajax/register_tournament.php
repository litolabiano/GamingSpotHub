<?php
/**
 * ajax/register_tournament.php
 * Public tournament self-registration endpoint.
 * Requires the customer to be logged in.
 * Returns JSON.
 */

require_once __DIR__ . '/../includes/session_helper.php';
require_once __DIR__ . '/../includes/db_config.php';

header('Content-Type: application/json');

// ── Auth guard ────────────────────────────────────────────────────────────────
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to register for a tournament.']);
    exit;
}

// Only customers (and walk-in) can self-register; staff use the admin panel
$userRole = $_SESSION['role'] ?? 'customer';
if (in_array($userRole, ['shopkeeper', 'owner'])) {
    echo json_encode(['success' => false, 'message' => 'Staff members cannot self-register. Use the admin panel instead.']);
    exit;
}

// ── Input validation ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$tournament_id  = (int)($_POST['tournament_id']  ?? 0);
$ign            = trim($_POST['ign']             ?? '');
$contact_number = trim($_POST['contact_number']  ?? '');
$notes          = trim($_POST['notes']           ?? '');
$user_id        = (int)($_SESSION['user_id']     ?? 0);

if (!$tournament_id || !$ign || !$contact_number) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

// ── Fetch tournament ──────────────────────────────────────────────────────────
$tStmt = $conn->prepare("SELECT tournament_id, status, max_participants FROM tournaments WHERE tournament_id = ?");
$tStmt->bind_param('i', $tournament_id);
$tStmt->execute();
$tournament = $tStmt->get_result()->fetch_assoc();

if (!$tournament) {
    echo json_encode(['success' => false, 'message' => 'Tournament not found.']);
    exit;
}

if ($tournament['status'] !== 'scheduled') {
    echo json_encode(['success' => false, 'message' => 'Registration is not currently open for this tournament.']);
    exit;
}

// ── Check participant cap ─────────────────────────────────────────────────────
$capStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tournament_participants WHERE tournament_id = ?");
$capStmt->bind_param('i', $tournament_id);
$capStmt->execute();
$capRow = $capStmt->get_result()->fetch_assoc();
if ((int)$capRow['cnt'] >= (int)$tournament['max_participants']) {
    echo json_encode(['success' => false, 'message' => 'Sorry, this tournament is full.']);
    exit;
}

// ── Check for duplicate registration ─────────────────────────────────────────
$dupStmt = $conn->prepare("SELECT participant_id FROM tournament_participants WHERE tournament_id = ? AND user_id = ?");
$dupStmt->bind_param('ii', $tournament_id, $user_id);
$dupStmt->execute();
if ($dupStmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You are already registered for this tournament.']);
    exit;
}

// ── GCash proof upload ────────────────────────────────────────────────────────
$gcash_proof = null;

if (!isset($_FILES['gcash_proof']) || $_FILES['gcash_proof']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Please upload your GCash proof of payment.']);
    exit;
}

$allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$ftype    = mime_content_type($_FILES['gcash_proof']['tmp_name']);
$fsize    = $_FILES['gcash_proof']['size'];

if (!in_array($ftype, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a JPG, PNG, or WebP image.']);
    exit;
}
if ($fsize > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5 MB.']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/gcash_proofs/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext      = strtolower(pathinfo($_FILES['gcash_proof']['name'], PATHINFO_EXTENSION));
$filename = 'tourn_' . $tournament_id . '_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['gcash_proof']['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file. Please try again.']);
    exit;
}
$gcash_proof = $filename;

// ── Insert into tournament_participants ───────────────────────────────────────
$insStmt = $conn->prepare("
    INSERT INTO tournament_participants
        (tournament_id, user_id, payment_status, ign, contact_number, gcash_proof, notes, registered_by, registration_date)
    VALUES (?, ?, 'pending', ?, ?, ?, ?, NULL, NOW())
");
$insStmt->bind_param(
    'iissss',
    $tournament_id,
    $user_id,
    $ign,
    $contact_number,
    $gcash_proof,
    $notes
);

if ($insStmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Registration submitted! We\'ll verify your GCash payment and confirm your slot soon.'
    ]);
} else {
    // Clean up uploaded file on DB error
    @unlink($destPath);
    // Check for duplicate key (race condition)
    if ($conn->errno === 1062) {
        echo json_encode(['success' => false, 'message' => 'You are already registered for this tournament.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
}
