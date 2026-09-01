<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirectTo('../auth/login.php');
}

$order_id = $_GET['id'];
$customer_id = $_SESSION['user_id'];

// Get order with progress
$query = "SELECT o.*, p.name as product_name, u.name as artist_name 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          LEFT JOIN users u ON o.artist_id = u.id 
          WHERE o.id = :order_id AND o.customer_id = :customer_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->bindParam(':customer_id', $customer_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirectTo('orders.php');
}

// Get progress timeline
$query = "SELECT op.*, u.name as user_name, u.role 
          FROM order_progress op 
          JOIN users u ON op.created_by = u.id 
          WHERE op.order_id = :order_id 
          ORDER BY op.created_at ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Order Tracking - Fashion Platform";
include '../includes/header.php';
?>

<div class="customer-container">
    <main class="content">
        <h1>Order Tracking - #<?php echo $order['order_id']; ?></h1>
        
        <div class="tracking-container">
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="summary-details">
                    <p><strong>Product:</strong> <?php echo $order['product_name']; ?></p>
                    <p><strong>Status:</strong> 
                        <span class="status status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                    </p>
                    <p><strong>Artist:</strong> <?php echo $order['artist_name'] ?? 'Not assigned yet'; ?></p>
                    <p><strong>Deadline:</strong> <?php echo $order['deadline'] ? date('M j, Y', strtotime($order['deadline'])) : 'TBD'; ?></p>
                    <p><strong>Total:</strong> $<?php echo number_format($order['total_price'], 2); ?></p>
                </div>
            </div>
            
            <div class="progress-timeline">
                <h3>Order Progress</h3>
                <div class="timeline">
                    <?php foreach ($progress as $step): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <strong><?php echo $step['user_name']; ?></strong>
                                <span class="timeline-role">(<?php echo ucfirst($step['role']); ?>)</span>
                                <span class="timeline-date"><?php echo date('M j, Y g:i A', strtotime($step['created_at'])); ?></span>
                            </div>
                            <p class="timeline-message"><?php echo $step['message']; ?></p>
                            <?php if ($step['attachment']): ?>
                                <div class="timeline-attachment">
                                    <a href="../uploads/designs/<?php echo $step['attachment']; ?>" target="_blank">
                                        <img src="../uploads/designs/<?php echo $step['attachment']; ?>" alt="Design Update" class="attachment-preview">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="order-actions">
                <a href="orders.php" class="btn-back">Back to Orders</a>
                <?php if ($order['status'] === 'in_progress' && $order['artist_name']): ?>
                    <a href="request_changes.php?id=<?php echo $order['id']; ?>" class="btn-changes">Request Changes</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<style>
.tracking-container {
    display: grid;
    gap: 30px;
}

.order-summary {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.summary-details p {
    margin-bottom: 10px;
    font-size: 16px;
}

.progress-timeline {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #3498db;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -37px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #3498db;
    border: 3px solid white;
    box-shadow: 0 0 0 3px #3498db;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #3498db;
}

.timeline-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.timeline-role {
    font-size: 12px;
    color: #7f8c8d;
}

.timeline-date {
    font-size: 12px;
    color: #95a5a6;
    margin-left: auto;
}

.timeline-message {
    margin-bottom: 10px;
    line-height: 1.5;
}

.attachment-preview {
    max-width: 200px;
    max-height: 150px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

.order-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
}

.btn-back, .btn-approve, .btn-changes {
    padding: 12px 24px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    text-align: center;
}

.btn-back {
    background: #95a5a6;
    color: white;
}

.btn-approve {
    background: #27ae60;
    color: white;
}

.btn-changes {
    background: #f39c12;
    color: white;
}

.btn-back:hover { background: #7f8c8d; }
.btn-approve:hover { background: #229954; }
.btn-changes:hover { background: #e67e22; }
</style>

<?php include '../includes/footer.php'; ?>