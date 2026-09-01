<?php
function calculateOrderPrice($product_id, $customization_details, $db) {
    // Get base product price
    $query = "SELECT base_price FROM products WHERE id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        return 0;
    }
    
    $total_price = $product['base_price'];
    $customization = json_decode($customization_details, true);
    
    if ($customization) {
        // Size upgrade pricing
        if (isset($customization['size'])) {
            switch ($customization['size']) {
                case 'L':
                    $total_price += 5;
                    break;
                case 'XL':
                    $total_price += 10;
                    break;
            }
        }
        
        // Premium design addon
        if (isset($customization['premium']) && $customization['premium'] == '1') {
            $total_price += 25;
        }
        
        // Custom text addon
        if (isset($customization['custom_text']) && !empty(trim($customization['custom_text']))) {
            $total_price += 10;
        }
    }
    
    return $total_price;
}

function getPriceBreakdown($product_id, $customization_details, $db) {
    $query = "SELECT base_price FROM products WHERE id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $breakdown = [
        'base_price' => $product['base_price'] ?? 0,
        'size_price' => 0,
        'premium_price' => 0,
        'text_price' => 0,
        'total_price' => $product['base_price'] ?? 0
    ];
    
    $customization = json_decode($customization_details, true);
    
    if ($customization) {
        // Size pricing
        if (isset($customization['size'])) {
            switch ($customization['size']) {
                case 'L':
                    $breakdown['size_price'] = 5;
                    break;
                case 'XL':
                    $breakdown['size_price'] = 10;
                    break;
            }
        }
        
        // Premium design
        if (isset($customization['premium']) && $customization['premium'] == '1') {
            $breakdown['premium_price'] = 25;
        }
        
        // Custom text
        if (isset($customization['custom_text']) && !empty(trim($customization['custom_text']))) {
            $breakdown['text_price'] = 10;
        }
        
        $breakdown['total_price'] = $breakdown['base_price'] + $breakdown['size_price'] + 
                                   $breakdown['premium_price'] + $breakdown['text_price'];
    }
    
    return $breakdown;
}
?>