<?php
/**
 * Admin Login Page
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: /event-portal/admin-dashboard.php');
    exit();
}

$pageTitle = 'Admin Login - Event Portal';

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } else {
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email format.';
            } else {
                // Check admin credentials
                $query = "SELECT id, email, password FROM admins WHERE email = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $admin = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                
                if ($admin && password_verify($password, $admin['password'])) {
                    loginAdmin($admin['id'], $admin['email']);
                    header('Location: /event-portal/admin-dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid email or password.';
                }
            }
        }
    }
}

// Display messages from session
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

<div class="card" style="max-width: 500px; margin: 2rem auto;">
    <h2>Admin Login</h2>
    
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="/event-portal/login.php">
        <?php echo csrfField(); ?>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn">Login</button>
    </form>
    
    <p class="mt-2 text-center">
        Don't have an account? <a href="/event-portal/register.php">Register here</a>
    </p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

