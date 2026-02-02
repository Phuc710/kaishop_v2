-- Migration: Add old_price column to products table
-- Date: 2026-02-02

ALTER TABLE `products` 
ADD COLUMN `old_price` int(11) NOT NULL DEFAULT 0 AFTER `price`;

-- Update existing products to have old_price = 0 if not set
UPDATE `products` SET `old_price` = 0 WHERE `old_price` IS NULL;
