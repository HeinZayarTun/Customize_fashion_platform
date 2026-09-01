<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('artist')) {
    redirectTo('../auth/login.php');
}

$artist_id = $_SESSION['user_id'];

// Handle order actions
if ($_POST) {
    $order_id = $_POST['order_id'];
    $action = $_POST['action'];
    
    if ($action === 'accept') {
        $query = "UPDATE orders SET status = 'in_progress' WHERE id = :order_id AND artist_id = :artist_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':artist_id', $artist_id);
        $stmt->execute();
        
        // Add progress entry
        $query = "INSERT INTO order_progress (order_id, status, message, created_by) 
                  VALUES (:order_id, 'in_progress', 'Order accepted by artist', :artist_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':artist_id', $artist_id);
        $stmt->execute();
        
        $success = "Order accepted successfully!";
    } elseif ($action === 'decline') {
        $query = "UPDATE orders SET artist_id = NULL, status = 'pending' WHERE id = :order_id AND artist_id = :artist_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':artist_id', $artist_id);
        $stmt->execute();
        
        $success = "Order declined successfully!";
    }
}

// Get artist's orders
$query = "SELECT o.*, u.name as customer_name, p.name as product_name 
          FROM orders o 
          JOIN users u ON o.customer_id = u.id 
          JOIN products p ON o.product_id = p.id 
          WHERE o.artist_id = :artist_id 
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':artist_id', $artist_id);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = "My Orders - Artist";
include '../includes/header.php';

?>

<div class="artist-container">    
    <main class="content">
        <h1>My Orders</h1>
        
        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="orders-grid">
            <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <h3>Order #<?php echo $order['order_id']; ?></h3>
                    <span class="status status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                    </span>
                </div>
                
                <div class="order-info">
                    <p><strong>Customer:</strong> <?php echo $order['customer_name']; ?></p>
                    <p><strong>Product:</strong> <?php echo $order['product_name']; ?></p>
                    <p><strong>Price:</strong> $<?php echo number_format($order['total_price'], 2); ?></p>
                    <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                </div>
                
                <div class="order-actions">
                    <?php if ($order['status'] === 'assigned'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="action" value="accept" class="btn-accept">Accept</button>
                            <button type="submit" name="action" value="decline" class="btn-decline" 
                                    onclick="return confirm('Are you sure you want to decline this order?')">Decline</button>
                        </form>
                    <?php elseif (in_array($order['status'], ['in_progress', 'review'])): ?>
                        <a href="order_work.php?id=<?php echo $order['id']; ?>" class="btn-work">Manage Work</a>
                    <?php else: ?>
                        <span class="completed">Order <?php echo ucfirst($order['status']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<style>
.success-message {
    background: #27ae60;
    color: white;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.orders-grid {
    display: grid;
    gap: 20px;
}

.order-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ecf0f1;
}

.order-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.btn-accept, .btn-decline, .btn-work {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
}

.btn-accept {
    background: #27ae60;
    color: white;
}

.btn-decline {
    background: #e74c3c;
    color: white;
}

.btn-work {
    background: #3498db;
    color: white;
}

.btn-accept:hover { background: #229954; }
.btn-decline:hover { background: #c0392b; }
.btn-work:hover { background: #2980b9; }

.completed {
    color: #27ae60;
    font-weight: bold;
    font-style: italic;
}
</style>