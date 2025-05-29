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
$current_user_id = $_SESSION['user_id'];
$user = [];
$stmt = $conn->prepare("SELECT id, full_name, email, phone, is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}
$stmt->close();

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $item_id = intval($_POST['item_id']);
    $receiver_id = intval($_POST['receiver_id']);
    $content = trim($_POST['content']);
    
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO messages (item_id, sender_id, receiver_id, content) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $item_id, $current_user_id, $receiver_id, $content);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to prevent form resubmission
        header("Location: messages.php?item_id=".$item_id."&with=".$receiver_id);
        exit();
    }
}

// Get conversation parameters from URL
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : null;
$other_user_id = isset($_GET['with']) ? intval($_GET['with']) : null;

// Get list of conversations (simplified without is_read check)
$conversations = [];
$stmt = $conn->prepare("
    SELECT 
        m.item_id, 
        CONCAT('Item #', m.item_id) AS item_name,
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END AS other_user_id,
        u.full_name AS other_user_name,
        MAX(m.created_at) AS last_message_time
    FROM messages m
    JOIN users u ON (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) = u.id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY m.item_id, other_user_id, other_user_name
    ORDER BY last_message_time DESC
");
$stmt->bind_param("iiii", $current_user_id, $current_user_id, $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}
$stmt->close();

// Get messages for current conversation
$messages = [];
$other_user = null;

if ($item_id && $other_user_id) {
    // Get other user information
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $other_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $other_user = $result->fetch_assoc();
    $stmt->close();
    
    // Get messages
    $stmt = $conn->prepare("
        SELECT m.*, u.full_name AS sender_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.item_id = ? AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("iiiii", $item_id, $current_user_id, $other_user_id, $other_user_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
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
    <title>Messages - Lost and Found</title>
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
                <a href="messages.php" class="nav-link active">
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

            <div class="page-header">
                <h1>Messages</h1>
            </div>

            <div class="messages-container">
                <div class="conversations-list">
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state" style="padding: 20px; text-align: center;">
                            <i class="fas fa-comments"></i>
                            <p>No conversations yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <a href="messages.php?item_id=<?php echo $conv['item_id']; ?>&with=<?php echo $conv['other_user_id']; ?>" 
                               class="conversation <?php echo ($item_id == $conv['item_id'] && $other_user_id == $conv['other_user_id']) ? 'active' : ''; ?>">
                                <div class="conversation-avatar">
                                    <?php echo strtoupper(substr($conv['other_user_name'], 0, 1)); ?>
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-name"><?php echo htmlspecialchars($conv['other_user_name']); ?></div>
                                    <div class="conversation-item"><?php echo htmlspecialchars($conv['item_name']); ?></div>
                                    <div class="conversation-time">
                                        <?php echo date('M j, g:i A', strtotime($conv['last_message_time'])); ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="messages-area">
                    <?php if ($item_id && $other_user_id): ?>
                        <div class="messages-header">
                            <h3>
                                <?php echo htmlspecialchars($other_user['full_name']); ?> - 
                                Conversation about Item #<?php echo $item_id; ?>
                            </h3>
                        </div>
                        
                        <div class="messages-list" id="messages-list">
                            <?php if (empty($messages)): ?>
                                <div class="empty-state" style="text-align: center; padding: 40px 0;">
                                    <i class="fas fa-comment-slash"></i>
                                    <p>No messages yet</p>
                                    <p>Start the conversation about this item</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="message <?php echo ($msg['sender_id'] == $current_user_id) ? 'sent' : 'received'; ?>">
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($msg['content'])); ?>
                                        </div>
                                        <div class="message-time">
                                            <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <form class="message-form" method="POST" action="messages.php">
                            <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                            <input type="hidden" name="receiver_id" value="<?php echo $other_user_id; ?>">
                            <textarea name="content" rows="3" placeholder="Type your message..." required></textarea>
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="empty-conversation">
                            <i class="fas fa-comments"></i>
                            <h3>Select a conversation</h3>
                            <p>Choose a conversation from the list to view messages</p>
                        </div>
                    <?php endif; ?>
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
            <a href="messages.php" class="nav-item active">
                <i class="fas fa-comment-dots"></i>
            </a>
            <a href="notifications.php" class="nav-item">
                <i class="fas fa-bell"></i>
            </a>
            <a href="profile.php" class="nav-item">
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
        
        // Auto-scroll to bottom of messages
        <?php if ($item_id && $other_user_id): ?>
            const messagesList = document.getElementById('messages-list');
            if (messagesList) {
                messagesList.scrollTop = messagesList.scrollHeight;
            }
        <?php endif; ?>
    </script>
</body>
</html>