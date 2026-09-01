<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
include_once '../includes/header.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirectTo('../auth/login.php');
}

$customer_id = $_SESSION['user_id'];

// Get customer's orders
$query = "SELECT o.*, p.name as product_name, u.name as artist_name 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          LEFT JOIN users u ON o.artist_id = u.id 
          WHERE o.customer_id = :customer_id 
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':customer_id', $customer_id);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured products
$query = "SELECT * FROM products ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Dashboard - Fashion Platform</title>
    <style>
    .customer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-align: center;
        padding: 60px 20px;
        border-radius: 15px;
        margin-bottom: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .hero h1 {
        font-size: 3rem;
        margin-bottom: 15px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    
    .hero p {
        font-size: 1.2rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }
    
    .cta-button {
        background: #ff6b6b;
        color: white;
        padding: 15px 40px;
        text-decoration: none;
        border-radius: 50px;
        font-weight: bold;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(255,107,107,0.4);
    }
    
    .cta-button:hover {
        background: #ff5252;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255,107,107,0.6);
    }
    
    .featured-products, .recent-orders {
        margin-bottom: 50px;
    }
    
    .featured-products h2, .recent-orders h2 {
        font-size: 2.5rem;
        color: #2c3e50;
        margin-bottom: 30px;
        text-align: center;
        position: relative;
    }
    
    .featured-products h2::after, .recent-orders h2::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 2px;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .product-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border: 1px solid #f0f0f0;
    }
    
    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    
    .product-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    
    .product-info {
        padding: 25px;
    }
    
    .product-info h3 {
        font-size: 1.4rem;
        color: #2c3e50;
        margin-bottom: 10px;
    }
    
    .price {
        font-size: 1.3rem;
        font-weight: bold;
        color: #27ae60;
        margin-bottom: 15px;
    }
    
    .description {
        color: #7f8c8d;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    
    .customize-btn {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 12px 25px;
        text-decoration: none;
        border-radius: 25px;
        font-weight: bold;
        transition: all 0.3s ease;
        display: inline-block;
    }
    
    .customize-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    }
    
    .orders-table {
        width: 100%;
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border-collapse: collapse;
    }
    
    .orders-table th {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 20px;
        text-align: left;
        font-weight: bold;
        font-size: 1.1rem;
    }
    
    .orders-table td {
        padding: 18px 20px;
        border-bottom: 1px solid #f0f0f0;
        color: #2c3e50;
    }
    
    .orders-table tr:hover {
        background: #f8f9fa;
    }
    
    .orders-table tr:last-child td {
        border-bottom: none;
    }
    
    @media (max-width: 768px) {
        .customer-container {
            padding: 10px;
        }
        
        .hero {
            padding: 40px 15px;
        }
        
        .hero h1 {
            font-size: 2rem;
        }
        
        .hero p {
            font-size: 1rem;
        }
        
        .products-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .featured-products h2, .recent-orders h2 {
            font-size: 2rem;
        }
        
        .orders-table {
            font-size: 14px;
        }
        
        .orders-table th, .orders-table td {
            padding: 12px 8px;
        }
    }
    
    @media (max-width: 480px) {
        .hero h1 {
            font-size: 1.5rem;
        }
        
        .cta-button {
            padding: 12px 25px;
            font-size: 1rem;
        }
        
        .product-info {
            padding: 15px;
        }
        
        .orders-table {
            font-size: 12px;
        }
    }
    </style>
</head>
<body>
    <div class="customer-container">        
        <main class="content">
            <section class="hero">
                <h1>Welcome, <?php echo $_SESSION['user_name']; ?></h1>
                <p>Create your unique fashion accessories with our talented artists</p>
                <a href="customize.php" class="cta-button">Start Customizing</a>
            </section>
            
            <section class="featured-products">
                <h2>All Products</h2>
                <div class="products-grid">
                    <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <?php 
                        $image_path = "../uploads/products/" . $product['image'];
                        if (!file_exists($image_path) || empty($product['image'])) {
                            $image_path = "data:image/svg+xml,%3Csvg width='200' height='150' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='200' height='150' fill='%23f8f9fa'/%3E%3Ctext x='50%25' y='50%25' font-family='Arial' font-size='12' fill='%23999' text-anchor='middle' dy='.3em'%3ENo Image%3C/text%3E%3C/svg%3E";
                        }
                        ?>
                        <img src="<?php echo $image_path; ?>" alt="<?php echo $product['name']; ?>">
                        <div class="product-info">
                            <h3><?php echo $product['name']; ?></h3>
                            <p class="price">Starting at $<?php echo number_format($product['base_price'], 2); ?></p>
                            <p class="description"><?php echo substr($product['description'], 0, 100); ?>...</p>
                            <a href="customize.php?product=<?php echo $product['id']; ?>" class="customize-btn">Customize Now</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <section class="recent-orders">
                <h2>Your Recent Orders</h2>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Artist</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                        <tr>
                            <td><?php echo $order['order_id']; ?></td>
                            <td><?php echo $order['product_name']; ?></td>
                            <td><?php echo $order['artist_name'] ?? 'Pending Assignment'; ?></td>
                            <td><?php echo ucfirst($order['status']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
<?php include_once '../includes/footer.php'; ?>