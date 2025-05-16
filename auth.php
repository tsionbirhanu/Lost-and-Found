<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Auto logout after 15 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    header("Location: login.php?message=Session expired");
    exit();
}

$_SESSION['last_activity'] = time();
?>
