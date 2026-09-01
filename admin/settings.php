<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirectTo('../auth/login.php');
}

$message = '';

// Handle settings update
if ($_POST) {
    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            $query = "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) 
                      ON DUPLICATE KEY UPDATE setting_value = :value";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':value', $value);
            $stmt->execute();
        }
    }
    $message = "Configuration saved successfully!";
}

// Get current settings
$query = "SELECT setting_key, setting_value FROM settings";
$stmt = $db->prepare($query);
$stmt->execute();
$settings_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$settings = [];
foreach ($settings_data as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

$page_title = "Platform Settings";
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
        --border: #e2e8f0;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    body { background-color: var(--bg-main); font-family: 'Inter', system-ui, sans-serif; }

    .admin-layout { display: flex; min-height: 100vh; }
    
    /* Sidebar */
    .sidebar { width: 260px; background: #1e293b; color: white; padding: 1.5rem; flex-shrink: 0; }
    .sidebar h3 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: #94a3b8; margin-bottom: 1.5rem; }
    .sidebar ul { list-style: none; padding: 0; }
    .sidebar ul li { margin-bottom: 0.5rem; }
    .sidebar ul li a { color: #cbd5e1; text-decoration: none; display: block; padding: 0.75rem 1rem; border-radius: 8px; transition: 0.2s; }
    .sidebar ul li a:hover { background: #334155; color: white; }
    .sidebar ul li a.active { background: var(--primary); color: white; }

    .main-content { flex: 1; padding: 2.5rem; max-width: 1200px; margin: 0 auto; width: 100%; }

    .page-header { margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
    .page-header h1 { font-size: 1.875rem; font-weight: 800; color: var(--text-dark); }

    /* Settings Grid */
    .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 2rem; }

    .settings-card { 
        background: var(--card-bg); 
        border-radius: 16px; 
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        padding: 2rem;
        height: fit-content;
    }

    .settings-card h3 { 
        font-size: 1.1rem; 
        font-weight: 700; 
        color: var(--text-dark); 
        margin-bottom: 1.5rem; 
        display: flex; 
        align-items: center; 
        gap: 0.75rem;
    }

    .settings-card h3::before {
        content: ''; width: 4px; height: 18px; background: var(--primary); border-radius: 2px;
    }

    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.5rem; }
    
    input[type="number"], input[type="text"], select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s;
        background: #fcfcfd;
    }

    input:focus, select:focus { 
        outline: none; 
        border-color: var(--primary); 
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); 
        background: white;
    }

    input[readonly] { background: #f1f5f9; cursor: not-allowed; color: var(--text-light); }

    .form-group small { display: block; margin-top: 0.4rem; color: var(--text-light); font-size: 0.75rem; line-height: 1.4; }

    /* Footer Action Bar */
    .action-bar {
        margin-top: 3rem;
        padding: 1.5rem;
        background: white;
        border-radius: 12px;
        border: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        position: sticky;
        bottom: 2rem;
        box-shadow: 0 -10px 15px -3px rgba(0,0,0,0.05);
    }

    .btn-save {
        background: var(--primary);
        color: white;
        padding: 0.75rem 2.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-save:hover { background: var(--primary-hover); transform: translateY(-1px); }

    .alert-success { 
        background: #dcfce7; color: #166534; padding: 1rem; 
        border-radius: 12px; border: 1px solid #bbf7d0; margin-bottom: 2rem;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .admin-layout { flex-direction: column; }
        .sidebar { width: 100%; padding: 1rem; }
        .sidebar ul { display: flex; overflow-x: auto; gap: 0.5rem; }
        .settings-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="admin-layout">

    <main class="main-content">
        <div class="page-header">
            <h1>Platform Settings</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">✓ <?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="settings-grid">
                
                <div class="settings-card">
                    <h3>Commission & Pricing</h3>
                    <div class="form-group">
                        <label>Platform Commission Rate (%)</label>
                        <input type="number" name="commission_rate" id="comm_rate"
                               value="<?php echo $settings['commission_rate'] ?? 20; ?>" 
                               min="0" max="50" required oninput="updatePayout()">
                        <small>The percentage the platform retains from each transaction.</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Artist Payout Rate (%)</label>
                        <input type="number" id="payout_rate" 
                               value="<?php echo (100 - ($settings['commission_rate'] ?? 20)); ?>" 
                               readonly>
                        <small>Calculated automatically (100% - Commission).</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Minimum Order Value ($)</label>
                        <input type="number" name="minimum_order_value" 
                               value="<?php echo $settings['minimum_order_value'] ?? 25; ?>" 
                               min="1" step="0.01" required>
                    </div>
                </div>

                <div class="settings-card">
                    <h3>Assignment & Deadlines</h3>
                    <div class="form-group">
                        <label>Order Assignment Method</label>
                        <select name="assignment_method">
                            <option value="manual" <?php echo ($settings['assignment_method'] ?? 'manual') == 'manual' ? 'selected' : ''; ?>>Manual Assignment</option>
                            <option value="auto" <?php echo ($settings['assignment_method'] ?? 'manual') == 'auto' ? 'selected' : ''; ?>>Automatic (Round Robin)</option>
                            <option value="hybrid" <?php echo ($settings['assignment_method'] ?? 'manual') == 'hybrid' ? 'selected' : ''; ?>>Hybrid Mode</option>
                        </select>
                        <small>Determines how incoming orders are distributed to artists.</small>
                    </div>

                    <div class="form-group">
                        <label>Max Concurrent Orders per Artist</label>
                        <input type="number" name="max_orders_per_artist" 
                               value="<?php echo $settings['max_orders_per_artist'] ?? 5; ?>" 
                               min="1" max="50" required>
                    </div>

                    <div class="form-group">
                        <label>Default Deadline (Days)</label>
                        <input type="number" name="default_deadline_days" 
                               value="<?php echo $settings['default_deadline_days'] ?? 14; ?>" 
                               min="1" max="365" required>
                    </div>
                </div>

                <div class="settings-card">
                    <h3>System & Storage</h3>
                    <div class="form-group">
                        <label>Max File Upload Size (MB)</label>
                        <input type="number" name="max_file_size_mb" 
                               value="<?php echo ($settings['max_file_size'] ?? 5242880) / 1048576; ?>" 
                               min="1" max="100" required>
                    </div>

                    <div class="form-group">
                        <label>Allowed Extensions</label>
                        <input type="text" name="allowed_file_types" 
                               value="<?php echo $settings['allowed_file_types'] ?? 'jpg,jpeg,png,gif'; ?>" 
                               required placeholder="e.g. jpg, png, pdf">
                        <small>Comma-separated extensions without dots.</small>
                    </div>
                </div>

                <div class="settings-card">
                    <h3>Access Control</h3>
                    <div class="form-group">
                        <label>Platform Status</label>
                        <select name="platform_status">
                            <option value="active" <?php echo ($settings['platform_status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Live / Online</option>
                            <option value="maintenance" <?php echo ($settings['platform_status'] ?? 'active') == 'maintenance' ? 'selected' : ''; ?>>Maintenance Mode</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>User Registration</label>
                        <select name="allow_registration">
                            <option value="1" <?php echo ($settings['allow_registration'] ?? 1) == 1 ? 'selected' : ''; ?>>Public (Enabled)</option>
                            <option value="0" <?php echo ($settings['allow_registration'] ?? 1) == 0 ? 'selected' : ''; ?>>Private (Disabled)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Allow New Orders</label>
                        <select name="allow_orders">
                            <option value="1" <?php echo ($settings['allow_orders'] ?? 1) == 1 ? 'selected' : ''; ?>>Accepting Orders</option>
                            <option value="0" <?php echo ($settings['allow_orders'] ?? 1) == 0 ? 'selected' : ''; ?>>Paused</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="action-bar">
                <button type="submit" name="submit" class="btn-save">Save All Settings</button>
            </div>
        </form>
    </main>
</div>

<script>
function updatePayout() {
    const comm = document.getElementById('comm_rate').value;
    document.getElementById('payout_rate').value = 100 - comm;
}
</script>

<?php include '../includes/footer.php'; ?>