<?php
/**
 * Edit Event Page
 * Admin form to edit existing events
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Require admin login
requireAdminLogin();

$pageTitle = 'Edit Event - Admin Dashboard';

$error = '';
$success = '';

// Get event ID
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($eventId <= 0) {
    header('Location: /event-portal/admin-dashboard.php');
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
    header('Location: /event-portal/admin-dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        $eventDatetime = isset($_POST['event_datetime']) ? trim($_POST['event_datetime']) : '';
        $location = isset($_POST['location']) ? trim($_POST['location']) : '';
        $maxSeats = isset($_POST['max_seats']) ? (int)$_POST['max_seats'] : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'open';
        
        // Validation
        if (empty($title) || empty($description) || empty($category) || empty($eventDatetime) || empty($location)) {
            $error = 'All fields are required.';
        } elseif ($maxSeats <= 0) {
            $error = 'Maximum seats must be greater than 0.';
        } elseif (!in_array($status, ['open', 'closed'])) {
            $error = 'Invalid status.';
        } else {
            // Handle image upload
            $imagePath = $event['image']; // Keep existing image by default
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/images/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = $_FILES['image']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    // Delete old image if exists
                    if ($event['image'] && file_exists(__DIR__ . '/../' . $event['image'])) {
                        unlink(__DIR__ . '/../' . $event['image']);
                    }
                    
                    $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid('event_', true) . '.' . $fileExtension;
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $imagePath = 'uploads/images/' . $fileName;
                    } else {
                        $error = 'Failed to upload image.';
                    }
                } else {
                    $error = 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP';
                }
            }
            
            if (empty($error)) {
                // Format datetime for MySQL
                $datetimeFormatted = date('Y-m-d H:i:s', strtotime($eventDatetime));
                
                // Update event
                $updateQuery = "UPDATE events SET title = ?, description = ?, category = ?, event_datetime = ?, location = ?, max_seats = ?, status = ?, image = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($stmt, "sssssissi", $title, $description, $category, $datetimeFormatted, $location, $maxSeats, $status, $imagePath, $eventId);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success'] = 'Event updated successfully!';
                    header('Location: /event-portal/admin-dashboard.php');
                    exit();
                } else {
                    $error = 'Failed to update event. Please try again.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Format datetime for input field (datetime-local format)
$eventDatetimeFormatted = date('Y-m-d\TH:i', strtotime($event['event_datetime']));

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Edit Event</h2>
    
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="/event-portal/admin/edit-event.php?id=<?php echo $eventId; ?>" enctype="multipart/form-data">
        <?php echo csrfField(); ?>
        
        <div class="form-group">
            <label for="image">Event Image</label>
            <?php if (!empty($event['image'])): ?>
                <div style="margin-bottom: 0.5rem;">
                    <img src="/event-portal/<?php echo htmlspecialchars($event['image']); ?>" alt="Current event image" style="max-width: 200px; height: auto; border-radius: 4px; margin-bottom: 0.5rem;">
                    <br>
                    <small style="color: #666;">Current image</small>
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
            <small style="color: #666; display: block; margin-top: 0.25rem;">Optional: Upload a new image to replace current one (JPG, PNG, GIF, WEBP)</small>
        </div>
        
        <div class="form-group">
            <label for="title">Event Title *</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($event['description']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="category">Category *</label>
            <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($event['category']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="event_datetime">Date & Time *</label>
            <input type="datetime-local" id="event_datetime" name="event_datetime" value="<?php echo htmlspecialchars($eventDatetimeFormatted); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="location">Location *</label>
            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="max_seats">Maximum Seats *</label>
            <input type="number" id="max_seats" name="max_seats" value="<?php echo $event['max_seats']; ?>" required min="1">
        </div>
        
        <div class="form-group">
            <label for="status">Status *</label>
            <select id="status" name="status" required>
                <option value="open" <?php echo $event['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                <option value="closed" <?php echo $event['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-success">Update Event</button>
        <a href="/event-portal/admin-dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

