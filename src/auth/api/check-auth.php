<?php
/**
 * Check Authentication Status
 * Returns JSON indicating if user is logged in
 */

session_start();

header("Content-Type: application/json; charset=UTF-8");

$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

echo json_encode([
    'logged_in' => $logged_in,
    'user' => $logged_in ? [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'is_admin' => $_SESSION['is_admin'] ?? 0
    ] : null
]);




