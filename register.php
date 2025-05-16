<?php
session_start();

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
$full_name = $email = $password = $confirm_password = $phone = '';
$errors = array();
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone = trim($_POST['phone'] ?? '');
    
    // Validate full name
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    } elseif (strlen($full_name) < 3) {
        $errors['full_name'] = 'Name must be at least 3 characters';
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    // Validate password confirmation
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();
        
        if ($check_email->num_rows > 0) {
            $errors['email'] = 'Email already registered';
        }
        $check_email->close();
    }
    
    // If no errors, insert new user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $phone);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Registration successful! You can now login.';
            header('Location: login.php');
            exit();
        } else {
            $errors['general'] = 'Error: ' . $conn->error;
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
    <title>Register | Lost and Found</title>
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
            <a href="login.php">Login</a>
            <a href="register.php" class="active">Register</a>
            <div class="user-icon">
                <i class="fas fa-user-circle"></i>
            </div>
        </div>
    </nav>
    
    <!-- Registration Form -->
    <div class="auth-container">
        <div class="login-icon">
            <img src="/assets/images/pet-icon.png" alt="Pet Icon">
        </div>
        <h1>Create Account</h1>
        <p>Join our community to report lost pets or help reunite found pets with their owners.</p>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="error-message" style="color: red; margin-bottom: 15px;">
                <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>
        
        <form class="auth-form" action="register.php" method="POST">
            <div class="form-group">
                <input type="text" name="full_name" placeholder="Full Name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                <?php if (!empty($errors['full_name'])): ?>
                    <span style="color: red; font-size: 0.8rem;"><?php echo $errors['full_name']; ?></span>
                <?php endif; ?>
            </div>
            
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
            
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <?php if (!empty($errors['confirm_password'])): ?>
                    <span style="color: red; font-size: 0.8rem;"><?php echo $errors['confirm_password']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <input type="tel" name="phone" placeholder="Phone Number (Optional)" value="<?php echo htmlspecialchars($phone); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php" class="text-link">Login here</a></p>
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