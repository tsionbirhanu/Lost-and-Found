<?php
session_start();

// Redirect to news-feed if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: news-feed.php');
    exit();
}

// Database configuration
$db_host = 'localhost';
$db_username = 'root'; // Change as needed
$db_password = ''; // Change as needed
$db_name = 'Lost_and_Found';

// Create connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$email = $password = '';
$errors = array();

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Validate inputs
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // Attempt login if no errors
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, full_name, email, password, is_admin FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Redirect to index
                header('Location: news-feed.php');
                exit();
            } else {
                $errors['general'] = 'Invalid email or password';
            }
        } else {
            $errors['general'] = 'Invalid email or password';
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Lost and Found</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        <?php include 'styles.css'; ?>
    </style>
</head>
<body>
    <!-- Background circles -->
    <div class="bg-circle top-left"></div>
    <div class="bg-circle bottom-right"></div>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <img src="/assets/images/logo.png" alt="Lost and Found Logo">
        </div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <span class="separator">|</span>
            <a href="login.php" class="active">Login</a>
            <a href="register.php">Register</a>
            <div class="user-icon">
                <i class="fas fa-user-circle"></i>
            </div>
        </div>
    </nav>
    
    <!-- Login Form -->
    <div class="auth-container">
        <div class="login-icon">
            <img src="/assets/images/pet-icon.png" alt="Pet Icon">
        </div>
        <h1>Welcome Back</h1>
        <p>Login to access your account and help reunite lost pets.</p>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="error-message" style="color: red; margin-bottom: 15px;">
                <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message" style="color: green; margin-bottom: 15px;">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <form class="auth-form" action="login.php" method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>" required>
                <?php if (!empty($errors['email'])): ?>
                    <span style="color: red; font-size: 0.8rem;"><?php echo $errors['email']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
                <?php if (!empty($errors['password'])): ?>
                    <span style="color: red; font-size: 0.8rem;"><?php echo $errors['password']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group" style="text-align: right;">
                <a href="forgot_password.php" class="text-link">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php" class="text-link">Register here</a></p>
        </div>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="/assets/images/logo-white.png" alt="Lost and Found Logo">
                <p>Helping reunite lost pets with their families.</p>
            </div>
            <div class="footer-links">
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Resources</h3>
                    <ul>
                        <li><a href="tips.php">Pet Care Tips</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> Lost and Found. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>