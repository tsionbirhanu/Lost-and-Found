<?php
session_start();

// Redirect to login if not authenticated
/*if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}*/

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

// Get current user's information
$user_id = $_SESSION['user_id'] ?? null;
$user = [];
if ($user_id) {
    $stmt = $conn->prepare("SELECT id, full_name, email, phone, is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
}

// Since you don't have an 'items' table, I'll remove that query
// If you need to display user-related items, you'll need to create that table first

$conn->close();

// Get current time for mobile status bar
$current_time = date("g:i A");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Lost and Found</title>
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
                <a href="profile.php" class="nav-link active">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
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

            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-image">
                        <img src="images/profile.jpg" alt="<?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>">
                    </div>
                    <div class="profile-info">
                        <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>
                            <?php if (!empty($user['is_admin'])): ?>
                                <span class="admin-badge">Admin</span>
                            <?php endif; ?>
                        </h1>
                        <div class="profile-actions">
                            <a href="edit_profile.php" class="btn btn-outline">
                                <i class="fas fa-user-edit"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="contact-info">
                    <?php if (!empty($user['email'])): ?>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['phone'])): ?>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($user['phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="contact-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Member since: <?php echo date('M Y', strtotime($user['created_at'] ?? 'now')); ?></span>
                    </div>
                </div>
                
                <div class="profile-actions" style="margin-top: 30px;">
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
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
            <a href="profile.php" class="nav-item active">
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