<?php
session_start();

// Redirect to login if not authenticated
if(!isset($_SESSION['user_id'])) {
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

// Fetch found items reported by the user with JOIN to items table
$user_id = $_SESSION['user_id'];
$found_items = [];
$stmt = $conn->prepare("
    SELECT i.id, i.title as item_name, i.description, i.location, 
           i.date_lost_found as found_date, i.status, i.image_path as image
    FROM items i
    JOIN reports r ON i.id = r.item_id
    WHERE r.user_id = ? AND i.category = 'Found'
    ORDER BY i.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    $found_items[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Found Items | Lost and Found</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px;
            margin-top: 20px;
        }

        .item-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .item-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            padding: 20px;
        }

        .item-details h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .item-details p {
            margin: 8px 0;
            color: var(--text-color);
        }

        .status-pending {
            color: var(--primary-color);
            font-weight: 600;
        }

        .item-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .container {
            max-width: 1200px;
            margin: 120px auto 60px;
            padding: 0 20px;
        }

        .page-title {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 30px;
            text-align: center;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-bottom: 30px;
        }

        .no-items {
            text-align: center;
            color: var(--text-color);
            padding: 40px;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .container {
                margin-top: 100px;
            }

            .page-title {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .items-grid {
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
                <a href="found.php" class="active">FOUND</a>
                <a href="about_us.php">ABOUT US</a>
                <div class="separator">|</div>
                <a href="user.php" class="user-icon">
                    <i class="fas fa-user"></i>
                </a>
                <a href="logout.php" class="register-link">Logout</a>
            </div>
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </header>

    <main>
        <div class="container">
            <h1 class="page-title">Found Items</h1>
            
            <div class="action-buttons">
                <a href="report-found.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Report Found Item
                </a>
                <a href="browse-claims.php" class="btn btn-light">
                    <i class="fas fa-list"></i> Browse Claims
                </a>
            </div>
            
            <div class="items-grid">
                <?php if(empty($found_items)): ?>
                    <p class="no-items">
                        <i class="fas fa-box-open" style="font-size: 3rem; color: var(--primary-light); margin-bottom: 15px;"></i><br>
                        You haven't reported any found items yet.
                    </p>
                <?php else: ?>
                    <?php foreach($found_items as $item): ?>
                        <div class="item-card">
                            <div class="item-image">
                                <img src="uploads/<?php echo htmlspecialchars($item['image'] ?? 'default-item.jpg'); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                            </div>
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
                                <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                                <p><strong>Location Found:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                                <p><strong>Date Found:</strong> <?php echo date('M j, Y', strtotime($item['found_date'])); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="status-<?php echo strtolower($item['status']); ?>">
                                        <?php echo htmlspecialchars($item['status']); ?>
                                    </span>
                                </p>
                                <div class="item-actions">
                                    <a href="item-details.php?id=<?php echo $item['id']; ?>" class="btn btn-light">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <?php if($item['status'] == 'Pending'): ?>
                                        <a href="manage-claims.php?item_id=<?php echo $item['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-tasks"></i> Manage Claims
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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