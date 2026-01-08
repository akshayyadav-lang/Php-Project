<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Event Portal'; ?></title>
    <link rel="stylesheet" href="/event-portal/assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <h1><a href="/event-portal/index.php">Event Portal</a></h1>
                <ul>
                    <li><a href="/event-portal/index.php">Events</a></li>
                    <?php if (isAdminLoggedIn()): ?>
                        <li><a href="/event-portal/admin-dashboard.php">Dashboard</a></li>
                        <li><a href="/event-portal/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/event-portal/login.php">Admin Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main class="container">

