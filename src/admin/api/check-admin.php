<?php
/**
 * Check Admin Access
 * Verifies if the current user is an admin
 */

session_start();

header("Content-Type: application/json; charset=UTF-8");

// CORS headers - must specify origin, not wildcard, when using credentials
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header("Access-Control-Allow-Origin: " . $origin);
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
// Check is_admin - handle both integer and string values
$is_admin = false;
if (isset($_SESSION['is_admin'])) {
    $adminValue = $_SESSION['is_admin'];
    $is_admin = ($adminValue == 1 || $adminValue === true || $adminValue === '1' || $adminValue === 1);
}

// Debug info (remove in production)
$debug_info = [
    'session_id' => session_id(),
    'has_session' => !empty($_SESSION),
    'logged_in' => $logged_in,
    'is_admin_value' => $_SESSION['is_admin'] ?? 'not set',
    'is_admin_check' => $is_admin
];

if (!$logged_in) {
    echo json_encode([
        'success' => false,
        'error' => 'Not logged in',
        'message' => 'Please log in to access this page',
        'debug' => $debug_info // Remove this in production
    ]);
    exit;
}

if (!$is_admin) {
    echo json_encode([
        'success' => false,
        'error' => 'Access denied',
        'message' => 'Only administrators can access this page',
        'debug' => $debug_info // Remove this in production
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Admin access granted',
    'user' => [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null
    ]
]);

