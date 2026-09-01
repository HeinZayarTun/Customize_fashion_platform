<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirectTo('../auth/login.php');
}

$stats = [];
$queries = [
    'total_users' => "SELECT COUNT(*) as count FROM users",
    'total_orders' => "SELECT COUNT(*) as count FROM orders",
    'pending_orders' => "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'",
    'total_artists' => "SELECT COUNT(*) as count FROM users WHERE role = 'artist'",
    'pending_artists' => "SELECT COUNT(*) as count FROM users WHERE role = 'artist' AND is_approved = 0"
];

foreach ($queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

$query = "SELECT id, name, email, created_at FROM users WHERE role = 'artist' AND is_approved = 0 ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT o.order_id, u.name as customer_name, p.name as product_name, o.status, o.created_at 
          FROM orders o 
          JOIN users u ON o.customer_id = u.id 
          JOIN products p ON o.product_id = p.id 
          ORDER BY o.created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Admin Dashboard";
include '../includes/header.php';
?>

<style>
    :root {
        --primary: #4f46e5;
        --bg-body: #f8fafc;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --white: #ffffff;
        --success: #22c55e;
        --danger: #ef4444;
        --warning: #f59e0b;
        --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    }

    body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; color: var(--text-main); }

    .dashboard-wrapper { display: flex; min-height: 100vh; }
    
    .content-area { flex: 1; padding: 2rem; max-width: 1200px; margin: 0 auto; width: 100%; }

    /* Header Section */
    .dashboard-header { margin-bottom: 2rem; }
    .dashboard-header h1 { font-size: 1.875rem; font-weight: 700; margin-bottom: 0.5rem; }

    /* Stats Grid */
    .stats-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
        gap: 1.5rem; 
        margin-bottom: 2.5rem; 
    }
    .stat-card { 
        background: var(--white); 
        padding: 1.5rem; 
        border-radius: 12px; 
        box-shadow: var(--shadow);
        border: 1px solid #e2e8f0;
    }
    .stat-card h3 { color: var(--text-muted); font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.025em; margin-bottom: 0.5rem; }
    .stat-card p { font-size: 1.5rem; font-weight: 700; color: var(--primary); }

    /* Tables & Lists */
    .section-card { 
        background: var(--white); 
        border-radius: 12px; 
        box-shadow: var(--shadow); 
        padding: 1.5rem; 
        margin-bottom: 2rem; 
        overflow: hidden;
    }
    .section-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between; }

    .data-table { width: 100%; border-collapse: collapse; text-align: left; }
    .data-table th { background: #f1f5f9; padding: 12px; font-size: 0.85rem; color: var(--text-muted); }
    .data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }

    /* Status Badges */
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .badge-success { background: #dcfce7; color: #166534; }

    /* Artist Cards */
    .artist-item { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 1rem; 
        border-bottom: 1px solid #f1f5f9;
    }
    .artist-item:last-child { border-bottom: none; }

    .btn { padding: 8px 16px; border-radius: 6px; font-size: 0.875rem; text-decoration: none; font-weight: 500; transition: 0.2s; }
    .btn-approve { background: var(--primary); color: white; }
    .btn-reject { background: #fee2e2; color: var(--danger); margin-left: 0.5rem; }
    .btn:hover { opacity: 0.9; }

    /* Alerts */
    .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    @media (max-width: 768px) {
        .artist-item { flex-direction: column; align-items: flex-start; gap: 1rem; }
        .data-table { display: block; overflow-x: auto; }
    }
</style>

<div class="dashboard-wrapper">
    <main class="content-area">
        <header class="dashboard-header">
            <h1>Admin Overview</h1>
            <p style="color: var(--text-muted)">Welcome back! Here is what's happening today.</p>
        </header>

        <?php if (isset($_GET['approved'])): ?>
            <div class="alert alert-success">✓ Artist approved successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">⚠ Error: Action failed. Please try again.</div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Users</h3>
                <p><?php echo $stats['total_users']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <p><?php echo $stats['total_orders']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <p style="color: var(--warning)"><?php echo $stats['pending_orders']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Artists</h3>
                <p><?php echo $stats['total_artists']; ?></p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
            
            <?php if (!empty($pending_artists)): ?>
            <section class="section-card">
                <div class="section-title">Pending Approvals <span><?php echo count($pending_artists); ?></span></div>
                <?php foreach ($pending_artists as $artist): ?>
                <div class="artist-item">
                    <div>
                        <div style="font-weight: 600;"><?php echo $artist['name']; ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted)"><?php echo $artist['email']; ?></div>
                    </div>
                    <div>
                        <a href="approve_artist.php?id=<?php echo $artist['id']; ?>&action=approve" class="btn btn-approve">Approve</a>
                        <a href="approve_artist.php?id=<?php echo $artist['id']; ?>&action=reject" class="btn btn-reject" onclick="return confirm('Reject this artist?')">Reject</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <section class="section-card">
                <div class="section-title">Recent Orders</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td style="font-weight: 600;">#<?php echo $order['order_id']; ?></td>
                            <td><?php echo $order['customer_name']; ?></td>
                            <td><?php echo $order['product_name']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo ($order['status'] == 'pending') ? 'pending' : 'success'; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>