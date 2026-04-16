-- Phase 5 migration for databases created before Stripe checkout fields existed.
-- Run this only if you already created the database in Phases 1-4.
-- If you are starting fresh, database/schema.sql already includes these fields.

USE ecommerce_university;

ALTER TABLE orders DROP FOREIGN KEY fk_orders_user;

ALTER TABLE orders
    MODIFY user_id INT UNSIGNED NULL,
    ADD COLUMN stripe_checkout_session_id VARCHAR(255) NULL UNIQUE,
    ADD COLUMN stripe_payment_intent_id VARCHAR(255) NULL;

ALTER TABLE orders
    ADD CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
