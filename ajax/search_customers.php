<?php
/**
 * AJAX: Customer Search
 * Returns a JSON array of registered customer accounts matching the query.
 * Only active customers with role='customer' are returned.
 *
 * Query params:
 *   q  — search string (searches full_name and email, min 1 char)
 *
 * Response: JSON array of { user_id, full_name, email }
 */
require_once __DIR__ . '/../includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
require_once __DIR__ . '/../includes/db_functions.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

// Limit to 10 results; search full_name OR email
$like = '%' . $conn->real_escape_string($q) . '%';

$stmt = $conn->prepare(
    "SELECT user_id, full_name, email
       FROM users
      WHERE role = 'customer'
        AND status = 'active'
        AND (full_name LIKE ? OR email LIKE ?)
      ORDER BY full_name
      LIMIT 10"
);
$stmt->bind_param('ss', $like, $like);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode($rows);
