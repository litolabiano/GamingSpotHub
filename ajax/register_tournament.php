<?php
/**
 * ajax/register_tournament.php
 * NOTE: Tournament registration now uses the PayMongo GCash redirect flow
 * handled directly in tournament_register.php (same pattern as reserve.php).
 * This endpoint is kept as a stub for any legacy calls.
 */
header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    'success' => false,
    'message' => 'Tournament registration is now handled via tournament_register.php. Please use the registration page directly.'
]);
