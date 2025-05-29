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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="main-container">
        <!-- Side Navigation - Desktop -->
        <div class="side-nav">
            <div class="nav-header">
                <img src="images/profile.jpg" alt="<?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>">
                <h2><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>
                    <?php if (!empty($user['is_admin'])): ?>
                        <span class="admin-badge">Admin</span>
                    <?php endif; ?>
                </h2>
            </div>
            <div class="side-nav-menu">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="search.php" class="nav-link">
                    <i class="fas fa-search"></i>
                    <span>Search</span>
                </a>
                <a href="messages.php" class="nav-link">
                    <i class="fas fa-comment-dots"></i>
                    <span>Messages</span>
                </a>
                <a href="notifications.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
                <a href="user.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="about_us.php" class="nav-link active">
                    <i class="fas fa-info-circle"></i>
                    <span>About Us</span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Mobile Status Bar -->
            <div class="status-bar">
                <span class="time"><?php echo htmlspecialchars($current_time); ?></span>
                <div class="status-icons">
                    <i class="fas fa-signal"></i>
                    <i class="fas fa-wifi"></i>
                    <i class="fas fa-battery-full"></i>
                </div>
            </div>

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
        </div>
        
        <!-- Mobile Navigation -->
        <div class="mobile-nav">
            <a href="index.php" class="nav-item">
                <i class="fas fa-home"></i>
            </a>
            <a href="search.php" class="nav-item">
                <i class="fas fa-search"></i>
            </a>
            <a href="messages.php" class="nav-item">
                <i class="fas fa-comment-dots"></i>
            </a>
            <a href="notifications.php" class="nav-item">
                <i class="fas fa-bell"></i>
            </a>
            <a href="user.php" class="nav-item">
                <i class="fas fa-user"></i>
            </a>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Update time every minute
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            const timeElements = document.querySelectorAll('.time');
            timeElements.forEach(el => el.textContent = timeStr);
        }
        
        setInterval(updateTime, 60000);
    </script>
</body>
</html>