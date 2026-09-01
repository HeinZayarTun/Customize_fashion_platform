<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('artist')) {
    redirectTo('../auth/login.php');
}

$artist_id = $_SESSION['user_id'];

// Get artist statistics
$stats = [];
$queries = [
    'total_orders' => "SELECT COUNT(*) as count FROM orders WHERE artist_id = :artist_id",
    'pending_orders' => "SELECT COUNT(*) as count FROM orders WHERE artist_id = :artist_id AND status = 'assigned'",
    'in_progress' => "SELECT COUNT(*) as count FROM orders WHERE artist_id = :artist_id AND status = 'in_progress'",
    'completed_orders' => "SELECT COUNT(*) as count FROM orders WHERE artist_id = :artist_id AND status = 'completed'"
];

foreach ($queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->bindParam(':artist_id', $artist_id);
    $stmt->execute();
    $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Get recent orders
$query = "SELECT o.*, u.name as customer_name, p.name as product_name 
          FROM orders o 
          JOIN users u ON o.customer_id = u.id 
          JOIN products p ON o.product_id = p.id 
          WHERE o.artist_id = :artist_id 
          ORDER BY o.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':artist_id', $artist_id);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total earnings
$query = "SELECT SUM(total_price) as total_revenue FROM orders WHERE artist_id = :artist_id AND status = 'completed'";
$stmt = $db->prepare($query);
$stmt->bindParam(':artist_id', $artist_id);
$stmt->execute();
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

// Get commission rate
$query = "SELECT setting_value FROM settings WHERE setting_key = 'commission_rate'";
$stmt = $db->prepare($query);
$stmt->execute();
$commission_rate = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? 20;

$total_earnings = $total_revenue * (100 - $commission_rate) / 100;

$page_title = "Artist Dashboard";
include '../includes/header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Artist Dashboard - Fashion Platform</title>
    <link rel="stylesheet" href="../assets/css/artist.css">
</head>
<body>
    <div class="artist-container">
        
        <main class="content">
            <h1>Welcome, <?php echo $_SESSION['user_name']; ?></h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p class="stat-number"><?php echo $stats['total_orders']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Orders</h3>
                    <p class="stat-number pending"><?php echo $stats['pending_orders']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>In Progress</h3>
                    <p class="stat-number progress"><?php echo $stats['in_progress']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Completed</h3>
                    <p class="stat-number completed"><?php echo $stats['completed_orders']; ?></p>
                </div>
                <div class="stat-card earnings">
                    <h3>Total Earnings</h3>
                    <p class="stat-number earnings-amount">$<?php echo number_format($total_earnings, 2); ?></p>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="recent-orders-section">
                    <h2>Recent Orders</h2>
                    <?php if (empty($recent_orders)): ?>
                        <div class="no-orders">
                            <p>No orders assigned yet. Wait for admin to assign orders to you.</p>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($recent_orders as $order): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <h4>Order #<?php echo $order['order_id']; ?></h4>
                                    <p><strong>Customer:</strong> <?php echo $order['customer_name']; ?></p>
                                    <p><strong>Product:</strong> <?php echo $order['product_name']; ?></p>
                                    <p><strong>Price:</strong> $<?php echo number_format($order['total_price'], 2); ?></p>
                                </div>
                                <div class="order-status">
                                    <span class="status status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                    <p class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                </div>
                                <div class="order-actions">
                                    <?php if ($order['status'] === 'assigned'): ?>
                                        <a href="orders.php" class="btn-action">Accept/Decline</a>
                                    <?php elseif (in_array($order['status'], ['in_progress', 'review'])): ?>
                                        <a href="order_work.php?id=<?php echo $order['id']; ?>" class="btn-action">Manage Work</a>
                                    <?php else: ?>
                                        <a href="orders.php" class="btn-view">View Details</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="view-all">
                            <a href="orders.php" class="btn-view-all">View All Orders</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="actions-grid">
                        <a href="orders.php" class="action-card">
                            <h3>My Orders</h3>
                            <p>Manage your assigned orders</p>
                        </a>
                        <a href="earnings.php" class="action-card">
                            <h3>Earnings</h3>
                            <p>View your income and payments</p>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<?php include '../includes/footer.php'; ?>