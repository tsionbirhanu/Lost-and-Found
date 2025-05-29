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

// Fetch recent lost and found items with user information
$recent_items = [];
$stmt = $conn->prepare("
    SELECT i.id, i.title, i.description, i.location, 
           i.date_lost_found, i.status, i.image_path, i.category,
           u.full_name as username, u.email,
           r.created_at as report_date
    FROM items i
    JOIN reports r ON i.id = r.item_id
    JOIN users u ON i.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 20
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recent_items[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsfeed - Lost and Found</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header Navigation -->
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="index.php"><img src="images/logo.png" alt="Lost and Found Logo"></a>
            </div>
            <div class="nav-links">
                <a href="index.php">HOME</a>
                <a href="lost.php">LOST</a>
                <a href="found.php">FOUND</a>
                <a href="about_us.php">ABOUT US</a>
                <div class="separator">|</div>
                <a href="user.php" class="user-icon active"><i class="fas fa-user-circle"></i></a>
                <a href="logout.php" class="register-link">Logout</a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <div class="newsfeed-container">
            <h1 class="page-title">Newsfeed</h1>
            
            <div class="refresh-icon">
                <i class="fas fa-sync-alt" onclick="location.reload()"></i>
            </div>
            
            <div class="posts-container" id="postsContainer">
                <?php foreach ($recent_items as $item): ?>
                    <div class="post">
                        <div class="post-header">
                            <div class="post-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="post-info">
                                <span class="post-author"><?php echo htmlspecialchars($item['username']); ?></span>
                                <span class="post-date"><?php echo date('M j, Y g:i A', strtotime($item['report_date'])); ?></span>
                            </div>
                        </div>
                        <div class="post-content">
                            <h3 class="post-title">
                                <?php echo htmlspecialchars($item['title']); ?>
                                <span class="post-category <?php echo strtolower($item['category']); ?>">
                                    <?php echo htmlspecialchars($item['category']); ?>
                                </span>
                            </h3>
                            <p><?php echo htmlspecialchars($item['description']); ?></p>
                            <?php if (!empty($item['image_path'])): ?>
                                <div class="post-image">
                                    <img src="uploads/<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="post-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location']); ?></span>
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($item['date_lost_found'])); ?></span>
                                <span class="status-<?php echo strtolower($item['status']); ?>">
                                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($item['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="post-actions">
                            <button class="action-btn contact-btn" data-email="<?php echo htmlspecialchars($item['email']); ?>">
                                <i class="fas fa-envelope"></i> Contact
                            </button>
                            <?php if ($item['category'] == 'Found'): ?>
                                <button class="action-btn claim-btn" data-item="<?php echo $item['id']; ?>">
                                    <i class="fas fa-hand-holding-heart"></i> Claim
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Background Decorations -->
    <div class="bg-circle top-left"></div>
    <div class="bg-circle bottom-right"></div>

    <script src="assets/js/script.js"></script>
    <script>
        // Contact button functionality
        document.querySelectorAll('.contact-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const email = this.getAttribute('data-email');
                window.location.href = `mailto:${email}`;
            });
        });

        // Claim button functionality
        document.querySelectorAll('.claim-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const itemId = this.getAttribute('data-item');
                window.location.href = `claim-item.php?id=${itemId}`;
            });
        });
    </script>
</body>
</html>