<?php
require_once __DIR__ . '/../../includes/header.php';

if (!hasPermission('cashier') && !hasPermission('waiter')) {
    echo '<script>window.location.href = "/";</script>';
    exit;
}

$db = getDBConnection();
?>

<div class="container mt-4">
    <h2>Create New Order</h2>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Select Menu Items</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <select id="menuCategory" class="form-control">
                                <option value="">All Categories</option>
                                <?php
                                $categories = $db->query("SELECT * FROM menu_categories ORDER BY display_order");
                                while ($cat = $categories->fetch_assoc()) {
                                    echo '<option value="'.$cat['category_id'].'">'.$cat['name'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="menuSearch" class="form-control" placeholder="Search menu items...">
                        </div>
                    </div>
                    
                    <div class="row" id="menuItemsContainer">
                        <!-- Menu items will be loaded here via AJAX -->
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Order Summary</h5>
                </div>
                <div class="card-body">
                    <form id="orderForm">
                        <div class="form-group">
                            <label for="tableSelect">Table</label>
                            <select class="form-control" id="tableSelect" required>
                                <option value="">Select Table</option>
                                <?php
                                $tables = $db->query("SELECT * FROM restaurant_tables WHERE status='available'");
                                while ($table = $tables->fetch_assoc()) {
                                    echo '<option value="'.$table['table_id'].'">'.$table['table_number'].' ('.$table['capacity'].' seats)</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="customerSelect">Customer</label>
                            <select class="form-control" id="customerSelect">
                                <option value="">Walk-in Customer</option>
                                <?php
                                $customers = $db->query("SELECT * FROM customers");
                                while ($customer = $customers->fetch_assoc()) {
                                    echo '<option value="'.$customer['customer_id'].'">'.$customer['name'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Order Items</label>
                            <div id="orderItemsList" class="mb-3">
                                <!-- Order items will be added here -->
                                <p class="text-muted">No items added yet</p>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong id="orderTotal">$0.00</strong>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="orderNotes">Special Instructions</label>
                            <textarea class="form-control" id="orderNotes" rows="2"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Place Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Load menu items
    loadMenuItems();
    
    // Filter menu items
    $('#menuCategory, #menuSearch').on('change keyup', function() {
        loadMenuItems();
    });
    
    // Function to load menu items
    function loadMenuItems() {
        const category = $('#menuCategory').val();
        const search = $('#menuSearch').val();
        
        $.ajax({
            url: '/modules/orders/ajax_get_menu_items.php',
            method: 'POST',
            data: {
                category: category,
                search: search
            },
            success: function(response) {
                $('#menuItemsContainer').html(response);
            }
        });
    }
    
    // Handle adding items to order
    $(document).on('click', '.add-to-order', function() {
        const itemId = $(this).data('id');
        const itemName = $(this).data('name');
        const itemPrice = $(this).data('price');
        
        // Check if item already exists in order
        if ($('#orderItem_' + itemId).length) {
            const qtyInput = $('#orderItem_' + itemId).find('.item-qty');
            qtyInput.val(parseInt(qtyInput.val()) + 1);
            updateOrderItem(itemId, parseInt(qtyInput.val()));
        } else {
            const itemHtml = `
                <div class="order-item mb-2 p-2 border rounded" id="orderItem_${itemId}">
                    <div class="d-flex justify-content-between">
                        <span>${itemName}</span>
                        <button type="button" class="btn btn-sm btn-danger remove-item" data-id="${itemId}">&times;</button>
                    </div>
                    <div class="d-flex align-items-center mt-1">
                        <button type="button" class="btn btn-sm btn-secondary decrement" data-id="${itemId}">-</button>
                        <input type="number" class="form-control form-control-sm item-qty mx-2" value="1" min="1" data-id="${itemId}" style="width: 50px;">
                        <button type="button" class="btn btn-sm btn-secondary increment" data-id="${itemId}">+</button>
                        <span class="ml-auto item-price">$${(itemPrice * 1).toFixed(2)}</span>
                    </div>
                    <textarea class="form-control form-control-sm mt-1 special-instructions" placeholder="Special instructions" data-id="${itemId}"></textarea>
                </div>
            `;
            
            if ($('#orderItemsList p.text-muted').length) {
                $('#orderItemsList').html(itemHtml);
            } else {
                $('#orderItemsList').append(itemHtml);
            }
            
            calculateOrderTotal();
        }
    });
    
    // Handle quantity changes
    $(document).on('click', '.increment, .decrement', function() {
        const itemId = $(this).data('id');
        const qtyInput = $('#orderItem_' + itemId).find('.item-qty');
        let newQty = parseInt(qtyInput.val());
        
        if ($(this).hasClass('increment')) {
            newQty++;
        } else {
            if (newQty > 1) newQty--;
        }
        
        qtyInput.val(newQty);
        updateOrderItem(itemId, newQty);
    });
    
    // Handle direct quantity input
    $(document).on('change', '.item-qty', function() {
        const itemId = $(this).data('id');
        const newQty = parseInt($(this).val()) || 1;
        $(this).val(newQty);
        updateOrderItem(itemId, newQty);
    });
    
    // Handle item removal
    $(document).on('click', '.remove-item', function() {
        const itemId = $(this).data('id');
        $('#orderItem_' + itemId).remove();
        
        if ($('#orderItemsList .order-item').length === 0) {
            $('#orderItemsList').html('<p class="text-muted">No items added yet</p>');
        }
        
        calculateOrderTotal();
    });
    
    // Update order item price
    function updateOrderItem(itemId, quantity) {
        const price = $('.add-to-order[data-id="' + itemId + '"]').data('price');
        $('#orderItem_' + itemId).find('.item-price').text('$' + (price * quantity).toFixed(2));
        calculateOrderTotal();
    }
    
    // Calculate order total
    function calculateOrderTotal() {
        let total = 0;
        
        $('.order-item').each(function() {
            const priceText = $(this).find('.item-price').text();
            total += parseFloat(priceText.substring(1));
        });
        
        $('#orderTotal').text('$' + total.toFixed(2));
    }
    
    // Handle form submission
    $('#orderForm').on('submit', function(e) {
        e.preventDefault();
        
        const tableId = $('#tableSelect').val();
        const customerId = $('#customerSelect').val() || null;
        const notes = $('#orderNotes').val();
        
        if (!tableId) {
            alert('Please select a table');
            return;
        }
        
        const orderItems = [];
        $('.order-item').each(function() {
            const itemId = $(this).attr('id').split('_')[1];
            orderItems.push({
                menu_item_id: itemId,
                quantity: parseInt($(this).find('.item-qty').val()),
                special_instructions: $(this).find('.special-instructions').val()
            });
        });
        
        if (orderItems.length === 0) {
            alert('Please add at least one item to the order');
            return;
        }
        
        $.ajax({
            url: '/modules/orders/ajax_create_order.php',
            method: 'POST',
            data: {
                table_id: tableId,
                customer_id: customerId,
                notes: notes,
                items: orderItems,
                waiter_id: <?php echo $_SESSION['user_id']; ?>
            },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    alert('Order created successfully!');
                    window.location.href = '/modules/orders/view_order.php?id=' + result.order_id;
                } else {
                    alert('Error: ' + result.message);
                }
            }
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>