<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirectTo('../auth/login.php');
}

$customer_id = $_SESSION['user_id'];
$page_title = "My Orders - Fashion Platform";

// Get customer's orders with details
$query = "SELECT o.*, p.name as product_name, p.image as product_image, 
          u.name as artist_name, u.email as artist_email
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          LEFT JOIN users u ON o.artist_id = u.id 
          WHERE o.customer_id = :customer_id 
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':customer_id', $customer_id);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="customer-container">
    <main class="content">
        <h1>My Orders</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">Order placed successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['approved'])): ?>
            <div class="success-message">Design approved successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['changes_requested'])): ?>
            <div class="success-message">Change request sent to artist!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php 
                switch($_GET['error']) {
                    case 'invalid': echo 'Invalid order or order cannot be cancelled.'; break;
                    case 'cancel_failed': echo 'Failed to cancel order. Please try again.'; break;
                    default: echo 'An error occurred.';
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <p>You haven't placed any orders yet.</p>
                <a href="customize.php" class="cta-button">Start Customizing</a>
            </div>
        <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <h3>Order #<?php echo $order['order_id']; ?></h3>
                        <span class="status status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    
                    <div class="order-details">
                        <div class="product-info">
                            <?php 
                            $image_path = "../uploads/products/" . $order['product_image'];
                            if (!file_exists($image_path) || empty($order['product_image'])) {
                                $image_path = "data:image/svg+xml,%3Csvg width='80' height='80' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='80' height='80' fill='%23f8f9fa'/%3E%3Ctext x='50%25' y='50%25' font-family='Arial' font-size='10' fill='%23999' text-anchor='middle' dy='.3em'%3ENo Image%3C/text%3E%3C/svg%3E";
                            }
                            ?>
                            <img src="<?php echo $image_path; ?>" 
                                 alt="<?php echo $order['product_name']; ?>" class="product-thumb">
                            <div>
                                <h4><?php echo $order['product_name']; ?></h4>
                                <p>Price: $<?php echo number_format($order['total_price'], 2); ?></p>
                                <p>Ordered: <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <?php if ($order['artist_name']): ?>
                        <div class="artist-info">
                            <p><strong>Artist:</strong> <?php echo $order['artist_name']; ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($order['deadline']): ?>
                        <div class="deadline-info">
                            <p><strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($order['deadline'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        $reference_images = json_decode($order['reference_images'], true);
                        if ($reference_images && !empty($reference_images)): 
                            $valid_images = [];
                            foreach ($reference_images as $image) {
                                if (file_exists("../uploads/references/" . $image)) {
                                    $valid_images[] = $image;
                                }
                            }
                            if (!empty($valid_images)):
                        ?>
                        <div class="reference-images">
                            <p><strong>Reference Images:</strong></p>
                            <div class="reference-thumbnails">
                                <?php foreach ($valid_images as $image): ?>
                                <img src="../uploads/references/<?php echo $image; ?>" alt="Reference" class="reference-thumb" onclick="openImageModal('<?php echo $image; ?>')">
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; endif; ?>
                    </div>
                    
                    <div class="order-actions">
                        <a href="order_tracking.php?id=<?php echo $order['id']; ?>" class="btn-primary">Track Progress</a>
                        <?php if ($order['status'] == 'pending'): ?>
                            <a href="cancel_order.php?id=<?php echo $order['id']; ?>" 
                               class="btn-secondary" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel</a>
                        <?php elseif (in_array($order['status'], ['in_progress']) && $order['artist_name']): ?>
                            <a href="request_changes.php?id=<?php echo $order['id']; ?>" class="btn-changes">Request Changes</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<style>
.customer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.content h1 {
    text-align: center;
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 40px;
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
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(39,174,96,0.3);
    font-weight: 500;
}

.error-message {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(231,76,60,0.3);
    font-weight: 500;
}

.no-orders {
    text-align: center;
    padding: 80px 40px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    border: 1px solid #f0f0f0;
}

.no-orders p {
    font-size: 1.3rem;
    color: #6c757d;
    margin-bottom: 30px;
}

.cta-button {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 40px;
    text-decoration: none;
    border-radius: 50px;
    font-weight: bold;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(102,126,234,0.3);
    display: inline-block;
}

.cta-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(102,126,234,0.4);
}

.orders-grid {
    display: grid;
    gap: 30px;
}

.order-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.order-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.order-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f8f9fa;
}

.order-header h3 {
    color: #2c3e50;
    font-size: 1.3rem;
    margin: 0;
}

.status {
    padding: 8px 20px;
    border-radius: 25px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.status-pending { background: linear-gradient(135deg, #f39c12, #e67e22); color: white; }
.status-assigned { background: linear-gradient(135deg, #3498db, #2980b9); color: white; }
.status-in_progress { background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white; }
.status-completed { background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; }
.status-cancelled { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; }

.order-details {
    margin-bottom: 25px;
}

.product-info {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    align-items: center;
}

.product-thumb {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.product-info div h4 {
    color: #2c3e50;
    font-size: 1.2rem;
    margin-bottom: 8px;
}

.product-info div p {
    color: #6c757d;
    margin-bottom: 5px;
    font-size: 0.95rem;
}

.artist-info, .deadline-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 15px;
    border-left: 4px solid #667eea;
}

.reference-images {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid #764ba2;
}

.reference-thumbnails {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.reference-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 10px;
    border: 2px solid #fff;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.reference-thumb:hover {
    transform: scale(1.1);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.order-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn-primary, .btn-secondary, .btn-changes {
    padding: 12px 25px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-primary {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    box-shadow: 0 6px 20px rgba(52,152,219,0.3);
}

.btn-secondary {
    background: linear-gradient(135deg, #95a5a6, #7f8c8d);
    color: white;
    box-shadow: 0 6px 20px rgba(149,165,166,0.3);
}

.btn-changes {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: white;
    box-shadow: 0 6px 20px rgba(243,156,18,0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(52,152,219,0.4);
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(149,165,166,0.4);
}

.btn-changes:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(243,156,18,0.4);
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
    backdrop-filter: blur(5px);
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 90%;
    max-height: 90%;
    border-radius: 15px;
    overflow: hidden;
}

.modal img {
    width: 100%;
    height: auto;
    display: block;
}

.close {
    position: absolute;
    top: 20px;
    right: 35px;
    color: white;
    font-size: 50px;
    font-weight: bold;
    cursor: pointer;
    z-index: 1001;
    transition: all 0.3s ease;
}

.close:hover {
    color: #ff6b6b;
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .customer-container {
        padding: 15px;
    }
    
    .content h1 {
        font-size: 2rem;
    }
    
    .order-card {
        padding: 20px;
    }
    
    .product-info {
        flex-direction: column;
        text-align: center;
    }
    
    .product-thumb {
        width: 80px;
        height: 80px;
    }
    
    .order-actions {
        justify-content: center;
    }
    
    .btn-primary, .btn-secondary, .btn-changes {
        flex: 1;
        text-align: center;
        min-width: 120px;
    }
}

@media (max-width: 480px) {
    .content h1 {
        font-size: 1.5rem;
    }
    
    .order-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .order-actions {
        flex-direction: column;
    }
}
</style>

<!-- Image Modal -->
<div id="imageModal" class="modal">
    <span class="close" onclick="closeImageModal()">&times;</span>
    <div class="modal-content">
        <img id="modalImage" src="" alt="Reference Image">
    </div>
</div>

<script>
function openImageModal(imageName) {
    document.getElementById('imageModal').style.display = 'block';
    document.getElementById('modalImage').src = '../uploads/references/' + imageName;
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}

// Close modal when clicking outside the image
window.onclick = function(event) {
    const modal = document.getElementById('imageModal');
    if (event.target == modal) {
        closeImageModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>