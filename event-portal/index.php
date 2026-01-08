<?php
/**
 * Home Page - Event Listing
 * Displays all events (upcoming and past)
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Start session for messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Events - Event Portal';

// Fetch all events ordered by date (upcoming first)
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
        $row['seats_available'] = $row['max_seats'] - $row['registrations_count'];
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

<?php if ($error): ?>
    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="message success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="card welcome-card">
    <div class="welcome-content">
        <div class="welcome-text">
            <h2>Welcome to Event Portal</h2>
            <p>Discover and register for exciting events happening around you. From conferences and workshops to seminars and meetups, find the perfect event that matches your interests. Browse through our curated list of upcoming events and secure your spot today!</p>
        </div>
        <div class="welcome-image">
            <img src="/event-portal/assets/images/welcome.jpg" alt="Welcome to Event Portal" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23e0e0e0\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EEvent Portal%3C/text%3E%3C/svg%3E'">
        </div>
    </div>
</div>

<div class="card events-header-card">
    <div class="events-header">
        <div class="events-header-text">
            <h2>All Events</h2>
            <p>Browse and register for upcoming events</p>
        </div>
        <div class="search-container">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search events by title, category, or location..." class="search-input">
                <span class="search-icon">🔍</span>
            </div>
        </div>
    </div>
</div>

<?php if (empty($events)): ?>
    <div class="card text-center no-events-card">
        <p>No events available at the moment.</p>
    </div>
<?php else: ?>
    <div id="eventsContainer" class="events-grid">
        <?php foreach ($events as $event): ?>
            <div class="event-card" data-title="<?php echo htmlspecialchars(strtolower($event['title'])); ?>" data-category="<?php echo htmlspecialchars(strtolower($event['category'])); ?>" data-location="<?php echo htmlspecialchars(strtolower($event['location'])); ?>">
                <?php if (!empty($event['image'])): ?>
                    <div class="event-image">
                        <img src="/event-portal/<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" onerror="this.style.display='none'">
                    </div>
                <?php else: ?>
                    <div class="event-image placeholder">
                        <div class="image-placeholder">No Image</div>
                    </div>
                <?php endif; ?>
                <div class="event-card-content">
                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                    <div class="badges">
                        <span class="category"><?php echo htmlspecialchars($event['category']); ?></span>
                        <span class="status <?php echo $event['status']; ?>">
                            <?php echo ucfirst($event['status']); ?>
                        </span>
                    </div>
                    <p class="info"><?php echo htmlspecialchars($event['description']); ?></p>
                    <p class="info">
                        <strong>Date & Time:</strong> <?php echo date('F j, Y g:i A', strtotime($event['event_datetime'])); ?>
                    </p>
                    <p class="info">
                        <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                    </p>
                    <div class="mt-2">
                        <a href="/event-portal/event.php?id=<?php echo $event['id']; ?>" class="btn">View Details</a>
                    </div>
                </div>
                <div class="event-footer">
                    <div class="seats-info">
                        <span class="seats-icon">👥</span>
                        <span><strong><?php echo $event['seats_available']; ?></strong> / <?php echo $event['max_seats']; ?> seats</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

