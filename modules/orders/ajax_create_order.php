<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

if (!isLoggedIn() || (!hasPermission('cashier') && !hasPermission('waiter'))) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$db = getDBConnection();

// Get POST data
$tableId = intval($_POST['table_id']);
$customerId = isset($_POST['customer_id']) && $_POST['customer_id'] ? intval($_POST['customer_id']) : null;
$notes = sanitizeInput($_POST['notes']);
$waiterId = intval($_POST['waiter_id']);
$items = json_decode($_POST['items'], true);

// Start transaction
$db->begin_transaction();

try {
    // Create order
    $stmt = $db->prepare("INSERT INTO orders (table_id, customer_id, waiter_id, notes, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiis", $tableId, $customerId, $waiterId, $notes);
    $stmt->execute();
    $orderId = $db->insert_id;
    
    // Calculate total amount
    $totalAmount = 0;
    
    // Add order items
    foreach ($items as $item) {
        // Get menu item price
        $menuItemStmt = $db->prepare("SELECT price FROM menu_items WHERE item_id = ?");
        $menuItemStmt->bind_param("i", $item['menu_item_id']);
        $menuItemStmt->execute();
        $menuItemResult = $menuItemStmt->get_result();
        $menuItem = $menuItemResult->fetch_assoc();
        
        if (!$menuItem) {
            throw new Exception("Menu item not found");
        }
        
        $price = $menuItem['price'];
        $quantity = intval($item['quantity']);
        $specialInstructions = sanitizeInput($item['special_instructions']);
        
        $totalAmount += $price * $quantity;
        
        // Insert order item
        $itemStmt = $db->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, special_instructions) VALUES (?, ?, ?, ?, ?)");
        $itemStmt->bind_param("iiids", $orderId, $item['menu_item_id'], $quantity, $price, $specialInstructions);
        $itemStmt->execute();
        
        // Update inventory (if recipe items exist)
        $recipeStmt = $db->prepare("SELECT inventory_item_id, quantity FROM recipe_items WHERE menu_item_id = ?");
        $recipeStmt->bind_param("i", $item['menu_item_id']);
        $recipeStmt->execute();
        $recipeResult = $recipeStmt->get_result();
        
        while ($recipeItem = $recipeResult->fetch_assoc()) {
            $consumedQuantity = $recipeItem['quantity'] * $quantity;
            
            // Update inventory quantity
            $updateStmt = $db->prepare("UPDATE inventory SET current_quantity = current_quantity - ? WHERE item_id = ?");
            $updateStmt->bind_param("di", $consumedQuantity, $recipeItem['inventory_item_id']);
            $updateStmt->execute();
            
            // Record inventory transaction
            $transStmt = $db->prepare("INSERT INTO inventory_transactions (item_id, quantity, transaction_type, reference_id, reference_type, user_id) VALUES (?, ?, 'consumption', ?, 'order', ?)");
            $transStmt->bind_param("idii", $recipeItem['inventory_item_id'], $consumedQuantity, $orderId, $waiterId);
            $transStmt->execute();
        }
    }
    
    // Update order total
    $updateOrderStmt = $db->prepare("UPDATE orders SET total_amount = ?, final_amount = ? WHERE order_id = ?");
    $updateOrderStmt->bind_param("ddi", $totalAmount, $totalAmount, $orderId);
    $updateOrderStmt->execute();
    
    // Update table status
    $updateTableStmt = $db->prepare("UPDATE restaurant_tables SET status = 'occupied' WHERE table_id = ?");
    $updateTableStmt->bind_param("i", $tableId);
    $updateTableStmt->execute();
    
    // Commit transaction
    $db->commit();
    
    echo json_encode(['success' => true, 'order_id' => $orderId]);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>