<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirectTo('../auth/login.php');
}

$message = '';
$error = '';

// Handle add product
if ($_POST && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $base_price = $_POST['base_price'];
    
    if (!empty($name) && $base_price >= 0) {
        $stmt = $db->prepare("INSERT INTO products (name, base_price) VALUES (:name, :price)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $base_price);
        
        if ($stmt->execute()) {
            $message = 'Product added successfully!';
        } else {
            $error = 'Failed to add product.';
        }
    } else {
        $error = 'Name is required and price must be >= 0.';
    }
}

// Handle image upload
if ($_POST && isset($_POST['upload_image'])) {
    $product_id = $_POST['product_id'];
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['product_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed) && $_FILES['product_image']['size'] <= 5000000) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/products/' . $new_filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $stmt = $db->prepare("UPDATE products SET image = :image WHERE id = :id");
                $stmt->bindParam(':image', $new_filename);
                $stmt->bindParam(':id', $product_id);
                
                if ($stmt->execute()) {
                    $message = 'Image uploaded successfully!';
                } else {
                    $error = 'Failed to update product image.';
                }
            } else {
                $error = 'Failed to upload image.';
            }
        } else {
            $error = 'Invalid file type or size too large (max 5MB).';
        }
    }
}

// Handle price update
if ($_POST && isset($_POST['update_price'])) {
    $product_id = $_POST['product_id'];
    $base_price = $_POST['base_price'];
    
    if ($base_price >= 0) {
        $stmt = $db->prepare("UPDATE products SET base_price = :price WHERE id = :id");
        $stmt->bindParam(':price', $base_price);
        $stmt->bindParam(':id', $product_id);
        
        if ($stmt->execute()) {
            $message = 'Price updated successfully!';
        } else {
            $error = 'Failed to update price.';
        }
    } else {
        $error = 'Price must be >= 0.';
    }
}

// Handle contact update
if ($_POST && isset($_POST['update_contact'])) {
    $product_id = $_POST['product_id'];
    $contact_info = trim($_POST['contact_info']);
    
    $stmt = $db->prepare("UPDATE products SET contact_info = :contact WHERE id = :id");
    $stmt->bindParam(':contact', $contact_info);
    $stmt->bindParam(':id', $product_id);
    
    if ($stmt->execute()) {
        $message = 'Contact info updated successfully!';
    } else {
        $error = 'Failed to update contact info.';
    }
}

// Handle delete product
if ($_POST && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
    $stmt->bindParam(':id', $product_id);
    
    if ($stmt->execute()) {
        $message = 'Product deleted successfully!';
    } else {
        $error = 'Failed to delete product.';
    }
}

// Get all products
$stmt = $db->prepare("SELECT * FROM products ORDER BY name");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Product Management - Admin";
include '../includes/header.php';
?>

