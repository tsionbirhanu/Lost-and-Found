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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        <?php include 'styles.css'; ?>
        /* Additional styles for register page */
        .auth-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(44, 62, 80, 0.1);
        }

        .auth-container h1 {
            color: #2C3E50;
            font-size: 2rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .auth-container p {
            color:blueviolet;
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-form .form-group {
            margin-bottom: 1.5rem;
        }

        .auth-form input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ECF0F1;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .auth-form input:focus {
            border-color: blueviolet;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .auth-form button {
            width: 100%;
            padding: 12px;
            background: blueviolet;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .auth-form button:hover {
            background: blueviolet;
        }

        .bg-circle {
            position: fixed;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            z-index: -1;
            opacity: 0.1;
        }

        .bg-circle.top-left {
            top: -300px;
            left: -300px;
            background: blueviolet;
        }

        .bg-circle.bottom-right {
            bottom: -300px;
            right: -300px;
            background: blueviolet;
        }

        .error-message {
            color: #E74C3C;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: blueviolet;
        }

        .auth-footer a {
            color:blueviolet;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            color: blueviolet;
        }

        .navbar {
            background: blueviolet;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(44, 62, 80, 0.1);
        }

        .navbar .nav-links a {
            color: #fff;
            text-decoration: none;
            margin: 0 1rem;
            transition: all 0.3s ease;
        }

        .navbar .nav-links a:hover,
        .navbar .nav-links a.active {
            color:blueviolet;
        }

        footer {
            background: blueviolet;
            color: #fff;
            padding: 3rem 0;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-column h3 {
            color: #3498DB;
            margin-bottom: 1rem;
        }

        .footer-column ul li a {
            color: #ECF0F1;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-column ul li a:hover {
            color: #3498DB;
        }

        /* Updated header and navbar styles to match index.php */
        header {
            width: 100%;
            position: relative;
            z-index: 100;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: blueviolet;
        }

        .logo a {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo img {
            height: 40px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            color: #ECF0F1;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: #3498DB;
        }

        .nav-links a.active {
            color: #3498DB;
        }

        .nav-links .separator {
            color: #ECF0F1;
            opacity: 0.3;
        }

        .register-link {
            padding: 0.5rem 1rem;
            border: 2px solid #3498DB;
            border-radius: 4px;
        }

        .user-icon {
            color: #ECF0F1;
            font-size: 1.2rem;
        }

        /* Updated footer styles to match index.php */
        footer {
            background: #2C3E50;
            color: #ECF0F1;
            padding: 4rem 0 2rem;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3rem;
        }

        .footer-column {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .footer-logo-img {
            height: 40px;
            margin-bottom: 1rem;
        }

        .site-column {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .site-column a {
            color: #ECF0F1;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .site-column a:hover {
            color: #3498DB;
        }

        .footer-column h3 {
            color: #3498DB;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-column ul li {
            margin-bottom: 0.8rem;
        }

        .footer-column ul li a {
            color: #ECF0F1;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-column ul li a:hover {
            color: #3498DB;
        }

        .footer-column p {
            color: #ECF0F1;
            margin: 0.5rem 0;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-icon {
            color: #ECF0F1;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            color: #3498DB;
        }

        .copyright {
            text-align: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(236, 240, 241, 0.1);
            color: #ECF0F1;
            opacity: 0.8;
            line-height: 1.6;
        }

        .menu-toggle {
            display: none;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
                background: none;
                border: none;
                cursor: pointer;
            }

            .menu-toggle span {
                display: block;
                width: 25px;
                height: 3px;
                background: #ECF0F1;
                margin: 5px 0;
                transition: all 0.3s ease;
            }

            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="bg-circle top-left"></div>
    <div class="bg-circle bottom-right"></div>
    
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="index.php">
                    <img src="assets/icons/logo.svg" alt="Lost and Found Logo">
                </a>
            </div>
            <div class="nav-links" id="navLinks">
                <a href="index.php">HOME</a>
                <a href="lost.php">LOST</a>
                <a href="found.php">FOUND</a>
                <a href="about_us.php">ABOUT US</a>
                <div class="separator">|</div>
                <a href="register.php" class="register-link active">Register</a>
                <a href="login.php" class="user-icon">
                    <i class="fas fa-user"></i>
                </a>
            </div>
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </header>
    
    <!-- Registration Form -->
    <div class="auth-container">
        <div class="login-icon">
            <img src="/assets/images/pet-icon.png" alt="Pet Icon">
        </div>
        <h1>Create Account</h1>
        <p>Join our community to report lost pets or help reunite found pets with their owners.</p>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="error-message">
                <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>
        
        <form class="auth-form" action="register.php" method="POST">
            <div class="form-group">
                <input type="text" name="full_name" placeholder="Full Name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                <?php if (!empty($errors['full_name'])): ?>
                    <span class="error-message"><?php echo $errors['full_name']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>" required>
                <?php if (!empty($errors['email'])): ?>
                    <span class="error-message"><?php echo $errors['email']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
                <?php if (!empty($errors['password'])): ?>
                    <span class="error-message"><?php echo $errors['password']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <?php if (!empty($errors['confirm_password'])): ?>
                    <span class="error-message"><?php echo $errors['confirm_password']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <input type="tel" name="phone" placeholder="Phone Number (Optional)" value="<?php echo htmlspecialchars($phone); ?>">
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <img src="assets/icons/logo.svg" alt="Lost and Found Logo" class="footer-logo-img">
                <div class="site-column">
                    <a href="lost.php">Lost</a>
                    <a href="report-lost.php">Report Lost</a>
                    <a href="found.php">Found</a>
                    <a href="report-found.php">Report Found</a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Help</h3>
                <ul>
                    <li><a href="support.php">Customer Support</a></li>
                    <li><a href="terms.html">Terms & Conditions</a></li>
                    <li><a href="privacy.html">Privacy Policy</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Contact</h3>
                <p>Tel: +94 718570620</p>
                <p>Email: talkprojects@werenix.com</p>
                <div class="social-links">
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-github"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            &copy; Copyright 2024 Lost and Found<br>
            All Rights Reserved
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>