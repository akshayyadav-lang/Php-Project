<?php
/**
 * Delete Event Handler
 * Deletes an event and its registrations
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Require admin login
requireAdminLogin();

// Get event ID
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($eventId <= 0) {
    $_SESSION['error'] = 'Invalid event ID.';
    header('Location: /event-portal/admin-dashboard.php');
    exit();
}

// Check if event exists and get image path
$query = "SELECT id, image FROM events WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $eventId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$event) {
    $_SESSION['error'] = 'Event not found.';
    header('Location: /event-portal/admin-dashboard.php');
    exit();
}

// Delete event image if exists
if (!empty($event['image']) && file_exists(__DIR__ . '/../' . $event['image'])) {
    unlink(__DIR__ . '/../' . $event['image']);
}

// Delete registrations first (foreign key constraint)
$deleteRegQuery = "DELETE FROM registrations WHERE event_id = ?";
$stmt = mysqli_prepare($conn, $deleteRegQuery);
mysqli_stmt_bind_param($stmt, "i", $eventId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Delete event
$deleteQuery = "DELETE FROM events WHERE id = ?";
$stmt = mysqli_prepare($conn, $deleteQuery);
mysqli_stmt_bind_param($stmt, "i", $eventId);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = 'Event deleted successfully.';
} else {
    $_SESSION['error'] = 'Failed to delete event.';
}
mysqli_stmt_close($stmt);

header('Location: /event-portal/admin-dashboard.php');
exit();

