<?php
/**
 * View Registrations Page
 * Shows all registrations for a specific event
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Require admin login
requireAdminLogin();

$pageTitle = 'Event Registrations - Admin Dashboard';

// Get event ID
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($eventId <= 0) {
    header('Location: /event-portal/admin-dashboard.php');
    exit();
}

// Fetch event details
$eventQuery = "SELECT * FROM events WHERE id = ?";
$stmt = mysqli_prepare($conn, $eventQuery);
mysqli_stmt_bind_param($stmt, "i", $eventId);
mysqli_stmt_execute($stmt);
$eventResult = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($eventResult);
mysqli_stmt_close($stmt);

if (!$event) {
    header('Location: /event-portal/admin-dashboard.php');
    exit();
}

// Fetch registrations for this event
$regQuery = "SELECT * FROM registrations WHERE event_id = ? ORDER BY id DESC";
$stmt = mysqli_prepare($conn, $regQuery);
mysqli_stmt_bind_param($stmt, "i", $eventId);
mysqli_stmt_execute($stmt);
$regResult = mysqli_stmt_get_result($stmt);

$registrations = [];
while ($row = mysqli_fetch_assoc($regResult)) {
    $registrations[] = $row;
}
mysqli_stmt_close($stmt);

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Registrations for: <?php echo htmlspecialchars($event['title']); ?></h2>
    <p><strong>Total Registrations:</strong> <?php echo count($registrations); ?> / <?php echo $event['max_seats']; ?></p>
    <div class="mt-2">
        <a href="/event-portal/admin-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php if (empty($registrations)): ?>
    <div class="card">
        <p>No registrations for this event yet.</p>
    </div>
<?php else: ?>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Registered On</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrations as $reg): ?>
                    <tr>
                        <td><?php echo $reg['id']; ?></td>
                        <td><?php echo htmlspecialchars($reg['name']); ?></td>
                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                        <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($reg['created_at'] ?? 'now')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

