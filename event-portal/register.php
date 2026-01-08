<?php
/**
 * Admin Registration Page
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: /event-portal/admin-dashboard.php');
    exit();
}

$pageTitle = 'Admin Registration - Event Portal';

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
            $error = 'Name should only contain letters and spaces.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            // Check if email already exists
            $checkQuery = "SELECT id FROM admins WHERE email = ?";
            $stmt = mysqli_prepare($conn, $checkQuery);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $error = 'Email already registered.';
                mysqli_stmt_close($stmt);
            } else {
                mysqli_stmt_close($stmt);
                
                // Hash password and insert admin
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertQuery = "INSERT INTO admins (name, email, password) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insertQuery);
                mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashedPassword);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success'] = 'Registration successful! Please login.';
                    header('Location: /event-portal/login.php');
                    exit();
                } else {
                    $error = 'Registration failed. Please try again.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 2rem auto;">
    <h2>Admin Registration</h2>
    
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="/event-portal/register.php">
        <?php echo csrfField(); ?>
        
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" pattern="[a-zA-Z\s]+" title="Name should only contain letters and spaces" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>
        
        <button type="submit" class="btn">Register</button>
    </form>
    
    <p class="mt-2 text-center">
        Already have an account? <a href="/event-portal/login.php">Login here</a>
    </p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

