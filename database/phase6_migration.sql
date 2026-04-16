-- Phase 6 migration for databases created before SEO slugs and newsletter queue existed.

USE ecommerce_university;

ALTER TABLE products
    ADD COLUMN slug VARCHAR(200) NULL UNIQUE AFTER title;

UPDATE products SET slug = 'wireless-study-headphones' WHERE title = 'Wireless Study Headphones';
UPDATE products SET slug = 'campus-backpack' WHERE title = 'Campus Backpack';
UPDATE products SET slug = 'desk-organizer-set' WHERE title = 'Desk Organizer Set';
UPDATE products SET slug = 'usb-c-study-hub' WHERE title = 'USB-C Study Hub';
UPDATE products SET slug = 'everyday-cotton-hoodie' WHERE title = 'Everyday Cotton Hoodie';
UPDATE products SET slug = 'led-desk-lamp' WHERE title = 'LED Desk Lamp';

ALTER TABLE products
    MODIFY slug VARCHAR(200) NOT NULL;

CREATE TABLE IF NOT EXISTS newsletter_queue (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL,
    status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
    source VARCHAR(80) NOT NULL DEFAULT 'website',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    UNIQUE KEY uq_newsletter_queue_email (email)
) ENGINE=InnoDB;
