-- Rollback Migration: Remove wholesale pricing support
-- Run this against the database

-- 1. Remove wholesale price from purchases table
ALTER TABLE `purchases` DROP COLUMN IF EXISTS `price_wholesale_purchase`;

-- 2. Remove unit price and wholesale price from products table  
ALTER TABLE `products` 
  DROP COLUMN IF EXISTS `price_unit_product`,
  DROP COLUMN IF EXISTS `price_wholesale_product`;

-- 3. Remove wholesale flag from sales table
ALTER TABLE `sales` DROP COLUMN IF EXISTS `wholesale_sale`;

-- 4. Remove column entries
DELETE FROM `columns` WHERE `title_column` = 'price_wholesale_purchase' AND `id_module_column` = 41;
DELETE FROM `columns` WHERE `title_column` = 'price_unit_product' AND `id_module_column` = 10;
DELETE FROM `columns` WHERE `title_column` = 'price_wholesale_product' AND `id_module_column` = 10;
DELETE FROM `columns` WHERE `title_column` = 'wholesale_sale' AND `id_module_column` = 16;
