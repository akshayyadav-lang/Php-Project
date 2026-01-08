<?php
/**
 * Admin Logout
 */

require_once __DIR__ . '/includes/auth.php';

logoutAdmin();
header('Location: /event-portal/index.php');
exit();

