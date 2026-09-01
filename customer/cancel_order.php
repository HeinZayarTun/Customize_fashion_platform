<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirectTo('../auth/login.php');
}

$order_id = $_GET['id'];
$customer_id = $_SESSION['user_id'];

// Verify order belongs to customer and is cancellable
$query = "SELECT * FROM orders WHERE id = :order_id AND customer_id = :customer_id AND status = 'pending'";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->bindParam(':customer_id', $customer_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirectTo('orders.php?error=invalid');
}

// Update order status to cancelled
$query = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);

if ($stmt->execute()) {
    // Add progress entry
    $query = "INSERT INTO order_progress (order_id, status, message, created_by) 
              VALUES (:order_id, 'cancelled', 'Order cancelled by customer', :customer_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    
    redirectTo('orders.php?cancelled=1');
} else {
    redirectTo('orders.php?error=cancel_failed');
}
?>