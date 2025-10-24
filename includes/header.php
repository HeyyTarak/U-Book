<?php
// Start output buffering to ensure no whitespace issues
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <?php if (isset($is_admin) && $is_admin): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <?php endif; ?>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="<?php echo BASE_URL; ?>/pages/index.php"><?php echo SITE_NAME; ?></a>
            </div>
            
            <div class="nav-menu">
                <a href="<?php echo BASE_URL; ?>/pages/events.php">Events</a>
                
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/booking_history.php">My Bookings</a>
                    
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo BASE_URL; ?>/admin/manage_events.php">Admin Panel</a>
                    <?php endif; ?>
                    
                    <div class="nav-user">
                        <span>Hello, <?php echo $_SESSION['user_name']; ?></span>
                        <a href="<?php echo BASE_URL; ?>/pages/logout.php" class="btn-logout">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/pages/login.php" class="btn-login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <?php displayFlashMessages(); ?>