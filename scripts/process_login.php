<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit();
}

$email = trim($_POST['email']);
$password = $_POST['password'];

// Demo accounts for testing (remove in production)
$demo_accounts = [
    'admin@college.edu' => [
        'password' => 'password',
        'name' => 'Administrator',
        'role' => 'admin',
        'id' => 1
    ],
    'student@college.edu' => [
        'password' => 'password',
        'name' => 'John Student',
        'role' => 'student',
        'id' => 2
    ]
];

// Check demo accounts first
if (array_key_exists($email, $demo_accounts)) {
    if ($password === $demo_accounts[$email]['password']) {
        loginUser(
            $demo_accounts[$email]['id'],
            $email,
            $demo_accounts[$email]['name'],
            $demo_accounts[$email]['role']
        );
        
        // Redirect to intended page or dashboard
        $redirect_url = $_SESSION['redirect_url'] ?? '../pages/index.php';
        unset($_SESSION['redirect_url']);
        
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Database authentication (for real users)
try {
    $stmt = $pdo->prepare("SELECT id, email, name, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        loginUser($user['id'], $user['email'], $user['name'], $user['role']);
        
        $redirect_url = $_SESSION['redirect_url'] ?? '../pages/index.php';
        unset($_SESSION['redirect_url']);
        
        header('Location: ' . $redirect_url);
        exit();
    } else {
        // Invalid credentials
        $_SESSION['login_error'] = 'Invalid email or password';
        header('Location: ../pages/login.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['login_error'] = 'Login failed. Please try again.';
    header('Location: ../pages/login.php');
    exit();
}
?>