<?php
/**
 * Event Details Page
 * Shows event details and registration form
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Get event ID
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($eventId <= 0) {
    header('Location: /event-portal/index.php');
    exit();
}

// Fetch event details
$query = "SELECT * FROM events WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $eventId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$event) {
    header('Location: /event-portal/index.php');
    exit();
}

// Check if event date has passed and update status
$eventDateTime = strtotime($event['event_datetime']);
$currentDateTime = time();

if ($eventDateTime < $currentDateTime && $event['status'] === 'open') {
    // Update status to closed in database
    $updateQuery = "UPDATE events SET status = 'closed' WHERE id = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "i", $eventId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
    $event['status'] = 'closed';
}

// Count registrations
$regQuery = "SELECT COUNT(*) as count FROM registrations WHERE event_id = ?";
$stmt = mysqli_prepare($conn, $regQuery);
mysqli_stmt_bind_param($stmt, "i", $eventId);
mysqli_stmt_execute($stmt);
$regResult = mysqli_stmt_get_result($stmt);
$regData = mysqli_fetch_assoc($regResult);
$registrationsCount = $regData['count'];
$seatsAvailable = $event['max_seats'] - $registrationsCount;
mysqli_stmt_close($stmt);

$pageTitle = htmlspecialchars($event['title']) . ' - Event Portal';

// Check if registration is possible
$canRegister = ($event['status'] === 'open' && $seatsAvailable > 0);

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <?php if (!empty($event['image'])): ?>
        <div class="event-detail-image">
            <img src="/event-portal/<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
        </div>
    <?php endif; ?>
    <h2><?php echo htmlspecialchars($event['title']); ?></h2>
    <div class="badges">
        <span class="category"><?php echo htmlspecialchars($event['category']); ?></span>
        <span class="status <?php echo $event['status']; ?>">
            <?php echo ucfirst($event['status']); ?>
        </span>
    </div>
    <div class="mt-2">
        <p><strong>Description:</strong></p>
        <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
    </div>
    <div class="mt-2">
        <p><strong>Date & Time:</strong> <?php echo date('F j, Y g:i A', strtotime($event['event_datetime'])); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
        <p><strong>Seats Available:</strong> <?php echo $seatsAvailable; ?> / <?php echo $event['max_seats']; ?></p>
        <p>
            <strong>Status:</strong> 
            <span class="status <?php echo $event['status']; ?>">
                <?php echo ucfirst($event['status']); ?>
            </span>
        </p>
    </div>
    <div class="mt-2">
        <a href="/event-portal/index.php" class="btn btn-secondary">Back to Events</a>
    </div>
</div>

<?php if ($canRegister): ?>
    <div class="card">
        <h3>Register for this Event</h3>
        <form action="/event-portal/register-event.php" method="POST">
            <?php
            require_once __DIR__ . '/includes/csrf.php';
            echo csrfField();
            ?>
            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
            
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" pattern="[a-zA-Z\s]+" title="Name should only contain letters and spaces" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone *</label>
                <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" title="Phone number must be exactly 10 digits" maxlength="10" required>
            </div>
            
            <button type="submit" class="btn btn-success">Register</button>
        </form>
    </div>
<?php else: ?>
    <div class="card">
        <p class="message error">
            <?php if ($event['status'] === 'closed'): ?>
                Registration is closed for this event.
            <?php elseif ($seatsAvailable <= 0): ?>
                All seats are full for this event.
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

