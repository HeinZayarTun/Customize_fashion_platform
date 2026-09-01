<?php

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirectTo('../auth/login.php');
}

// Handle cancel order
if ($_POST && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    $query = "UPDATE orders SET status = 'cancelled' WHERE id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    if ($stmt->execute()) { $success = "Order cancelled successfully!"; } 
    else { $error = "Failed to cancel order."; }
}

// Handle artist assignment
if ($_POST && isset($_POST['assign_artist'])) {
    $order_id = $_POST['order_id'];
    $artist_id = $_POST['artist_id'];
    $deadline_days = 14; 
    $deadline = date('Y-m-d', strtotime("+{$deadline_days} days"));
    
    $query = "UPDATE orders SET artist_id = :artist_id, status = 'assigned', deadline = :deadline WHERE id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':artist_id', $artist_id);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':deadline', $deadline);
    
    if ($stmt->execute()) {
        $query = "INSERT INTO order_progress (order_id, status, message, created_by) 
                  VALUES (:order_id, 'assigned', 'Order assigned to artist', :admin_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':admin_id', $_SESSION['user_id']);
        $stmt->execute();
        $success = "Artist assigned successfully!";
    } else {
        $error = "Failed to assign artist.";
    }
}

$query = "SELECT o.*, u.name as customer_name, p.name as product_name, a.name as artist_name
          FROM orders o 
          JOIN users u ON o.customer_id = u.id 
          JOIN products p ON o.product_id = p.id 
          LEFT JOIN users a ON o.artist_id = a.id 
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT id, name FROM users WHERE role = 'artist' AND is_approved = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Orders";
include '../includes/header.php';
?>

<style>
    :root {
        --primary: #4f46e5;
        --secondary: #64748b;
        --success: #10b981;
        --danger: #ef4444;
        --warning: #f59e0b;
        --bg: #f8fafc;
        --white: #ffffff;
        --border: #e2e8f0;
    }

    body { background: var(--bg); font-family: 'Inter', system-ui, sans-serif; }

    .admin-layout { display: flex; min-height: 100vh; }
    
    /* Sidebar Styling */
    .sidebar { width: 260px; background: #1e293b; color: white; padding: 1.5rem; }
    .sidebar h3 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: #94a3b8; margin-bottom: 1.5rem; }
    .sidebar ul { list-style: none; padding: 0; }
    .sidebar ul li { margin-bottom: 0.5rem; }
    .sidebar ul li a { color: #cbd5e1; text-decoration: none; display: block; padding: 0.75rem 1rem; border-radius: 8px; transition: 0.2s; }
    .sidebar ul li a:hover { background: #334155; color: white; }
    .sidebar ul li a.active { background: var(--primary); color: white; }

    .main-content { flex: 1; padding: 2.5rem; overflow-x: hidden; }

    .page-header { margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
    .page-header h1 { font-size: 1.75rem; font-weight: 700; color: #0f172a; }

    /* Messages */
    .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid transparent; }
    .alert-success { background: #ecfdf5; color: #065f46; border-color: #d1fae5; }
    .alert-error { background: #fef2f2; color: #991b1b; border-color: #fee2e2; }

    /* Orders Table Card */
    .card { background: var(--white); border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
    .table-container { width: 100%; overflow-x: auto; }
    
    table { width: 100%; border-collapse: collapse; white-space: nowrap; }
    th { background: #f1f5f9; padding: 1rem; text-align: left; font-size: 0.875rem; font-weight: 600; color: var(--secondary); border-bottom: 1px solid var(--border); }
    td { padding: 1rem; font-size: 0.9rem; border-bottom: 1px solid #f8fafc; vertical-align: middle; }

    /* Status Pills */
    .badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .badge-assigned { background: #e0e7ff; color: #3730a3; }
    .badge-completed { background: #dcfce7; color: #166534; }
    .badge-cancelled { background: #fee2e2; color: #991b1b; }

    /* Form Controls */
    .assign-form { display: flex; gap: 0.5rem; align-items: center; }
    select { padding: 0.5rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.875rem; outline: none; transition: border 0.2s; }
    select:focus { border-color: var(--primary); }

    .btn { padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; font-size: 0.875rem; cursor: pointer; border: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: #4338ca; }
    .btn-outline-danger { background: transparent; color: var(--danger); border: 1px solid #fecaca; }
    .btn-outline-danger:hover { background: #fef2f2; border-color: var(--danger); }

    @media (max-width: 1024px) {
        .admin-layout { flex-direction: column; }
        .sidebar { width: 100%; padding: 1rem; }
        .sidebar ul { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .main-content { padding: 1.5rem; }
    }
</style>

<div class="admin-layout">

    <main class="main-content">
        <header class="page-header">
            <h1>Manage Orders</h1>
        </header>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">⚠ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer & Product</th>
                            <th>Status</th>
                            <th>Assigned Artist</th>
                            <th>Order Date</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--primary);">#<?php echo $order['order_id']; ?></td>
                            <td>
                                <div style="font-weight: 500;"><?php echo $order['customer_name']; ?></div>
                                <div style="font-size: 0.75rem; color: var(--secondary);"><?php echo $order['product_name']; ?></div>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($order['artist_name']): ?>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">
                                            <?php echo strtoupper(substr($order['artist_name'], 0, 1)); ?>
                                        </div>
                                        <?php echo $order['artist_name']; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--secondary); font-style: italic;">Not Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--secondary);">
                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 1rem; align-items: center;">
                                    <?php if ($order['status'] == 'pending' && !$order['artist_id']): ?>
                                        <form method="POST" class="assign-form">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="artist_id" required>
                                                <option value="">Choose Artist</option>
                                                <?php foreach ($artists as $artist): ?>
                                                    <option value="<?php echo $artist['id']; ?>"><?php echo $artist['name']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_artist" class="btn btn-primary">Assign</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($order['status'] != 'cancelled' && $order['status'] != 'completed'): ?>
                                        <form method="POST" onsubmit="return confirm('Cancel this order permanently?')">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" name="cancel_order" class="btn btn-outline-danger">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>