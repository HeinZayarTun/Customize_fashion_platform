<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirectTo('../auth/login.php');
}

$order_id = $_GET['id'];
$customer_id = $_SESSION['user_id'];

// Get order details
$query = "SELECT o.*, p.name as product_name, p.image as product_image, 
          u.name as artist_name, u.email as artist_email
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

$customization = json_decode($order['customization_details'], true);
$reference_images = json_decode($order['reference_images'], true);

include '../includes/header.php';
?>

<div class="customer-container">
    <main class="content">
        <h1>Order Details - #<?php echo $order['order_id']; ?></h1>
        
        <div class="order-detail-card">
            <div class="order-status">
                <span class="status status-<?php echo $order['status']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                </span>
            </div>
            
            <div class="product-section">
                <h3>Product Information</h3>
                <div class="product-info">
                    <?php 
                    $image_path = "../uploads/products/" . $order['product_image'];
                    if (!file_exists($image_path) || empty($order['product_image'])) {
                        $image_path = "../uploads/products/placeholder.jpg";
                    }
                    ?>
                    <img src="<?php echo $image_path; ?>" 
                         alt="<?php echo $order['product_name']; ?>" class="product-image"
                         onerror="this.src='../uploads/products/placeholder.jpg'">
                    <div class="product-details">
                        <h4><?php echo $order['product_name']; ?></h4>
                        <p><strong>Price:</strong> $<?php echo number_format($order['total_price'], 2); ?></p>
                        <p><strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                        <?php if ($order['deadline']): ?>
                        <p><strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($order['deadline'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($customization): ?>
            <div class="customization-section">
                <h3>Customization Details</h3>
                <div class="customization-grid">
                    <?php if (isset($customization['primary_color'])): ?>
                    <div class="custom-item">
                        <strong>Primary Color:</strong>
                        <div class="color-box" style="background: <?php echo $customization['primary_color']; ?>"></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($customization['secondary_color'])): ?>
                    <div class="custom-item">
                        <strong>Secondary Color:</strong>
                        <div class="color-box" style="background: <?php echo $customization['secondary_color']; ?>"></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($customization['custom_text'])): ?>
                    <div class="custom-item">
                        <strong>Custom Text:</strong> <?php echo $customization['custom_text']; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($customization['size'])): ?>
                    <div class="custom-item">
                        <strong>Size:</strong> <?php echo $customization['size']; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($customization['instructions'])): ?>
                    <div class="custom-item full-width">
                        <strong>Special Instructions:</strong><br>
                        <?php echo nl2br($customization['instructions']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($reference_images && !empty($reference_images)): ?>
            <div class="reference-section">
                <h3>Reference Images</h3>
                <div class="reference-grid">
                    <?php foreach ($reference_images as $image): ?>
                    <?php if (file_exists("../uploads/references/" . $image)): ?>
                    <img src="../uploads/references/<?php echo $image; ?>" alt="Reference" class="reference-image">
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($order['artist_name']): ?>
            <div class="artist-section">
                <h3>Assigned Artist</h3>
                <p><strong>Name:</strong> <?php echo $order['artist_name']; ?></p>
                <p><strong>Email:</strong> <?php echo $order['artist_email']; ?></p>
            </div>
            <?php endif; ?>
            
            <div class="order-actions">
                <a href="orders.php" class="btn-secondary">Back to Orders</a>
                <?php if ($order['status'] == 'pending'): ?>
                    <a href="cancel_order.php?id=<?php echo $order['id']; ?>" 
                       class="btn-danger" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<style>
.order-detail-card {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.order-status {
    text-align: center;
    margin-bottom: 30px;
}

.product-section, .customization-section, .reference-section, .artist-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #ecf0f1;
}

.product-info {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.product-image {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 10px;
}

.customization-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.custom-item {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.custom-item.full-width {
    grid-column: 1 / -1;
}

.color-box {
    width: 30px;
    height: 30px;
    border-radius: 5px;
    border: 1px solid #ddd;
    display: inline-block;
    margin-left: 10px;
}

.reference-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.reference-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 5px;
    border: 1px solid #ddd;
    cursor: pointer;
}

.reference-image:hover {
    opacity: 0.8;
    transform: scale(1.02);
    transition: all 0.3s ease;
}

.btn-danger {
    background: #e74c3c;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;
}

.btn-danger:hover {
    background: #c0392b;
}
</style>

<?php include '../includes/footer.php'; ?>