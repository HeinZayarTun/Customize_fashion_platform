<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirectTo('../auth/login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle password change
if ($_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                $message = 'Password changed successfully!';
            } else {
                $error = 'Failed to update password.';
            }
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

$page_title = "My Profile - Fashion Platform";
include '../includes/header.php';
?>

<style>
    :root {
        --primary: #4f46e5;
        --primary-hover: #4338ca;
        --bg-main: #f8fafc;
        --card-bg: #ffffff;
        --text-dark: #0f172a;
        --text-light: #64748b;
        --success: #10b981;
        --danger: #ef4444;
        --border: #e2e8f0;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    body { background-color: var(--bg-main); font-family: 'Inter', system-ui, sans-serif; }

    .profile-container {
        max-width: 800px;
        margin: 3rem auto;
        padding: 0 1.5rem;
    }

    .page-header { margin-bottom: 2rem; }
    .page-header h1 { font-size: 1.875rem; font-weight: 800; color: var(--text-dark); }

    /* Alert Styling */
    .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid transparent; font-weight: 500; }
    .alert-success { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .alert-error { background: #fef2f2; color: #991b1b; border-color: #fee2e2; }

    /* Card Styling */
    .profile-card { 
        background: var(--card-bg); 
        padding: 2.5rem; 
        border-radius: 16px; 
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
    }

    .profile-card h2 { 
        font-size: 1.25rem; 
        color: var(--text-dark); 
        margin-bottom: 1.5rem; 
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .profile-card h2::before {
        content: ''; width: 4px; height: 20px; background: var(--primary); border-radius: 2px;
    }

    /* Form Elements */
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { 
        display: block; 
        font-size: 0.875rem; 
        font-weight: 600; 
        color: var(--text-dark); 
        margin-bottom: 0.5rem; 
    }

    input[type="password"] {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.2s ease-in-out;
        background: #fcfcfd;
    }

    /* Fixed the "warring" ring issue here too */
    input:focus { 
        outline: none; 
        border-color: var(--primary); 
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        background: white;
    }

    .btn-submit { 
        background: var(--primary); 
        color: white; 
        padding: 0.8rem 2rem; 
        border: none; 
        border-radius: 8px; 
        font-weight: 700; 
        cursor: pointer; 
        transition: 0.2s;
        width: 100%;
        font-size: 1rem;
    }

    .btn-submit:hover { 
        background: var(--primary-hover); 
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    }

    @media (max-width: 640px) {
        .profile-card { padding: 1.5rem; }
    }
</style>

<div class="profile-container">
    <header class="page-header">
        <h1>Account Settings</h1>
    </header>

    <?php if ($message): ?>
        <div class="alert alert-success">✓ <?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">⚠ <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="profile-card">
        <h2>Security & Password</h2>
        <form method="POST">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" placeholder="••••••••" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required>
            </div>
            
            <button type="submit" name="change_password" class="btn-submit">Update Password</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>