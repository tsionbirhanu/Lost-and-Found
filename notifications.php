<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
$user_id = $_SESSION['user_id'];
$user = [];
$stmt = $conn->prepare("SELECT id, full_name, email, phone, is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}
$stmt->close();

// Get notifications for current user
$notifications = [];
$stmt = $conn->prepare("SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Mark all notifications as read if requested
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Update the local array to reflect the changes
    foreach ($notifications as &$notification) {
        $notification['is_read'] = 1;
    }
}

// Get count of unread notifications
$unread_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $unread_count = $row['count'];
}
$stmt->close();

$conn->close();

// Get current time for mobile status bar
$current_time = date("g:i A");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Lost and Found</title>
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
                <a href="notifications.php" class="nav-link active">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="user.php" class="nav-link">
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

            <div class="notifications-container">
                <div class="page-header">
                    <h1>Notifications</h1>
                    <?php if ($unread_count > 0): ?>
                        <a href="notifications.php?mark_all_read=1" class="btn btn-outline btn-sm">
                            <i class="fas fa-check-circle"></i> Mark all as read
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <i class="far fa-bell-slash"></i>
                        <h3>No notifications yet</h3>
                        <p>When you have notifications, they'll appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="notifications-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                <div class="notification-icon">
                                    <i class="fas fa-<?php echo $notification['is_read'] ? 'bell' : 'bell-on'; ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </div>
                                    <div class="notification-time">
                                        <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
            <a href="notifications.php" class="nav-item active">
                <i class="fas fa-bell">
                    <?php if ($unread_count > 0): ?>
                        <span class="mobile-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </i>
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