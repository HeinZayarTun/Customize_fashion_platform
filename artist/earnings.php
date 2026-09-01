<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('artist')) {
    redirectTo('../auth/login.php');
}

$artist_id = $_SESSION['user_id'];

// Get commission rate from settings
$query = "SELECT setting_value FROM settings WHERE setting_key = 'commission_rate'";
$stmt = $db->prepare($query);
$stmt->execute();
$commission_rate = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? 20;

// Get completed orders for earnings calculation
$query = "SELECT o.*, p.name as product_name, u.name as customer_name
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          JOIN users u ON o.customer_id = u.id 
          WHERE o.artist_id = :artist_id AND o.status = 'completed'
          ORDER BY o.updated_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':artist_id', $artist_id);
$stmt->execute();
$completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate earnings
$total_earnings = 0;
$total_orders = count($completed_orders);

foreach ($completed_orders as $order) {
    $artist_share = $order['total_price'] * (100 - $commission_rate) / 100;
    $total_earnings += $artist_share;
}

// Get monthly earnings
$monthly_query = "SELECT 
    DATE_FORMAT(updated_at, '%Y-%m') as month,
    COUNT(*) as orders_count,
    SUM(total_price) as total_revenue
    FROM orders 
    WHERE artist_id = :artist_id AND status = 'completed'
    GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12";
$stmt = $db->prepare($monthly_query);
$stmt->bindParam(':artist_id', $artist_id);
$stmt->execute();
$monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Earnings - Artist";
include '../includes/header.php';
?>

<div class="artist-container"> 
    <main class="content">
        <button class="menu-toggle" onclick="toggleSidebar()">☰ Menu</button>
        <h1>My Earnings</h1>
        
        <div class="earnings-stats">
            <div class="stat-card">
                <h3>Total Earnings</h3>
                <p class="earnings-amount">$<?php echo number_format($total_earnings, 2); ?></p>
            </div>
            <div class="stat-card">
                <h3>Completed Orders</h3>
                <p class="earnings-count"><?php echo $total_orders; ?></p>
            </div>
            <div class="stat-card">
                <h3>Commission Rate</h3>
                <p class="commission-rate"><?php echo $commission_rate; ?>%</p>
            </div>
            <div class="stat-card">
                <h3>Your Share</h3>
                <p class="artist-share"><?php echo (100 - $commission_rate); ?>%</p>
            </div>
        </div>
        
        <div class="earnings-sections">
            <div class="monthly-earnings">
                <h3>Monthly Earnings</h3>
                <table class="earnings-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Orders</th>
                            <th>Total Revenue</th>
                            <th>Your Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_data as $month): ?>
                        <?php 
                        $artist_earnings = $month['total_revenue'] * (100 - $commission_rate) / 100;
                        ?>
                        <tr>
                            <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                            <td><?php echo $month['orders_count']; ?></td>
                            <td>$<?php echo number_format($month['total_revenue'], 2); ?></td>
                            <td>$<?php echo number_format($artist_earnings, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="order-earnings">
                <h3>Completed Orders</h3>
                <table class="earnings-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Total Price</th>
                            <th>Your Earnings</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completed_orders as $order): ?>
                        <?php 
                        $artist_earnings = $order['total_price'] * (100 - $commission_rate) / 100;
                        ?>
                        <tr>
                            <td><?php echo $order['order_id']; ?></td>
                            <td><?php echo $order['customer_name']; ?></td>
                            <td><?php echo $order['product_name']; ?></td>
                            <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                            <td>$<?php echo number_format($artist_earnings, 2); ?></td>
                            <td><?php echo date('M j, Y', strtotime($order['updated_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}
</script>


<style>
.artist-container {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 250px;
    background: #2c3e50;
    color: white;
    padding: 20px;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    z-index: 1000;
}

.sidebar.active {
    transform: translateX(0);
}

.content {
    flex: 1;
    padding: 20px;
    margin-left: 0;
    transition: margin-left 0.3s ease;
}

.earnings-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.earnings-amount {
    font-size: 32px;
    font-weight: bold;
    color: #27ae60;
    margin: 10px 0;
}

.earnings-count {
    font-size: 32px;
    font-weight: bold;
    color: #3498db;
    margin: 10px 0;
}

.commission-rate {
    font-size: 32px;
    font-weight: bold;
    color: #e74c3c;
    margin: 10px 0;
}

.artist-share {
    font-size: 32px;
    font-weight: bold;
    color: #f39c12;
    margin: 10px 0;
}

.earnings-sections {
    display: grid;
    gap: 30px;
}

.monthly-earnings, .order-earnings {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow-x: auto;
}

.earnings-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    min-width: 600px;
}

.earnings-table th,
.earnings-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ecf0f1;
    white-space: nowrap;
}

.earnings-table th {
    background: #34495e;
    color: white;
    font-weight: bold;
}

.earnings-table tr:hover {
    background: #f8f9fa;
}

.menu-toggle {
    display: none;
    background: #3498db;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    margin-bottom: 20px;
}

@media (min-width: 769px) {
    .sidebar {
        position: static;
        transform: translateX(0);
        width: 250px;
    }
    
    .content {
        margin-left: 0;
    }
}

@media (max-width: 768px) {
    .menu-toggle {
        display: block;
    }
    
    .earnings-stats {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .stat-card {
        padding: 15px;
    }
    
    .earnings-amount,
    .earnings-count,
    .commission-rate,
    .artist-share {
        font-size: 24px;
    }
    
    .monthly-earnings,
    .order-earnings {
        padding: 15px;
    }
    
    .earnings-table th,
    .earnings-table td {
        padding: 8px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .content {
        padding: 10px;
    }
    
    .earnings-stats {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .stat-card {
        padding: 10px;
    }
    
    .earnings-amount,
    .earnings-count,
    .commission-rate,
    .artist-share {
        font-size: 20px;
    }
    
    .earnings-table {
        min-width: 500px;
    }
    
    .earnings-table th,
    .earnings-table td {
        padding: 6px;
        font-size: 12px;
    }
}
</style>
