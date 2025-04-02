<?php
require_once 'includes/header.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = getDBConnection();
?>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <?php if (hasPermission('manager') || hasPermission('admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/staff/">
                            <i class="fas fa-users"></i> Staff Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/customers/">
                            <i class="fas fa-user-friends"></i> Customers
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/orders/">
                            <i class="fas fa-clipboard-list"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/menu/">
                            <i class="fas fa-utensils"></i> Menu
                        </a>
                    </li>
                    <?php if (hasPermission('manager') || hasPermission('admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/inventory/">
                            <i class="fas fa-boxes"></i> Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/suppliers/">
                            <i class="fas fa-truck"></i> Suppliers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/accounting/">
                            <i class="fas fa-calculator"></i> Accounting
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/maintenance/">
                            <i class="fas fa-tools"></i> Maintenance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports/">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                        <span data-feather="calendar"></span>
                        This week
                    </button>
                </div>
            </div>

            <!-- Dashboard cards -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-primary h-100">
                        <div class="card-body">
                            <h5 class="card-title">Today's Orders</h5>
                            <?php
                            $today = date('Y-m-d');
                            $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = ?");
                            $stmt->bind_param("s", $today);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $count = $result->fetch_assoc()['count'];
                            ?>
                            <h2 class="card-text"><?php echo $count; ?></h2>
                            <a href="modules/orders/" class="text-white">View all orders</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-success h-100">
                        <div class="card-body">
                            <h5 class="card-title">Today's Revenue</h5>
                            <?php
                            $stmt = $db->prepare("SELECT SUM(final_amount) as total FROM orders WHERE DATE(order_date) = ? AND status = 'completed'");
                            $stmt->bind_param("s", $today);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $total = $result->fetch_assoc()['total'] ?? 0;
                            ?>
                            <h2 class="card-text">$<?php echo number_format($total, 2); ?></h2>
                            <a href="modules/accounting/" class="text-white">View financial reports</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-warning h-100">
                        <div class="card-body">
                            <h5 class="card-title">Items to Reorder</h5>
                            <?php
                            $stmt = $db->prepare("SELECT COUNT(*) as count FROM inventory WHERE current_quantity <= reorder_level");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $count = $result->fetch_assoc()['count'];
                            ?>
                            <h2 class="card-text"><?php echo $count; ?></h2>
                            <a href="modules/inventory/" class="text-white">Manage inventory</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent orders -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Table</th>
                                    <th>Customer</th>
                                    <th>Waiter</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $db->prepare("
                                    SELECT o.order_id, o.order_date, o.status, o.final_amount, 
                                           t.table_number, c.name as customer_name, u.full_name as waiter_name
                                    FROM orders o
                                    LEFT JOIN restaurant_tables t ON o.table_id = t.table_id
                                    LEFT JOIN customers c ON o.customer_id = c.customer_id
                                    LEFT JOIN users u ON o.waiter_id = u.user_id
                                    ORDER BY o.order_date DESC LIMIT 10
                                ");
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                while ($order = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td><?php echo $order['table_number'] ?? 'Takeout'; ?></td>
                                    <td><?php echo $order['customer_name'] ?? 'Walk-in'; ?></td>
                                    <td><?php echo $order['waiter_name']; ?></td>
                                    <td>$<?php echo number_format($order['final_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($order['status']) {
                                                case 'completed': echo 'success'; break;
                                                case 'cancelled': echo 'danger'; break;
                                                case 'pending': echo 'warning'; break;
                                                default: echo 'info';
                                            }
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, g:i a', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <a href="modules/orders/view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>