<?php
$page_title = "Login";
require_once '../includes/config.php';
require_once '../includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/pages/index.php', 'You are already logged in!');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        require_once '../scripts/process_login.php';
    }
}
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-card">
            <h1>Login to U-Book</h1>
            <p>Access your account to book event tickets</p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo $_POST['email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Login</button>
            </form>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Create one here</a></p>
            </div>
            <div class="demo-accounts">
                <h3>Demo Accounts:</h3>
                <p><strong>Admin:</strong> admin@college.edu / password</p>
                <p><strong>Student:</strong> student@college.edu / password</p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>