-- Database Clear Script (Users Table Preserved)
-- Run this script to clear all data except users table

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Clear tables in correct order (child tables first, then parent tables)
-- This prevents foreign key constraint errors

-- Step 1: Clear child tables first (tables with foreign keys)
TRUNCATE TABLE sale_items;
TRUNCATE TABLE order_items;
TRUNCATE TABLE supplier_bill_items;
TRUNCATE TABLE product_units;
TRUNCATE TABLE unit_conversions;
TRUNCATE TABLE product_history;
TRUNCATE TABLE supplier_transactions;
TRUNCATE TABLE user_activity_logs;

-- Step 2: Clear intermediate tables
TRUNCATE TABLE invoices;
TRUNCATE TABLE sales_returns;
TRUNCATE TABLE sales;
TRUNCATE TABLE orders;
TRUNCATE TABLE supplier_bills;

-- Step 3: Clear main entity tables
TRUNCATE TABLE products;
TRUNCATE TABLE customers;
TRUNCATE TABLE suppliers;
TRUNCATE TABLE categories;
TRUNCATE TABLE units;
TRUNCATE TABLE expenses;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify users table is intact
SELECT COUNT(*) as user_count FROM users;

