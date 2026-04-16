-- Phase 1 database foundation for the university E-Commerce project.
-- Run this file in MySQL before connecting the PHP application.

CREATE DATABASE IF NOT EXISTS ecommerce_university
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ecommerce_university;

-- Users will be used in Phase 3 for secure registration and login.
-- password_hash stores the output of PHP password_hash(), never a plain password.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categories keep product navigation normalized and easier to filter later.
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products are separated from categories using a foreign key.
-- Prices use DECIMAL, not FLOAT, to avoid rounding errors in money values.
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    discount_percent DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    image_url VARCHAR(255) NOT NULL,
    stock_qty INT UNSIGNED NOT NULL DEFAULT 0,
    seo_keywords VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_title (title),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Orders and order_items will be populated after Stripe checkout in Phase 5.
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    stripe_checkout_session_id VARCHAR(255) NULL UNIQUE,
    stripe_payment_intent_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    price_at_purchase DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Sample seed data supports the static homepage in Phase 1 and dynamic catalog in Phase 2.
INSERT INTO categories (name, slug, description) VALUES
('Electronics', 'electronics', 'Smart devices and useful technology for daily life.'),
('Fashion', 'fashion', 'Comfortable clothing and accessories for modern shoppers.'),
('Home Essentials', 'home-essentials', 'Reliable home products for study, work, and living.')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO products (category_id, title, slug, description, price, image_url, stock_qty, seo_keywords)
SELECT id, 'Wireless Study Headphones', 'wireless-study-headphones', 'Noise-reducing headphones designed for focused study sessions.', 59.99, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=80', 25, 'headphones, audio, study'
FROM categories WHERE slug = 'electronics'
ON DUPLICATE KEY UPDATE
    slug = VALUES(slug),
    description = VALUES(description),
    price = VALUES(price),
    image_url = VALUES(image_url),
    stock_qty = VALUES(stock_qty),
    seo_keywords = VALUES(seo_keywords);


INSERT INTO products (category_id, title, slug, description, price, image_url, stock_qty, seo_keywords)
SELECT id, 'Campus Backpack', 'campus-backpack', 'Durable backpack with organized laptop and book storage.', 44.50, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=900&q=80', 40, 'backpack, student, campus'
FROM categories WHERE slug = 'fashion'
ON DUPLICATE KEY UPDATE
    slug = VALUES(slug),
    description = VALUES(description),
    price = VALUES(price),
    image_url = VALUES(image_url),
    stock_qty = VALUES(stock_qty),
    seo_keywords = VALUES(seo_keywords);

INSERT INTO products (category_id, title, slug, description, price, image_url, stock_qty, seo_keywords)
SELECT id, 'Desk Organizer Set', 'desk-organizer-set', 'Minimal organizer set for keeping a clean and productive workspace.', 24.99, 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=900&q=80', 35, 'desk, organizer, productivity'
FROM categories WHERE slug = 'home-essentials'
ON DUPLICATE KEY UPDATE
    slug = VALUES(slug),
    description = VALUES(description),
    price = VALUES(price),
    image_url = VALUES(image_url),
    stock_qty = VALUES(stock_qty),
    seo_keywords = VALUES(seo_keywords);

INSERT INTO products (category_id, title, slug, description, price, image_url, stock_qty, seo_keywords)
SELECT id, 'USB-C Study Hub', 'usb-c-study-hub', 'Compact hub for connecting displays, drives, and class devices.', 34.95, 'https://images.unsplash.com/photo-1625842268584-8f3296236761?auto=format&fit=crop&w=900&q=80', 18, 'usb-c, hub, laptop'
FROM categories WHERE slug = 'electronics'
ON DUPLICATE KEY UPDATE
    slug = VALUES(slug),
    description = VALUES(description),
    price = VALUES(price),
    image_url = VALUES(image_url),
    stock_qty = VALUES(stock_qty),
    seo_keywords = VALUES(seo_keywords);

INSERT INTO products (category_id, title, slug, description, price, image_url, stock_qty, seo_keywords)
SELECT id, 'Everyday Cotton Hoodie', 'everyday-cotton-hoodie', 'Soft hoodie made for cool classrooms and relaxed weekends.', 39.00, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=900&q=80', 30, 'hoodie, cotton, casual'
FROM categories WHERE slug = 'fashion'
ON DUPLICATE KEY UPDATE
    slug = VALUES(slug),
    description = VALUES(description),
    price = VALUES(price),
    image_url = VALUES(image_url),
    stock_qty = VALUES(stock_qty),
    seo_keywords = VALUES(seo_keywords);

INSERT INTO products (category_id, title, slug, description, price, image_url, stock_qty, seo_keywords)
SELECT id, 'LED Desk Lamp', 'led-desk-lamp', 'Adjustable lamp with warm and cool light modes for reading.', 29.75, 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=900&q=80', 22, 'lamp, desk, reading'
FROM categories WHERE slug = 'home-essentials'
ON DUPLICATE KEY UPDATE
    slug = VALUES(slug),
    description = VALUES(description),
    price = VALUES(price),
    image_url = VALUES(image_url),
    stock_qty = VALUES(stock_qty),
    seo_keywords = VALUES(seo_keywords);

CREATE TABLE IF NOT EXISTS newsletter_queue (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL,
    status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
    source VARCHAR(80) NOT NULL DEFAULT 'website',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    UNIQUE KEY uq_newsletter_queue_email (email)
) ENGINE=InnoDB;

