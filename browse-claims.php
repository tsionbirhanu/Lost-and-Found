<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'Lost_and_Found';

$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user's ID
$current_user_id = $_SESSION['user_id'];

// Fetch claims made by the current user
$user_claims = [];
$stmt = $conn->prepare("
    SELECT c.*, i.title as item_title, i.image_path, i.location, 
           i.date_lost_found, i.item_type, u.full_name as reporter_name
    FROM claims c
    JOIN items i ON c.item_id = i.id
    JOIN users u ON i.user_id = u.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_claims[] = $row;
}
$stmt->close();

// Fetch claims on items reported by the current user (for approval)
$claims_on_my_items = [];
$stmt = $conn->prepare("
    SELECT c.*, i.title as item_title, i.image_path, i.item_type,
           u.full_name as claimant_name, u.email as claimant_email
    FROM claims c
    JOIN items i ON c.item_id = i.id
    JOIN users u ON c.user_id = u.id
    WHERE i.user_id = ? AND i.category = 'Found'
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $claims_on_my_items[] = $row;
}
$stmt->close();

// Handle claim status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $claim_id = $_POST['claim_id'];
    $new_status = $_POST['action'] == 'approve' ? 'Approved' : 'Rejected';
    
    $stmt = $conn->prepare("UPDATE claims SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $claim_id);
    
    if ($stmt->execute()) {
        // If approved, update the item status in items table
        if ($new_status == 'Approved') {
            $update_item = $conn->prepare("
                UPDATE items i
                JOIN claims c ON i.id = c.item_id
                SET i.status = 'Claimed'
                WHERE c.id = ?
            ");
            $update_item->bind_param("i", $claim_id);
            $update_item->execute();
            $update_item->close();
        }
        
        header("Location: browse-claims.php?success=1");
        exit();
    } else {
        $error = "Error updating claim status: " . $conn->error;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Claims | Lost and Found</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="index.php"><img src="images/logo.png" alt="Lost and Found Logo"></a>
            </div>
            <div class="nav-links">
                <a href="index.php">HOME</a>
                <a href="lost.php">LOST</a>
                <a href="found.php" class="active">FOUND</a>
                <a href="about_us.php">ABOUT US</a>
                <div class="separator">|</div>
                <a href="user.php" class="user-icon"><i class="fas fa-user-circle"></i></a>
                <a href="logout.php" class="register-link">Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <h1 class="page-title">Browse Claims</h1>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Claim status updated successfully!</div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Claims on items you've reported as found -->
            <section class="claims-section">
                <h2>Claims on Your Found Items</h2>
                
                <?php if (empty($claims_on_my_items)): ?>
                    <p class="no-items">No one has claimed your found items yet.</p>
                <?php else: ?>
                    <div class="claims-grid">
                        <?php foreach ($claims_on_my_items as $claim): ?>
                            <div class="claim-card">
                                <?php if (!empty($claim['image_path'])): ?>
                                <div class="claim-image">
                                    <img src="uploads/<?php echo htmlspecialchars($claim['image_path']); ?>" alt="<?php echo htmlspecialchars($claim['item_title']); ?>">
                                </div>
                                <?php endif; ?>
                                <div class="claim-details">
                                    <h3><?php echo htmlspecialchars($claim['item_title']); ?></h3>
                                    <p><strong>Type:</strong> <?php echo htmlspecialchars($claim['item_type']); ?></p>
                                    <p><strong>Claimant:</strong> <?php echo htmlspecialchars($claim['claimant_name']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($claim['claimant_email']); ?></p>
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($claim['description']); ?></p>
                                    <p><strong>Date Claimed:</strong> <?php echo date('M j, Y', strtotime($claim['created_at'])); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="status-<?php echo strtolower($claim['status']); ?>">
                                            <?php echo htmlspecialchars($claim['status']); ?>
                                        </span>
                                    </p>
                                    
                                    <?php if ($claim['status'] == 'Pending'): ?>
                                        <form method="post" class="claim-actions">
                                            <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Claims you've made on others' found items -->
            <section class="claims-section">
                <h2>Your Claims on Found Items</h2>
                
                <?php if (empty($user_claims)): ?>
                    <p class="no-items">You haven't made any claims yet.</p>
                <?php else: ?>
                    <div class="claims-grid">
                        <?php foreach ($user_claims as $claim): ?>
                            <div class="claim-card">
                                <?php if (!empty($claim['image_path'])): ?>
                                <div class="claim-image">
                                    <img src="uploads/<?php echo htmlspecialchars($claim['image_path']); ?>" alt="<?php echo htmlspecialchars($claim['item_title']); ?>">
                                </div>
                                <?php endif; ?>
                                <div class="claim-details">
                                    <h3><?php echo htmlspecialchars($claim['item_title']); ?></h3>
                                    <p><strong>Type:</strong> <?php echo htmlspecialchars($claim['item_type']); ?></p>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($claim['location']); ?></p>
                                    <p><strong>Date Found:</strong> <?php echo date('M j, Y', strtotime($claim['date_lost_found'])); ?></p>
                                    <p><strong>Reported By:</strong> <?php echo htmlspecialchars($claim['reporter_name']); ?></p>
                                    <p><strong>Your Description:</strong> <?php echo htmlspecialchars($claim['description']); ?></p>
                                    <p><strong>Date Claimed:</strong> <?php echo date('M j, Y', strtotime($claim['created_at'])); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="status-<?php echo strtolower($claim['status']); ?>">
                                            <?php echo htmlspecialchars($claim['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>