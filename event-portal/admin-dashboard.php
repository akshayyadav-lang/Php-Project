<?php
/**
 * Admin Dashboard
 * Main admin page showing events and statistics
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Require admin login
requireAdminLogin();

$pageTitle = 'Admin Dashboard - Event Portal';

// Fetch all events
$query = "SELECT * FROM events ORDER BY event_datetime DESC";
$result = mysqli_query($conn, $query);

$events = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Check if event date has passed and update status
        $eventDateTime = strtotime($row['event_datetime']);
        $currentDateTime = time();
        
        if ($eventDateTime < $currentDateTime && $row['status'] === 'open') {
            // Update status to closed in database
            $updateQuery = "UPDATE events SET status = 'closed' WHERE id = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "i", $row['id']);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
            $row['status'] = 'closed';
        }
        
        // Count registrations for each event
        $regQuery = "SELECT COUNT(*) as count FROM registrations WHERE event_id = ?";
        $stmt = mysqli_prepare($conn, $regQuery);
        mysqli_stmt_bind_param($stmt, "i", $row['id']);
        mysqli_stmt_execute($stmt);
        $regResult = mysqli_stmt_get_result($stmt);
        $regData = mysqli_fetch_assoc($regResult);
        $row['registrations_count'] = $regData['count'];
        mysqli_stmt_close($stmt);
        
        $events[] = $row;
    }
}

// Display messages
$error = '';
$success = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Admin Dashboard</h2>
    <p>Manage events and view registrations</p>
</div>

<?php if ($error): ?>
    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="message success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3>All Events</h3>
        <a href="/event-portal/admin/create-event.php" class="btn btn-success">Create New Event</a>
    </div>
    
    <?php if (empty($events)): ?>
        <p>No events created yet. <a href="/event-portal/admin/create-event.php">Create your first event</a></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Date & Time</th>
                    <th>Location</th>
                    <th>Registrations</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['category']); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($event['event_datetime'])); ?></td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td>
                            <?php echo $event['registrations_count']; ?> / <?php echo $event['max_seats']; ?>
                            <a href="/event-portal/admin/registrations.php?event_id=<?php echo $event['id']; ?>" style="margin-left: 0.5rem;">View</a>
                        </td>
                        <td>
                            <span class="status <?php echo $event['status']; ?>">
                                <?php echo ucfirst($event['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="/event-portal/admin/edit-event.php?id=<?php echo $event['id']; ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Edit</a>
                            <a href="/event-portal/admin/delete-event.php?id=<?php echo $event['id']; ?>" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.875rem;" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

