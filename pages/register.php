<?php
$page_title = "Register";
require_once '../includes/config.php';
require_once '../includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/pages/index.php', 'You are already logged in!');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered. Please <a href="login.php">login instead</a>.';
            } else {
                // Create new user - phone and student_id are now optional
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, student_id, password, role) VALUES (?, ?, ?, ?, ?, 'student')");
                $stmt->execute([$name, $email, $phone, $student_id, $hashed_password]);
                
                $user_id = $pdo->lastInsertId();
                
                // Auto-login after registration
                loginUser($user_id, $email, $name, 'student');
                
                redirect('/pages/index.php', 'Registration successful! Welcome to U-Book.');
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'Registration failed. Please try again. Error: ' . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-card">
            <h1>Join U-Book</h1>
            <p>Create your account to book event tickets</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="your.email@college.edu">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number (Optional)</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                           placeholder="+1 (555) 123-4567">
                    <small style="color: #666; font-size: 0.8rem;">We'll only use this for important updates</small>
                </div>
                
                <div class="form-group">
                    <label for="student_id">Student ID (Optional)</label>
                    <input type="text" id="student_id" name="student_id" 
                           value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>"
                           placeholder="STU123456">
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required
                           placeholder="At least 6 characters"
                           minlength="6">
                    <small style="color: #666; font-size: 0.8rem;">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Re-enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    Create Account
                </button>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
            
            <!-- Demo Accounts Reminder -->
            <div class="demo-accounts" style="margin-top: 2rem; padding: 1rem; background: #e8f4fd; border-radius: 8px;">
                <h4>Demo Accounts Available:</h4>
                <p><strong>Admin:</strong> admin@college.edu / password</p>
                <p><strong>Student:</strong> student@college.edu / password</p>
                <p><em>Or create your own account above!</em></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long');
            return;
        }
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match');
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Creating Account...';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>