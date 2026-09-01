<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('artist')) {
    redirectTo('../auth/login.php');
}

$order_id = $_GET['id'];
$artist_id = $_SESSION['user_id'];

// Verify order belongs to artist
$query = "SELECT o.*, u.name as customer_name, p.name as product_name 
          FROM orders o 
          JOIN users u ON o.customer_id = u.id 
          JOIN products p ON o.product_id = p.id 
          WHERE o.id = :order_id AND o.artist_id = :artist_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->bindParam(':artist_id', $artist_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirectTo('orders.php');
}

// Handle file upload and progress update
if ($_POST && !in_array($order['status'], ['completed', 'approved', 'cancelled'])) {
    $status = $_POST['status'];
    $message = $_POST['message'];
    $attachment = null;
    
    // Handle file upload
    if (isset($_FILES['design_file']) && $_FILES['design_file']['error'] === 0) {
        $file_name = time() . '_' . $_FILES['design_file']['name'];
        $target_path = '../uploads/designs/' . $file_name;
        if (move_uploaded_file($_FILES['design_file']['tmp_name'], $target_path)) {
            $attachment = $file_name;
        }
    }
    
    // Update order status
    $query = "UPDATE orders SET status = :status WHERE id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    
    // Add progress entry
    $query = "INSERT INTO order_progress (order_id, status, message, attachment, created_by) 
              VALUES (:order_id, :status, :message, :attachment, :artist_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':attachment', $attachment);
    $stmt->bindParam(':artist_id', $artist_id);
    $stmt->execute();
    
    $success = "Progress updated successfully!";
    
    // Refresh order data
    $stmt = $db->prepare("SELECT o.*, u.name as customer_name, p.name as product_name 
                          FROM orders o 
                          JOIN users u ON o.customer_id = u.id 
                          JOIN products p ON o.product_id = p.id 
                          WHERE o.id = :order_id AND o.artist_id = :artist_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':artist_id', $artist_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get order progress
$query = "SELECT op.*, u.name as user_name 
          FROM order_progress op 
          JOIN users u ON op.created_by = u.id 
          WHERE op.order_id = :order_id 
          ORDER BY op.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

$customization = json_decode($order['customization_details'], true);

$page_title = "Manage Order - Artist";
include '../includes/header.php';
?>

<div class="artist-container">    
    <main class="content">
        <h1>Manage Order #<?php echo $order['order_id']; ?></h1>
        
        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="work-container">
            <div class="order-details">
                <h3>Order Details</h3>
                <p><strong>Customer:</strong> <?php echo $order['customer_name']; ?></p>
                <p><strong>Product:</strong> <?php echo $order['product_name']; ?></p>
                <p><strong>Price:</strong> $<?php echo number_format($order['total_price'], 2); ?></p>
                <p><strong>Status:</strong> 
                    <span class="status status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                    </span>
                </p>
                
                <?php if ($customization): ?>
                <div class="customization-details">
                    <h4>Customization Requirements:</h4>
                    <?php if (isset($customization['primary_color'])): ?>
                        <p><strong>Primary Color:</strong> <span class="color-box" style="background: <?php echo $customization['primary_color']; ?>"></span></p>
                    <?php endif; ?>
                    <?php if (isset($customization['custom_text'])): ?>
                        <p><strong>Text:</strong> <?php echo $customization['custom_text']; ?></p>
                    <?php endif; ?>
                    <?php if (isset($customization['instructions'])): ?>
                        <p><strong>Instructions:</strong> <?php echo $customization['instructions']; ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="progress-form">
                <h3>Update Progress</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" required <?php echo in_array($order['status'], ['completed', 'approved', 'cancelled']) ? 'disabled' : ''; ?>>
                            <option value="in_progress" <?php echo $order['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <?php if ($order['status'] !== 'completed'): ?>
                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Progress Message:</label>
                        <textarea name="message" rows="3" placeholder="Update message for customer" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Design (Draft/Final):</label>
                        <input type="file" name="design_file" accept="image/*">
                    </div>
                    
                    <button type="submit" class="btn-update" <?php echo in_array($order['status'], ['completed', 'approved', 'cancelled']) ? 'disabled' : ''; ?>>Update Progress</button>
                </form>
            </div>
        </div>
        
        <div class="progress-history">
            <h3>Progress History</h3>
            <?php foreach ($progress as $entry): ?>
            <div class="progress-entry">
                <div class="progress-header">
                    <strong><?php echo $entry['user_name']; ?></strong>
                    <span class="progress-date"><?php echo date('M j, Y g:i A', strtotime($entry['created_at'])); ?></span>
                </div>
                <p><?php echo $entry['message']; ?></p>
                <?php if ($entry['attachment']): ?>
                    <div class="attachment">
                        <a href="../uploads/designs/<?php echo $entry['attachment']; ?>" target="_blank">
                            <img src="../uploads/designs/<?php echo $entry['attachment']; ?>" alt="Design" class="design-preview">
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

<style>
.artist-container {
    display: flex;
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.sidebar {
    width: 250px;
    background: rgba(44, 62, 80, 0.95);
    color: white;
    padding: 20px;
    backdrop-filter: blur(10px);
    box-shadow: 2px 0 20px rgba(0,0,0,0.1);
}

.sidebar h3 {
    color: #ecf0f1;
    margin-bottom: 30px;
    font-size: 1.2rem;
    text-align: center;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.sidebar ul {
    list-style: none;
    padding: 0;
}

.sidebar ul li {
    margin-bottom: 10px;
}

.sidebar ul li a {
    color: #bdc3c7;
    text-decoration: none;
    display: block;
    padding: 12px 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.sidebar ul li a:hover {
    background: rgba(52, 152, 219, 0.2);
    color: white;
    transform: translateX(5px);
}

.content {
    flex: 1;
    padding: 30px;
    background: rgba(255, 255, 255, 0.95);
    margin: 20px;
    border-radius: 20px;
    backdrop-filter: blur(10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.content h1 {
    color: #2c3e50;
    font-size: 2.2rem;
    margin-bottom: 30px;
    text-align: center;
    position: relative;
}

.content h1::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 2px;
}

.success-message {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
    padding: 15px 25px;
    border-radius: 15px;
    margin-bottom: 25px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(39,174,96,0.3);
    font-weight: 500;
}

.work-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.order-details, .progress-form {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    border: 1px solid #f0f0f0;
    position: relative;
    overflow: hidden;
}

.order-details::before, .progress-form::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.order-details h3, .progress-form h3 {
    color: #2c3e50;
    font-size: 1.4rem;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.order-details h3::before {
    content: '📋';
    font-size: 1.2rem;
}

.progress-form h3::before {
    content: '🎨';
    font-size: 1.2rem;
}

.order-details p {
    margin-bottom: 12px;
    color: #34495e;
    font-size: 1rem;
}

.status {
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-in_progress {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
    color: white;
}

.status-completed {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: white;
}

.customization-details {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 15px;
    margin-top: 20px;
    border-left: 4px solid #667eea;
}

.customization-details h4 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-size: 1.1rem;
}

.color-box {
    width: 25px;
    height: 25px;
    display: inline-block;
    border: 2px solid #fff;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    margin-left: 10px;
    vertical-align: middle;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 1rem;
}

.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fcfcfd;
}

.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    outline: none;
    background: white;
}

.btn-update {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 30px;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    width: 100%;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 8px 20px rgba(102,126,234,0.3);
}

.btn-update:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(102,126,234,0.4);
}

.btn-update:disabled {
    background: #95a5a6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.progress-history {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    border: 1px solid #f0f0f0;
    position: relative;
    overflow: hidden;
}

.progress-history::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.progress-history h3 {
    color: #2c3e50;
    font-size: 1.4rem;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-history h3::before {
    content: '📈';
    font-size: 1.2rem;
}

.progress-entry {
    background: #f8f9fa;
    border-left: 4px solid #667eea;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.progress-entry:hover {
    background: #f1f3f4;
    transform: translateX(5px);
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.progress-header strong {
    color: #2c3e50;
    font-size: 1.1rem;
}

.progress-date {
    color: #7f8c8d;
    font-size: 0.9rem;
    font-style: italic;
}

.progress-entry p {
    color: #34495e;
    line-height: 1.6;
    margin-bottom: 15px;
}

.attachment {
    text-align: center;
}

.design-preview {
    max-width: 250px;
    max-height: 200px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    cursor: pointer;
}

.design-preview:hover {
    transform: scale(1.05);
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .artist-container {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
        padding: 15px;
    }
    
    .sidebar ul {
        display: flex;
        overflow-x: auto;
        gap: 10px;
    }
    
    .sidebar ul li {
        margin-bottom: 0;
        white-space: nowrap;
    }
    
    .content {
        margin: 10px;
        padding: 20px;
    }
    
    .work-container {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .content h1 {
        font-size: 1.8rem;
    }
}

@media (max-width: 480px) {
    .content {
        margin: 5px;
        padding: 15px;
    }
    
    .order-details, .progress-form, .progress-history {
        padding: 20px;
    }
    
    .content h1 {
        font-size: 1.5rem;
    }
}
</style>

