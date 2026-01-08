<?php
/**
 * Event Registration Handler
 * Processes event registration form submission
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/csrf.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        header('Location: /event-portal/index.php');
        exit();
    }
    
    // Get and validate input
    $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // Validation
    $errors = [];
    
    if ($eventId <= 0) {
        $errors[] = 'Invalid event.';
    }
    
    if (empty($name)) {
        $errors[] = 'Name is required.';
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $errors[] = 'Name should only contain letters and spaces.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone is required.';
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = 'Phone number must be exactly 10 digits.';
    }
    
    if (empty($errors)) {
        // Check if event exists and is open
        $query = "SELECT * FROM events WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $eventId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$event) {
            $_SESSION['error'] = 'Event not found.';
            header('Location: /event-portal/index.php');
            exit();
        }
        
        // Check if event date has passed
        $eventDateTime = strtotime($event['event_datetime']);
        $currentDateTime = time();
        
        if ($eventDateTime < $currentDateTime) {
            // Update status to closed if date passed
            if ($event['status'] === 'open') {
                $updateQuery = "UPDATE events SET status = 'closed' WHERE id = ?";
                $updateStmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($updateStmt, "i", $eventId);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
                $event['status'] = 'closed';
            }
        }
        
        if ($event['status'] !== 'open') {
            $_SESSION['error'] = 'Registration is closed for this event.';
            header('Location: /event-portal/event.php?id=' . $eventId);
            exit();
        }
        
        // Count current registrations
        $regQuery = "SELECT COUNT(*) as count FROM registrations WHERE event_id = ?";
        $stmt = mysqli_prepare($conn, $regQuery);
        mysqli_stmt_bind_param($stmt, "i", $eventId);
        mysqli_stmt_execute($stmt);
        $regResult = mysqli_stmt_get_result($stmt);
        $regData = mysqli_fetch_assoc($regResult);
        $registrationsCount = $regData['count'];
        mysqli_stmt_close($stmt);
        
        if ($registrationsCount >= $event['max_seats']) {
            $_SESSION['error'] = 'All seats are full for this event.';
            header('Location: /event-portal/event.php?id=' . $eventId);
            exit();
        }
        
        // Insert registration
        $insertQuery = "INSERT INTO registrations (event_id, name, email, phone) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($stmt, "isss", $eventId, $name, $email, $phone);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Registration successful!';
            header('Location: /event-portal/index.php');
        } else {
            $_SESSION['error'] = 'Registration failed. Please try again.';
            header('Location: /event-portal/index.php');
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = implode(' ', $errors);
        header('Location: /event-portal/event.php?id=' . $eventId);
    }
} else {
    header('Location: /event-portal/index.php');
}

exit();

