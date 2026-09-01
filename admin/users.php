<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirectTo('../auth/login.php');
}

// Handle user actions
if ($_POST) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'activate') {
        $query = "UPDATE users SET is_active = 1 WHERE id = :id";
    } elseif ($action === 'deactivate') {
        $query = "UPDATE users SET is_active = 0 WHERE id = :id";
    } elseif ($action === 'delete') {
        $query = "DELETE FROM users WHERE id = :id AND role != 'admin'";
    }
    
    if (isset($query)) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $success = "User updated successfully!";
    }
}

// Get all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Users - Admin";
include '../includes/header.php';
?>

<div class="admin-container">
    <nav class="sidebar">
        <h3>Admin Panel</h3>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="../auth/logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <main class="content">
        <h1>Manage Users</h1>
        
        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="users-section">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Approved</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['name']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><span class="role role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                        <td>
                            <span class="status <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="approval <?php echo $user['is_approved'] ? 'approved' : 'pending'; ?>">
                                <?php echo $user['is_approved'] ? 'Yes' : 'Pending'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['role'] !== 'admin'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <?php if ($user['is_active']): ?>
                                    <button type="submit" name="action" value="deactivate" class="btn-deactivate">Deactivate</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="activate" class="btn-activate">Activate</button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="delete" class="btn-delete" 
                                        onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                            </form>
                            <?php else: ?>
                            <span class="protected">Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<style>
.users-section {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.users-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th,
.users-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ecf0f1;
}

.users-table th {
    background: #34495e;
    color: white;
    font-weight: bold;
}

.role {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.role-admin { background: #e74c3c; color: white; }
.role-artist { background: #9b59b6; color: white; }
.role-customer { background: #3498db; color: white; }

.status.active { color: #27ae60; font-weight: bold; }
.status.inactive { color: #e74c3c; font-weight: bold; }

.approval.approved { color: #27ae60; font-weight: bold; }
.approval.pending { color: #f39c12; font-weight: bold; }

.btn-activate, .btn-deactivate, .btn-delete {
    padding: 4px 8px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 11px;
    margin-right: 5px;
}

.btn-activate { background: #27ae60; color: white; }
.btn-deactivate { background: #f39c12; color: white; }
.btn-delete { background: #e74c3c; color: white; }

.protected { color: #95a5a6; font-style: italic; }

.success-message {
    background: #27ae60;
    color: white;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}
</style>
