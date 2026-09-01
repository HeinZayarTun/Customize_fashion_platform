<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/price_calculator.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirectTo('../auth/login.php');
}

$order_id = $_GET['id'];
$customer_id = $_SESSION['user_id'];

// Get order details
$query = "SELECT o.*, p.name as product_name, p.image as product_image, 
          u.name as artist_name
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

// Get price breakdown
$breakdown = getPriceBreakdown($order['product_id'], $order['customization_details'], $db);
$customization = json_decode($order['customization_details'], true);

$page_title = "Order Invoice - Fashion Platform";
include '../includes/header.php';
?>

<div class="customer-container">
    <main class="content">
        <div class="invoice-container">
            <div class="invoice-header">
                <h1>Order Invoice</h1>
                <div class="invoice-info">
                    <p><strong>Order ID:</strong> <?php echo $order['order_id']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="status status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <div class="invoice-details">
                <div class="product-section">
                    <h3>Product Details</h3>
                    <div class="product-info">
                        <p><strong>Product:</strong> <?php echo $order['product_name']; ?></p>
                        <?php if ($customization): ?>
                            <?php if (isset($customization['size'])): ?>
                                <p><strong>Size:</strong> <?php echo $customization['size']; ?></p>
                            <?php endif; ?>
                            <?php if (isset($customization['custom_text']) && !empty($customization['custom_text'])): ?>
                                <p><strong>Custom Text:</strong> <?php echo $customization['custom_text']; ?></p>
                            <?php endif; ?>
                            <?php if (isset($customization['premium']) && $customization['premium'] == '1'): ?>
                                <p><strong>Premium Design:</strong> Yes</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="price-section">
                    <h3>Price Breakdown</h3>
                    <table class="price-table">
                        <tr>
                            <td>Base Product Price</td>
                            <td>$<?php echo number_format($breakdown['base_price'], 2); ?></td>
                        </tr>
                        <?php if ($breakdown['size_price'] > 0): ?>
                        <tr>
                            <td>Size Upgrade (<?php echo $customization['size']; ?>)</td>
                            <td>$<?php echo number_format($breakdown['size_price'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($breakdown['text_price'] > 0): ?>
                        <tr>
                            <td>Custom Text</td>
                            <td>$<?php echo number_format($breakdown['text_price'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($breakdown['premium_price'] > 0): ?>
                        <tr>
                            <td>Premium Design</td>
                            <td>$<?php echo number_format($breakdown['premium_price'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <td><strong>Total Amount</strong></td>
                            <td><strong>$<?php echo number_format($breakdown['total_price'], 2); ?></strong></td>
                        </tr>
                    </table>
                </div>
                
                <?php if ($order['artist_name']): ?>
                <div class="artist-section">
                    <h3>Artist Information</h3>
                    <p><strong>Assigned Artist:</strong> <?php echo $order['artist_name']; ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="invoice-actions">
                <a href="orders.php" class="btn-back">Back to Orders</a>
                <button onclick="window.print()" class="btn-print">Print Invoice</button>
            </div>
        </div>
    </main>
</div>

<style>
.invoice-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 2px solid #3498db;
}

.invoice-info p {
    margin-bottom: 5px;
}

.invoice-details {
    margin-bottom: 40px;
}

.product-section, .price-section, .artist-section {
    margin-bottom: 30px;
}

.product-section h3, .price-section h3, .artist-section h3 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-size: 18px;
}

.price-table {
    width: 100%;
    border-collapse: collapse;
}

.price-table td {
    padding: 12px 0;
    border-bottom: 1px solid #ecf0f1;
}

.price-table td:last-child {
    text-align: right;
    font-weight: bold;
}

.total-row {
    border-top: 2px solid #3498db;
    font-size: 18px;
}

.total-row td {
    padding-top: 20px;
    border-bottom: none;
}

.invoice-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn-back, .btn-print {
    padding: 12px 24px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    border: none;
    cursor: pointer;
}

.btn-back {
    background: #95a5a6;
    color: white;
}

.btn-print {
    background: #3498db;
    color: white;
}

.btn-back:hover { background: #7f8c8d; }
.btn-print:hover { background: #2980b9; }

@media print {
    .invoice-actions {
        display: none;
    }
    
    .invoice-container {
        box-shadow: none;
        padding: 20px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>