<?php
session_start();

// Database configuration
$db_host = 'localhost';
$db_username = 'root'; 
$db_password = '';
$db_name = 'Lost_and_Found';

// Create connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user's information if logged in
$user = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, full_name, email, phone, is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
}

$conn->close();

// Get current time for mobile status bar
$current_time = date("g:i A");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Lost and Found</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .about-container {
            max-width: 1200px;
            margin: 120px auto 60px;
            padding: 0 20px;
        }

        .about-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .about-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .about-header p {
            color: var(--text-color);
            font-size: 1.2rem;
        }

        .about-section {
            margin-bottom: 60px;
        }

        .about-section h2 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 30px;
            text-align: center;
        }

        .about-section p {
            color: var(--text-color);
            line-height: 1.8;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .feature-card {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .feature-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .team-members {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .team-member {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .team-member:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .team-member img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .team-member h3 {
            color: var(--primary-color);
            margin: 20px 0 5px;
            padding: 0 20px;
        }

        .team-member .position {
            color: var(--text-color);
            font-size: 0.9rem;
            margin-bottom: 15px;
            padding: 0 20px;
        }

        .team-member p {
            padding: 0 20px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 20px;
            border-top: 1px solid var(--gray-color);
        }

        .social-links a {
            color: var(--primary-color);
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .social-links a:hover {
            color: var(--primary-dark);
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .contact-method {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
        }

        .contact-method:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .contact-method i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .contact-method h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .contact-method p {
            margin: 5px 0;
            color: var(--text-color);
        }

        @media (max-width: 768px) {
            .about-container {
                margin-top: 100px;
            }

            .about-header h1 {
                font-size: 2rem;
            }

            .about-section h2 {
                font-size: 1.8rem;
            }

            .features,
            .team-members,
            .contact-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="bg-circle top-left"></div>
    
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
                <a href="about_us.php" class="active">ABOUT US</a>
                <div class="separator">|</div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="user.php" class="user-icon">
                        <i class="fas fa-user"></i>
                    </a>
                    <a href="logout.php" class="register-link">Logout</a>
                <?php else: ?>
                    <a href="register.php" class="register-link">Register</a>
                    <a href="login.php" class="user-icon">
                        <i class="fas fa-user"></i>
                    </a>
                <?php endif; ?>
            </div>
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </header>

    <main>
        <div class="about-container">
            <div class="about-header">
                <h1>About Lost and Found</h1>
                <p>Connecting people with their lost items since 2023</p>
            </div>

            <div class="about-section">
                <h2>Our Mission</h2>
                <p>At Lost and Found, we believe that losing personal items shouldn't mean losing them forever. Our mission is to create a community-driven platform that helps reunite people with their lost belongings quickly and efficiently. Whether you've lost something valuable or found an item that doesn't belong to you, our platform makes the connection process simple and secure.</p>
            </div>

            <div class="about-section">
                <h2>How It Works</h2>
                <div class="features">
                    <div class="feature-card">
                        <i class="fas fa-search"></i>
                        <h3>Report Lost Items</h3>
                        <p>Create a detailed listing of your lost item with photos, description, and location. Our system will notify you of potential matches.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-check-circle"></i>
                        <h3>Register Found Items</h3>
                        <p>Found something? Register it on our platform to help locate the rightful owner. We'll handle the verification process.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-comments"></i>
                        <h3>Secure Messaging</h3>
                        <p>Our built-in messaging system allows secure communication between users without revealing personal contact information.</p>
                    </div>
                </div>
            </div>

            <div class="about-section">
                <h2>Our Team</h2>
                <div class="team-members">
                    <div class="team-member">
                        <img src="images/team1.jpg" alt="John Doe">
                        <h3>John Doe</h3>
                        <p class="position">Founder & CEO</p>
                        <p>With over 10 years in community platforms, John created Lost and Found to solve a personal frustration.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                    <div class="team-member">
                        <img src="images/team2.jpg" alt="Jane Smith">
                        <h3>Jane Smith</h3>
                        <p class="position">Lead Developer</p>
                        <p>Jane oversees all technical aspects of our platform, ensuring a smooth and secure user experience.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-github"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                    <div class="team-member">
                        <img src="images/team3.jpg" alt="Mike Johnson">
                        <h3>Mike Johnson</h3>
                        <p class="position">Community Manager</p>
                        <p>Mike builds and nurtures our user community, ensuring everyone has a positive experience.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="about-section">
                <h2>Contact Us</h2>
                <div class="contact-info">
                    <div class="contact-method">
                        <i class="fas fa-envelope"></i>
                        <h3>Email</h3>
                        <p>support@lostandfound.com</p>
                        <p>We typically respond within 24 hours.</p>
                    </div>
                    <div class="contact-method">
                        <i class="fas fa-phone"></i>
                        <h3>Phone</h3>
                        <p>+1 (555) 123-4567</p>
                        <p>Monday-Friday, 9am-5pm EST</p>
                    </div>
                    <div class="contact-method">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Office</h3>
                        <p>123 Finder Street</p>
                        <p>Boston, MA 02115, USA</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
            &copy; <?php echo date('Y'); ?> Lost and Found<br>
            All Rights Reserved
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>