<style>
    :root {
        --primary: #4f46e5;
        --primary-hover: #4338ca;
        --bg-main: #f1f5f9;
        --card-bg: #ffffff;
        --text-dark: #0f172a;
        --text-light: #64748b;
        --success: #10b981;
        --danger: #ef4444;
        --warning: #f59e0b;
        --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    body { background-color: var(--bg-main); font-family: 'Inter', sans-serif; }

    .admin-wrapper { display: flex; min-height: 100vh; }
    
    .main-content { flex: 1; padding: 2rem; max-width: 1400px; margin: 0 auto; width: 100%; }

    /* Header & Add Product Bar */
    .page-header { margin-bottom: 2rem; }
    .page-header h1 { font-size: 1.875rem; font-weight: 800; color: var(--text-dark); margin-bottom: 1.5rem; }

    .add-product-card { 
        background: var(--card-bg); 
        padding: 1.5rem; 
        border-radius: 16px; 
        box-shadow: var(--shadow);
        border: 1px solid #e2e8f0;
        margin-bottom: 3rem;
    }
    .add-product-form { display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; }
    
    /* Responsive inputs */
    input[type="text"], input[type="number"], input[type="file"] {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    input:focus { outline: none; border-color: var(--primary); ring: 2px solid #e0e7ff; }

    /* Product Grid */
    .products-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); 
        gap: 2rem; 
    }

    .product-card { 
        background: var(--card-bg); 
        border-radius: 20px; 
        overflow: hidden; 
        box-shadow: var(--shadow);
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s;
    }
    .product-card:hover { transform: translateY(-5px); }

    .image-container { position: relative; height: 200px; background: #f8fafc; }
    .image-container img { width: 100%; height: 100%; object-fit: cover; }
    
    .price-badge { 
        position: absolute; top: 1rem; right: 1rem; 
        background: rgba(255,255,255,0.9); padding: 0.5rem 1rem; 
        border-radius: 12px; font-weight: 700; color: var(--primary);
        backdrop-filter: blur(4px); box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .product-body { padding: 1.5rem; flex-grow: 1; }
    .product-body h3 { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-dark); }
    .contact-text { font-size: 0.875rem; color: var(--text-light); margin-bottom: 1.5rem; }

    /* Actions Section */
    .actions-panel { 
        background: #f8fafc; 
        padding: 1.25rem; 
        border-top: 1px solid #e2e8f0; 
        display: flex; 
        flex-direction: column; 
        gap: 1rem; 
    }

    .form-group { display: flex; gap: 0.5rem; }
    
    .btn { 
        padding: 0.6rem 1rem; border-radius: 8px; font-weight: 600; 
        font-size: 0.85rem; border: none; cursor: pointer; 
        transition: 0.2s; text-align: center;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-success { background: var(--success); color: white; }
    .btn-danger { background: #fff1f2; color: var(--danger); }
    .btn-danger:hover { background: var(--danger); color: white; }

    .file-input-wrapper { position: relative; overflow: hidden; display: block; width: 100%; }

    /* Messages */
    .msg { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500; }
    .msg-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .msg-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    @media (max-width: 768px) {
        .add-product-form { grid-template-columns: 1fr; }
        .main-content { padding: 1rem; }
    }
</style>

<div class="admin-wrapper">
    <main class="main-content">
        <header class="page-header">
            <h1>Product Management</h1>
            
            <?php if ($message): ?>
                <div class="msg msg-success">✓ <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="msg msg-error">⚠ <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="add-product-card">
                <form method="POST" class="add-product-form">
                    <input type="text" name="name" placeholder="New Product Name" required>
                    <input type="number" name="base_price" placeholder="Base Price ($)" step="0.01" min="0" required>
                    <button type="submit" name="add_product" class="btn btn-success" style="padding: 0 2rem;">+ Create Product</button>
                </form>
            </div>
        </header>

        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="image-container">
                    <?php 
                    $image_path = "../uploads/products/" . $product['image'];
                    if (!file_exists($image_path) || empty($product['image'])) {
                        $image_path = "https://ui-avatars.com/api/?name=".urlencode($product['name'])."&background=f1f5f9&color=64748b&size=256";
                    }
                    ?>
                    <img src="<?php echo $image_path; ?>" alt="Product">
                    <div class="price-badge">$<?php echo number_format($product['base_price'], 2); ?></div>
                </div>

                <div class="product-body">
                    <h3><?php echo $product['name']; ?></h3>
                    <div class="contact-text">
                        <strong>Contact:</strong> <?php echo isset($product['contact_info']) ? $product['contact_info'] : 'Not set'; ?>
                    </div>

                    <form method="POST" enctype="multipart/form-data" style="margin-top: 1rem;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="form-group">
                            <input type="file" name="product_image" accept="image/*" required style="font-size: 0.75rem;">
                            <button type="submit" name="upload_image" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                </div>

                <div class="actions-panel">
                    <form method="POST" class="form-group">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="number" name="base_price" value="<?php echo $product['base_price']; ?>" step="0.01" min="0" required>
                        <button type="submit" name="update_price" class="btn btn-primary">Set Price</button>
                    </form>

                    <form method="POST" class="form-group">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="text" name="contact_info" placeholder="Contact info" 
                               value="<?php echo isset($product['contact_info']) ? $product['contact_info'] : ''; ?>">
                        <button type="submit" name="update_contact" class="btn btn-primary">Update Contact</button>
                    </form>

                    <form method="POST" onsubmit="return confirm('Delete this product permanently?')" style="width: 100%;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" name="delete_product" class="btn btn-danger" style="width: 100%;">Delete Product</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>