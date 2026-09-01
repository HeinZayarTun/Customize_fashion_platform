<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirectTo('../auth/login.php');
}

$artist_id = $_GET['id'];
$action = $_GET['action'];

if ($action === 'approve') {
    $query = "UPDATE users SET is_approved = 1 WHERE id = :id AND role = 'artist'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $artist_id);
    
    if ($stmt->execute()) {
        redirectTo('dashboard.php?approved=1');
    } else {
        redirectTo('dashboard.php?error=approve_failed');
    }
} elseif ($action === 'reject') {
    $query = "DELETE FROM users WHERE id = :id AND role = 'artist' AND is_approved = 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $artist_id);
    
    if ($stmt->execute()) {
        redirectTo('dashboard.php?rejected=1');
    } else {
        redirectTo('dashboard.php?error=reject_failed');
    }
} else {
    redirectTo('dashboard.php');
}
?>