<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirectTo('../auth/login.php');
}

$order_id = $_GET['id'];
$customer_id = $_SESSION['user_id'];

// Verify order belongs to customer
$query = "SELECT * FROM orders WHERE id = :order_id AND customer_id = :customer_id AND status IN ('review', 'in_progress')";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->bindParam(':customer_id', $customer_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirectTo('orders.php?error=invalid');
}

// Handle change request
if ($_POST) {
    $changes_message = $_POST['changes_message'];
    
    // Update order status to in_progress
    $query = "UPDATE orders SET status = 'in_progress' WHERE id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    
    // Add progress entry with change request
    $query = "INSERT INTO order_progress (order_id, status, message, created_by) 
              VALUES (:order_id, 'changes_requested', :message, :customer_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':message', $changes_message);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    
    redirectTo('orders.php?changes_requested=1');
}

$page_title = "Request Changes - Fashion Platform";
include '../includes/header.php';
?>

<div class="customer-container">
    <main class="content">
        <h1>Request Changes - Order #<?php echo $order['order_id']; ?></h1>
        
        <div class="changes-container">
            <div class="order-info">
                <h3>Order Information</h3>
                <p><strong>Status:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></p>
                <p><strong>Total:</strong> $<?php echo number_format($order['total_price'], 2); ?></p>
            </div>
            
            <div class="changes-form">
                <h3>Request Changes</h3>
                <p>Please describe the changes you'd like the artist to make to your order.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Change Request Details:</label>
                        <textarea name="changes_message" rows="6" 
                                  placeholder="Please be specific about what changes you'd like. Include details about colors, design elements, text, or any other modifications..." 
                                  required></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Send Change Request</button>
                        <a href="order_tracking.php?id=<?php echo $order['id']; ?>" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<style>
.changes-container {
    display: grid;
    gap: 30px;
    max-width: 800px;
    margin: 0 auto;
}

.order-info, .changes-form {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #2c3e50;
}

.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-family: inherit;
    resize: vertical;
    min-height: 120px;
}

.form-actions {
    display: flex;
    gap: 15px;
}

.btn-submit {
    background: #e67e22;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}

.btn-cancel {
    background: #95a5a6;
    color: white;
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
}

.btn-submit:hover { background: #d35400; }
.btn-cancel:hover { background: #7f8c8d; }
</style>

<?php include '../includes/footer.php'; ?>