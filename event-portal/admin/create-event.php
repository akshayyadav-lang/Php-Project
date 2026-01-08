<?php
/**
 * Create Event Page
 * Admin form to create new events
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Require admin login
requireAdminLogin();

$pageTitle = 'Create Event - Admin Dashboard';

$error = '';
$success = '';

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
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/images/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = $_FILES['image']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
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
                
                // Insert event
                if ($imagePath) {
                    $query = "INSERT INTO events (title, description, category, event_datetime, location, max_seats, status, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "sssssiss", $title, $description, $category, $datetimeFormatted, $location, $maxSeats, $status, $imagePath);
                } else {
                    $query = "INSERT INTO events (title, description, category, event_datetime, location, max_seats, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "sssssis", $title, $description, $category, $datetimeFormatted, $location, $maxSeats, $status);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success'] = 'Event created successfully!';
                    header('Location: /event-portal/admin-dashboard.php');
                    exit();
                } else {
                    $error = 'Failed to create event. Please try again.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Create New Event</h2>
    
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="/event-portal/admin/create-event.php" enctype="multipart/form-data">
        <?php echo csrfField(); ?>
        
        <div class="form-group">
            <label for="image">Event Image</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
            <small style="color: #666; display: block; margin-top: 0.25rem;">Optional: Upload an image for this event (JPG, PNG, GIF, WEBP)</small>
        </div>
        
        <div class="form-group">
            <label for="title">Event Title *</label>
            <input type="text" id="title" name="title" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="category">Category *</label>
            <input type="text" id="category" name="category" required placeholder="e.g., Conference, Workshop, Seminar">
        </div>
        
        <div class="form-group">
            <label for="event_datetime">Date & Time *</label>
            <input type="datetime-local" id="event_datetime" name="event_datetime" required>
        </div>
        
        <div class="form-group">
            <label for="location">Location *</label>
            <input type="text" id="location" name="location" required>
        </div>
        
        <div class="form-group">
            <label for="max_seats">Maximum Seats *</label>
            <input type="number" id="max_seats" name="max_seats" required min="1">
        </div>
        
        <div class="form-group">
            <label for="status">Status *</label>
            <select id="status" name="status" required>
                <option value="open">Open</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-success">Create Event</button>
        <a href="/event-portal/admin-dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

