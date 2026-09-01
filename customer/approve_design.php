<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirectTo('../auth/login.php');
}

$order_id = $_GET['id'];
$customer_id = $_SESSION['user_id'];

// Verify order belongs to customer and is in review status
$query = "SELECT * FROM orders WHERE id = :order_id AND customer_id = :customer_id AND status = 'review'";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->bindParam(':customer_id', $customer_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirectTo('orders.php?error=invalid');
}

// Handle approval
if ($_POST && $_POST['action'] === 'approve') {
    // Update order status to approved
    $query = "UPDATE orders SET status = 'approved' WHERE id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    
    // Add progress entry
    $query = "INSERT INTO order_progress (order_id, status, message, created_by) 
              VALUES (:order_id, 'approved', 'Design approved by customer', :customer_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    
    redirectTo('orders.php?approved=1');
}

// Handle change request
if ($_POST && $_POST['action'] === 'request_changes') {
    $changes_message = $_POST['changes_message'];
    
    // Update order status back to in_progress
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

$page_title = "Approve Design - Fashion Platform";
include '../includes/header.php';
?>

<div class="customer-container">
    <main class="content">
        <h1>Design Approval - Order #<?php echo $order['order_id']; ?></h1>
        
        <div class="approval-container">
            <div class="design-review">
                <h3>Review Your Design</h3>
                <p>Please review the design submitted by your artist and choose to approve it or request changes.</p>
                
                <div class="approval-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn-approve" onclick="return confirm('Are you sure you want to approve this design? This will move the order to final production.')">
                            ✓ Approve Design
                        </button>
                    </form>
                    
                    <button type="button" class="btn-changes" onclick="showChangesForm()">
                        ✎ Request Changes
                    </button>
                </div>
            </div>
            
            <div id="changes-form" class="changes-form" style="display: none;">
                <h3>Request Changes</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="request_changes">
                    <div class="form-group">
                        <label>Please describe the changes you'd like:</label>
                        <textarea name="changes_message" rows="5" placeholder="Describe what changes you'd like the artist to make..." required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Send Change Request</button>
                        <button type="button" class="btn-cancel" onclick="hideChangesForm()">Cancel</button>
                    </div>
                </form>
            </div>
            
            <div class="order-info">
                <h3>Order Information</h3>
                <p><strong>Product:</strong> <?php echo $order['product_name'] ?? 'Product'; ?></p>
                <p><strong>Total:</strong> $<?php echo number_format($order['total_price'], 2); ?></p>
                <p><strong>Status:</strong> Ready for Review</p>
            </div>
        </div>
        
        <div class="navigation">
            <a href="order_tracking.php?id=<?php echo $order['id']; ?>" class="btn-back">← Back to Tracking</a>
        </div>
    </main>
</div>

<style>
.approval-container {
    display: grid;
    gap: 30px;
    margin-bottom: 30px;
}

.design-review, .changes-form, .order-info {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.approval-actions {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.btn-approve, .btn-changes {
    padding: 15px 30px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
}

.btn-approve {
    background: #27ae60;
    color: white;
}

.btn-changes {
    background: #f39c12;
    color: white;
}

.btn-approve:hover { background: #229954; }
.btn-changes:hover { background: #e67e22; }

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-family: inherit;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 15px;
}

.btn-submit {
    background: #e67e22;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.btn-cancel {
    background: #95a5a6;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.btn-back {
    background: #34495e;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
}

.navigation {
    text-align: center;
}
</style>

<script>
function showChangesForm() {
    document.getElementById('changes-form').style.display = 'block';
}

function hideChangesForm() {
    document.getElementById('changes-form').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>