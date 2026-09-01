<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/price_calculator.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirectTo('../auth/login.php');
}

// Get products for customization
$query = "SELECT p.*, c.name as category_name FROM products p 
          JOIN categories c ON p.category_id = c.id 
          WHERE p.is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_POST) {
    $customer_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $customization = json_encode($_POST['customization']);
    $order_id = generateOrderId();
    
    // Handle file uploads
    $reference_images = [];
    if (isset($_FILES['reference_images'])) {
        foreach ($_FILES['reference_images']['tmp_name'] as $key => $tmp_name) {
            if (!empty($tmp_name)) {
                $file_name = time() . '_' . $_FILES['reference_images']['name'][$key];
                $target_path = '../uploads/references/' . $file_name;
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $reference_images[] = $file_name;
                }
            }
        }
    }
    
    $query = "INSERT INTO orders (order_id, customer_id, product_id, customization_details, reference_images, total_price) 
              VALUES (:order_id, :customer_id, :product_id, :customization, :references, :price)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->bindParam(':customization', $customization);
    $stmt->bindParam(':references', json_encode($reference_images));
    
    // Get product base price if total_price is not set
    $total_price = isset($_POST['total_price']) && $_POST['total_price'] > 0 ? $_POST['total_price'] : 0;
    if ($total_price == 0) {
        // Get base price from database
        $query_price = "SELECT base_price FROM products WHERE id = :product_id";
        $stmt_price = $db->prepare($query_price);
        $stmt_price->bindParam(':product_id', $product_id);
        $stmt_price->execute();
        $product_data = $stmt_price->fetch(PDO::FETCH_ASSOC);
        $total_price = $product_data['base_price'] ?? 0;
        
        // Add customization costs
        $total_price = calculateOrderPrice($product_id, $customization, $db);
    }
    
    $stmt->bindParam(':price', $total_price);
    
    if ($stmt->execute()) {
        $new_order_id = $db->lastInsertId();
        
        // Add initial progress entry
        $query = "INSERT INTO order_progress (order_id, status, message, created_by) 
                  VALUES (:order_id, 'pending', 'Order placed successfully', :customer_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':order_id', $new_order_id);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();
        
        // Set deadline
        $deadline_days = 14; // Default from settings
        $deadline = date('Y-m-d', strtotime("+{$deadline_days} days"));
        $query = "UPDATE orders SET deadline = :deadline WHERE id = :order_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':deadline', $deadline);
        $stmt->bindParam(':order_id', $new_order_id);
        $stmt->execute();
        
        header('Location: orders.php?success=1');
        exit();
    }
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customize - Fashion Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>        
        <main class="content">
            <h1>Customize Your Product</h1>
            
            <form method="POST" enctype="multipart/form-data" class="customize-form">
                <div class="form-section">
                    <h3>Select Product</h3>
                    <select name="product_id" required>
                        <option value="">Choose a product</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['base_price']; ?>">
                            <?php echo $product['name']; ?> - $<?php echo $product['base_price']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-section">
                    <h3>Customization Options</h3>
                    <div class="color-inputs">
                        <div class="color-group">
                            <label>Primary Color:</label>
                            <input type="color" name="customization[primary_color]" value="#000000" onchange="calculatePrice()">
                        </div>
                        <div class="color-group">
                            <label>Secondary Color:</label>
                            <input type="color" name="customization[secondary_color]" value="#ffffff" onchange="calculatePrice()">
                        </div>
                    </div>
                    
                    <label>Custom Text:</label>
                    <input type="text" name="customization[custom_text]" placeholder="Enter custom text" onchange="calculatePrice()">
                    
                    <label>Size:</label>
                    <select name="customization[size]" onchange="calculatePrice()">
                        <option value="XS" data-price="0">XS</option>
                        <option value="S" data-price="0">S</option>
                        <option value="M" data-price="0">M</option>
                        <option value="L" data-price="5">L (+$5)</option>
                        <option value="XL" data-price="10">XL (+$10)</option>
                    </select>
                    
                    <label>
                        <input type="checkbox" name="customization[premium]" value="1" data-price="25" onchange="calculatePrice()"> 
                        Add premium design (+$25)
                    </label>
                </div>
                
                <div class="form-section">
                    <h3>Reference Images</h3>
                    <input type="file" name="reference_images[]" multiple accept="image/*">
                    <small>Upload reference images for your design</small>
                </div>
                
                <div class="form-section">
                    <h3>Special Instructions</h3>
                    <textarea name="customization[instructions]" rows="4" placeholder="Any special instructions for the artist"></textarea>
                </div>
                
                <div class="form-section">
                    <h3>Price Breakdown</h3>
                    <div class="price-breakdown">
                        <div class="price-item">
                            <span>Base Price:</span>
                            <span id="base-price">$0.00</span>
                        </div>
                        <div class="price-item">
                            <span>Size Upgrade:</span>
                            <span id="size-price">$0.00</span>
                        </div>
                        <div class="price-item">
                            <span>Premium Design:</span>
                            <span id="premium-price">$0.00</span>
                        </div>
                        <div class="price-item">
                            <span>Custom Text:</span>
                            <span id="text-price">$0.00</span>
                        </div>
                        <div class="price-total">
                            <span><strong>Total Price:</strong></span>
                            <span id="total-price"><strong>$0.00</strong></span>
                        </div>
                    </div>
                    <input type="hidden" name="total_price" id="total-price-input" value="0">
                </div>
                
                <button type="submit" class="submit-btn">Place Order</button>
            </form>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <style>
    .content {
        max-width: 1000px;
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
    
    .customize-form {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        border: 1px solid #f0f0f0;
    }
    
    .form-section {
        margin-bottom: 40px;
        padding: 25px;
        background: #f8f9fa;
        border-radius: 15px;
        border-left: 5px solid #667eea;
        transition: all 0.3s ease;
    }
    
    .form-section:hover {
        background: #f1f3f4;
        transform: translateX(5px);
    }
    
    .form-section h3 {
        color: #2c3e50;
        margin-bottom: 25px;
        font-size: 1.4rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-section h3::before {
        content: '🎨';
        font-size: 1.2rem;
    }
    
    .form-section:nth-child(1) h3::before { content: '📦'; }
    .form-section:nth-child(3) h3::before { content: '🖼️'; }
    .form-section:nth-child(4) h3::before { content: '📝'; }
    .form-section:nth-child(5) h3::before { content: '💰'; }
    
    .form-section label {
        display: block;
        margin: 20px 0 8px 0;
        font-weight: 600;
        color: #34495e;
        font-size: 1.1rem;
    }
    
    .form-section input,
    .form-section select,
    .form-section textarea {
        width: 100%;
        max-width: 500px;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: white;
    }
    
    .form-section input:focus,
    .form-section select:focus,
    .form-section textarea:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        outline: none;
    }
    
    .form-section input[type="color"] {
        width: 80px;
        height: 50px;
        padding: 5px;
        border-radius: 10px;
        cursor: pointer;
    }
    
    .form-section input[type="checkbox"] {
        width: 20px;
        height: 20px;
        margin-right: 15px;
        accent-color: #667eea;
    }
    
    .form-section input[type="file"] {
        padding: 20px;
        border: 2px dashed #667eea;
        background: #f8f9ff;
        text-align: center;
        cursor: pointer;
    }
    
    .form-section small {
        color: #7f8c8d;
        font-style: italic;
        margin-top: 8px;
        display: block;
    }
    
    .price-breakdown {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(102,126,234,0.3);
    }
    
    .price-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    
    .price-item:last-child {
        border-bottom: none;
    }
    
    .price-total {
        border-top: 2px solid rgba(255,255,255,0.3);
        padding-top: 20px;
        margin-top: 20px;
        font-size: 1.3rem;
        font-weight: bold;
    }
    
    .submit-btn {
        background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        color: white;
        padding: 18px 40px;
        border: none;
        border-radius: 50px;
        font-size: 1.2rem;
        font-weight: bold;
        cursor: pointer;
        width: 100%;
        margin-top: 30px;
        transition: all 0.3s ease;
        box-shadow: 0 8px 20px rgba(255,107,107,0.3);
    }
    
    .submit-btn:hover {
        background: linear-gradient(135deg, #ee5a52, #ff6b6b);
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(255,107,107,0.4);
    }
    
    .color-inputs {
        display: flex;
        gap: 20px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .color-group {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    
    @media (max-width: 768px) {
        .content {
            padding: 15px;
        }
        
        .content h1 {
            font-size: 2rem;
        }
        
        .customize-form {
            padding: 25px;
        }
        
        .form-section {
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .form-section input,
        .form-section select,
        .form-section textarea {
            max-width: 100%;
        }
        
        .color-inputs {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .price-breakdown {
            padding: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .content h1 {
            font-size: 1.5rem;
        }
        
        .customize-form {
            padding: 15px;
        }
        
        .form-section {
            padding: 15px;
        }
        
        .submit-btn {
            padding: 15px 30px;
            font-size: 1rem;
        }
    }
    </style>
    
    <script>
        let basePrice = 0;
        
        document.querySelector('select[name="product_id"]').addEventListener('change', function() {
            basePrice = parseFloat(this.options[this.selectedIndex].dataset.price) || 0;
            calculatePrice();
        });
        
        function calculatePrice() {
            let total = basePrice;
            let sizePrice = 0;
            let premiumPrice = 0;
            let textPrice = 0;
            
            // Size upgrade cost
            const sizeSelect = document.querySelector('select[name="customization[size]"]');
            if (sizeSelect) {
                sizePrice = parseFloat(sizeSelect.options[sizeSelect.selectedIndex].dataset.price) || 0;
                total += sizePrice;
            }
            
            // Premium design cost
            const premiumCheck = document.querySelector('input[name="customization[premium]"]');
            if (premiumCheck && premiumCheck.checked) {
                premiumPrice = parseFloat(premiumCheck.dataset.price) || 0;
                total += premiumPrice;
            }
            
            // Custom text cost
            const textInput = document.querySelector('input[name="customization[custom_text]"]');
            if (textInput && textInput.value.trim()) {
                textPrice = 10; // $10 for custom text
                total += textPrice;
            }
            
            // Update display
            document.getElementById('base-price').textContent = '$' + basePrice.toFixed(2);
            document.getElementById('size-price').textContent = '$' + sizePrice.toFixed(2);
            document.getElementById('premium-price').textContent = '$' + premiumPrice.toFixed(2);
            document.getElementById('text-price').textContent = '$' + textPrice.toFixed(2);
            document.getElementById('total-price').innerHTML = '<strong>$' + total.toFixed(2) + '</strong>';
            document.getElementById('total-price-input').value = total.toFixed(2);
        }
        
        // Initialize price calculation
        calculatePrice();
    </script>
</body>
</html>