<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Secure Session Logout Handler
 * BCA 5th Semester Major Project 1
 */

require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Delete session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start fresh session just for the flash message
session_start();
$_SESSION['flash_type'] = 'info';
$_SESSION['flash_message'] = 'You have been successfully signed out of the portal.';

header('Location: ' . BASE_URL . 'login.php');
exit;
