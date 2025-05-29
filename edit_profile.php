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

// Initialize variables
$errors = [];
$success = false;

// Get current user's information
$user_id = $_SESSION['user_id'];
$user = [];
$stmt = $conn->prepare("SELECT id, full_name, email, phone, profile_photo FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validate full name
    if (empty($full_name)) {
        $errors['full_name'] = "Full name is required";
    } elseif (strlen($full_name) > 100) {
        $errors['full_name'] = "Full name must be less than 100 characters";
    }

    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    } elseif (strlen($email) > 100) {
        $errors['email'] = "Email must be less than 100 characters";
    } else {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['email'] = "Email is already taken";
        }
        $stmt->close();
    }

    // Validate phone (optional)
    if (!empty($phone) && !preg_match('/^[0-9]{10,20}$/', $phone)) {
        $errors['phone'] = "Phone number must be 10-20 digits";
    }

    // Validate password change if any password field is filled
    if (!empty($current_password)) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $db_user = $result->fetch_assoc();
        $stmt->close();

        if (!password_verify($current_password, $db_user['password'])) {
            $errors['current_password'] = "Current password is incorrect";
        } elseif (empty($new_password)) {
            $errors['new_password'] = "New password is required";
        } elseif (strlen($new_password) < 8) {
            $errors['new_password'] = "New password must be at least 8 characters";
        } elseif ($new_password !== $confirm_password) {
            $errors['confirm_password'] = "Passwords do not match";
        }
    }

    // Handle file upload
    $profile_photo = $user['profile_photo'] ?? null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profile_photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $file_name = 'user_' . $user_id . '_' . time() . '.' . strtolower($file_ext);
        $target_file = $upload_dir . $file_name;

        // Validate image file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array(strtolower($file_ext), $allowed_types)) {
            $errors['profile_photo'] = "Only JPG, JPEG, PNG & GIF files are allowed";
        } elseif ($_FILES['profile_photo']['size'] > $max_size) {
            $errors['profile_photo'] = "File size must be less than 2MB";
        } elseif (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file)) {
            // Delete old photo if it exists
            if ($profile_photo && file_exists($profile_photo)) {
                unlink($profile_photo);
            }
            $profile_photo = $target_file;
        } else {
            $errors['profile_photo'] = "Error uploading file";
        }
    }

    // If no errors, update the profile
    if (empty($errors)) {
        // Prepare the update query
        if (!empty($current_password)) {
            // Update with password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, profile_photo = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $full_name, $email, $phone, $profile_photo, $hashed_password, $user_id);
        } else {
            // Update without password
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, profile_photo = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $full_name, $email, $phone, $profile_photo, $user_id);
        }

        if ($stmt->execute()) {
            $success = true;
            // Update the user array with new values
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['profile_photo'] = $profile_photo;
        } else {
            $errors['general'] = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    }
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
    <title>Edit Profile - Lost and Found</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>
    <div class="main-container">
        <!-- Side Navigation - Desktop -->
        <div class="side-nav">
            <div class="nav-header">
                <img src="<?php echo !empty($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : 'images/profile.jpg'; ?>" alt="<?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>">
                <h2><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></h2>
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
                <a href="user.php" class="nav-link active">
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
                        <img src="<?php echo !empty($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : 'images/profile.jpg'; ?>" alt="<?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>">
                    </div>
                    <div class="profile-info">
                        <h1 class="profile-name">Edit Profile</h1>
                    </div>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Profile updated successfully!
                    </div>
                <?php elseif (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($errors['general']); ?>
                    </div>
                <?php endif; ?>
                
                <form action="edit_profile.php" method="POST" class="profile-form" enctype="multipart/form-data">
                    <div class="profile-image-upload">
                        <img id="profile-preview" src="<?php echo !empty($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : 'images/profile.jpg'; ?>" class="profile-image-preview" alt="Profile Preview">
                        <input type="file" id="profile-photo" name="profile_photo" accept="image/*" class="profile-image-input">
                        <label for="profile-photo" class="profile-image-label">
                            <i class="fas fa-camera"></i> Change Photo
                        </label>
                        <?php if (isset($errors['profile_photo'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['profile_photo']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                               class="<?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>">
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['full_name']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                               class="<?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                               class="<?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>">
                        <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['phone']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password (required for password change)</label>
                        <input type="password" id="current_password" name="current_password" 
                               class="<?php echo isset($errors['current_password']) ? 'is-invalid' : ''; ?>">
                        <?php if (isset($errors['current_password'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['current_password']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               class="<?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>">
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['new_password']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="<?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>">
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="profile.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
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
        
        // Profile photo preview
        document.getElementById('profile-photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profile-preview').src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>