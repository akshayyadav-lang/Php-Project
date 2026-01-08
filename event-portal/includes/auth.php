<?php
/**
 * Authentication Functions
 * Session-based admin authentication
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_email']);
}

/**
 * Require admin login - redirect if not logged in
 */
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: /event-portal/login.php');
        exit();
    }
}

/**
 * Get current admin ID
 */
function getAdminId() {
    return isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
}

/**
 * Get current admin email
 */
function getAdminEmail() {
    return isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : null;
}

/**
 * Login admin
 */
function loginAdmin($adminId, $adminEmail) {
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_email'] = $adminEmail;
}

/**
 * Logout admin
 */
function logoutAdmin() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_email']);
    session_destroy();
}

