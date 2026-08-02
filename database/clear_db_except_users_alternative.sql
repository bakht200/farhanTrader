-- Alternative Database Clear Script (Using DELETE instead of TRUNCATE)
-- This method works better with foreign key constraints
-- Users Table Preserved

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Clear tables in correct order using DELETE (works better with foreign keys)

-- Step 1: Clear child tables first (tables with foreign keys)
DELETE FROM sale_items;
DELETE FROM order_items;
DELETE FROM supplier_bill_items;
DELETE FROM product_units;
DELETE FROM unit_conversions;
DELETE FROM product_history;
DELETE FROM supplier_transactions;
DELETE FROM user_activity_logs;

-- Step 2: Clear intermediate tables
DELETE FROM invoices;
DELETE FROM sales_returns;
DELETE FROM sales;
DELETE FROM orders;
DELETE FROM supplier_bills;

-- Step 3: Clear main entity tables
DELETE FROM products;
DELETE FROM customers;
DELETE FROM suppliers;
DELETE FROM categories;
DELETE FROM units;
DELETE FROM expenses;

-- Reset auto increment counters (optional but recommended)
ALTER TABLE sale_items AUTO_INCREMENT = 1;
ALTER TABLE order_items AUTO_INCREMENT = 1;
ALTER TABLE supplier_bill_items AUTO_INCREMENT = 1;
ALTER TABLE product_units AUTO_INCREMENT = 1;
ALTER TABLE unit_conversions AUTO_INCREMENT = 1;
ALTER TABLE product_history AUTO_INCREMENT = 1;
ALTER TABLE supplier_transactions AUTO_INCREMENT = 1;
ALTER TABLE user_activity_logs AUTO_INCREMENT = 1;
ALTER TABLE invoices AUTO_INCREMENT = 1;
ALTER TABLE sales_returns AUTO_INCREMENT = 1;
ALTER TABLE sales AUTO_INCREMENT = 1;
ALTER TABLE orders AUTO_INCREMENT = 1;
ALTER TABLE supplier_bills AUTO_INCREMENT = 1;
ALTER TABLE products AUTO_INCREMENT = 1;
ALTER TABLE customers AUTO_INCREMENT = 1;
ALTER TABLE suppliers AUTO_INCREMENT = 1;
ALTER TABLE categories AUTO_INCREMENT = 1;
ALTER TABLE units AUTO_INCREMENT = 1;
ALTER TABLE expenses AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify users table is intact
SELECT COUNT(*) as user_count FROM users;

