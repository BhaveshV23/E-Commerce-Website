-- Admin dashboard database upgrade.
-- Run this if your products table was created before discount support existed.

USE ecommerce_university;

ALTER TABLE products
    ADD COLUMN discount_percent DECIMAL(5, 2) NOT NULL DEFAULT 0.00 AFTER price;
