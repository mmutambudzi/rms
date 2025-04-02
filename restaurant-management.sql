-- Database creation
CREATE DATABASE IF NOT EXISTS restaurant_management;
USE restaurant_management;

-- Users/Staff table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    role ENUM('manager', 'cashier', 'waiter', 'bartender', 'barista', 'chef', 'admin') NOT NULL,
    hire_date DATE NOT NULL,
    salary DECIMAL(10,2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Customers table
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    loyalty_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Suppliers table
CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    tax_id VARCHAR(50),
    payment_terms VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory categories
CREATE TABLE inventory_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Inventory items
CREATE TABLE inventory (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    unit VARCHAR(20) NOT NULL,
    current_quantity DECIMAL(10,3) DEFAULT 0,
    reorder_level DECIMAL(10,3) DEFAULT 0,
    cost_per_unit DECIMAL(10,2) NOT NULL,
    supplier_id INT,
    barcode VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES inventory_categories(category_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
);

-- Inventory transactions
CREATE TABLE inventory_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    transaction_type ENUM('purchase', 'consumption', 'adjustment', 'waste') NOT NULL,
    reference_id INT,
    reference_type VARCHAR(50),
    notes TEXT,
    user_id INT NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory(item_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Menu categories
CREATE TABLE menu_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    display_order INT DEFAULT 0
);

-- Menu items
CREATE TABLE menu_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    price DECIMAL(10,2) NOT NULL,
    cost DECIMAL(10,2),
    is_active BOOLEAN DEFAULT TRUE,
    preparation_time INT, -- in minutes
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES menu_categories(category_id)
);

-- Recipe/Ingredients for menu items
CREATE TABLE recipe_items (
    recipe_id INT AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT NOT NULL,
    inventory_item_id INT NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    notes TEXT,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id),
    FOREIGN KEY (inventory_item_id) REFERENCES inventory(item_id)
);

-- Tables in the restaurant
CREATE TABLE restaurant_tables (
    table_id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(20) NOT NULL UNIQUE,
    capacity INT NOT NULL,
    location VARCHAR(100),
    status ENUM('available', 'occupied', 'reserved', 'out_of_service') DEFAULT 'available'
);

-- Orders
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT,
    customer_id INT,
    waiter_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'preparing', 'ready', 'served', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    total_amount DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(table_id),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (waiter_id) REFERENCES users(user_id)
);

-- Order items
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    special_instructions TEXT,
    status ENUM('pending', 'preparing', 'ready', 'served', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id)
);

-- Payments
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'mobile_payment', 'voucher') NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_reference VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    user_id INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Invoices
CREATE TABLE invoices (
    invoice_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    order_id INT,
    customer_id INT,
    issue_date DATE NOT NULL,
    due_date DATE,
    total_amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Accounting transactions
CREATE TABLE accounting_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    account_type ENUM('income', 'expense', 'asset', 'liability', 'equity') NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    reference_id INT,
    reference_type VARCHAR(50),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Maintenance requests
CREATE TABLE maintenance_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'completed', 'cancelled') DEFAULT 'open',
    assigned_to INT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Sample data population
INSERT INTO users (username, password, full_name, email, phone, role, hire_date, salary) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@restaurant.com', '1234567890', 'admin', '2020-01-01', 5000.00),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager User', 'manager@restaurant.com', '1234567891', 'manager', '2020-01-01', 4000.00),
('cashier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cashier One', 'cashier1@restaurant.com', '1234567892', 'cashier', '2020-01-01', 2500.00),
('waiter1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Waiter One', 'waiter1@restaurant.com', '1234567893', 'waiter', '2020-01-01', 2000.00);

INSERT INTO inventory_categories (name, description) VALUES 
('Vegetables', 'Fresh vegetables'),
('Meat', 'Various meat products'),
('Dairy', 'Milk, cheese, and other dairy products'),
('Beverages', 'Drinks and beverages'),
('Dry Goods', 'Non-perishable food items');

INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES 
('Fresh Foods Inc.', 'John Supplier', 'john@freshfoods.com', '5551234567', '123 Supplier St, Supplier City'),
('Beverage Distributors', 'Sarah Johnson', 'sarah@beveragedist.com', '5552345678', '456 Distribution Ave, Beverage Town');

INSERT INTO inventory (name, description, category_id, unit, current_quantity, reorder_level, cost_per_unit, supplier_id) VALUES 
('Tomatoes', 'Fresh red tomatoes', 1, 'kg', 50, 10, 1.20, 1),
('Chicken Breast', 'Boneless chicken breast', 2, 'kg', 30, 5, 5.50, 1),
('Milk', 'Whole milk 1L', 3, 'unit', 100, 20, 0.80, 1),
('Cola', 'Carbonated drink 330ml', 4, 'unit', 200, 50, 0.50, 2);

INSERT INTO menu_categories (name, description, display_order) VALUES 
('Starters', 'Appetizers and small dishes', 1),
('Main Courses', 'Main dishes', 2),
('Desserts', 'Sweet treats', 3),
('Drinks', 'Beverages', 4);

INSERT INTO menu_items (name, description, category_id, price, cost, is_active, preparation_time) VALUES 
('Caesar Salad', 'Classic Caesar salad with croutons and dressing', 1, 8.99, 2.50, TRUE, 10),
('Grilled Chicken', 'Grilled chicken breast with vegetables', 2, 14.99, 5.00, TRUE, 20),
('Chocolate Cake', 'Rich chocolate cake with ice cream', 3, 6.99, 2.00, TRUE, 5),
('Cola', 'Carbonated soft drink', 4, 2.50, 0.60, TRUE, 1);

INSERT INTO recipe_items (menu_item_id, inventory_item_id, quantity, unit) VALUES 
(1, 1, 0.2, 'kg'), -- Caesar Salad uses tomatoes
(2, 2, 0.3, 'kg'), -- Grilled Chicken uses chicken breast
(4, 4, 1, 'unit'); -- Cola uses cola from inventory

INSERT INTO restaurant_tables (table_number, capacity, location) VALUES 
('T1', 4, 'Main Hall'),
('T2', 4, 'Main Hall'),
('T3', 6, 'Terrace'),
('T4', 2, 'Bar Area');

INSERT INTO customers (name, email, phone) VALUES 
('Regular Customer', 'customer@example.com', '5559876543'),
('Walk-in Customer', NULL, NULL);