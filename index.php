<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

// Redirect based on user role
switch ($_SESSION['user_role']) {
    case 'admin':
        header('Location: admin/dashboard.php');
        break;
    case 'artist':
        header('Location: artist/dashboard.php');
        break;
    case 'customer':
        header('Location: customer/dashboard.php');
        break;
    default:
        header('Location: auth/login.php');
}
exit();
?>