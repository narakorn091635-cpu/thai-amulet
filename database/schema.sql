-- ==========================================================================
-- Thai Amulet E-commerce & Real-time Auction Platform Schema
-- File: database/schema.sql
-- Mutation Log: 2026-11-04 - Created complete schema for Modules 1, 2, 3, and 4
-- ==========================================================================

CREATE DATABASE IF NOT EXISTS `thai_amulet_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `thai_amulet_db`;

-- 1. Users Table (Module 4: Authentication & Admin Panel)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories Table (Module 1: Amulet Marketplace)
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Amulets Table (Module 1 Marketplace + Module 3 6-Axis Radar Status Attributes)
CREATE TABLE IF NOT EXISTS `amulets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(180) NOT NULL,
  `temple_name` VARCHAR(150) NOT NULL,
  `monk_name` VARCHAR(150) NOT NULL,
  `year_created` VARCHAR(30) DEFAULT NULL,
  `material` VARCHAR(120) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `stock_qty` INT UNSIGNED NOT NULL DEFAULT 1,
  `listing_type` ENUM('marketplace', 'auction') NOT NULL DEFAULT 'marketplace',
  `image_path` VARCHAR(255) DEFAULT NULL,
  -- Module 3: 6 Sacred Status Attributes (Scale 1 to 10)
  `stat_protection` TINYINT UNSIGNED NOT NULL DEFAULT 8 COMMENT 'พุทธคุณ (Protection/Overall Grace)',
  `stat_kindness` TINYINT UNSIGNED NOT NULL DEFAULT 8 COMMENT 'เมตตามหานิยม (Popularity/Kindness)',
  `stat_charm` TINYINT UNSIGNED NOT NULL DEFAULT 7 COMMENT 'มหาเสน่ห์ (Charm/Attraction)',
  `stat_evasion` TINYINT UNSIGNED NOT NULL DEFAULT 8 COMMENT 'แคล้วคลาด (Evasion/Safety)',
  `stat_invincibility` TINYINT UNSIGNED NOT NULL DEFAULT 7 COMMENT 'คงกระพันชาตรี (Invincibility)',
  `stat_wealth` TINYINT UNSIGNED NOT NULL DEFAULT 8 COMMENT 'โชคลาภ (Wealth/Luck)',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_amulets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  INDEX `idx_amulet_search` (`temple_name`, `monk_name`, `listing_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Auctions Table (Module 2: Real-time Auction with Anti-Snipe)
CREATE TABLE IF NOT EXISTS `auctions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `amulet_id` INT UNSIGNED NOT NULL UNIQUE,
  `start_price` DECIMAL(12, 2) NOT NULL,
  `current_price` DECIMAL(12, 2) NOT NULL,
  `min_increment` DECIMAL(12, 2) NOT NULL DEFAULT 100.00,
  `highest_bidder_id` INT UNSIGNED DEFAULT NULL,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NOT NULL,
  `anti_snipe_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('upcoming', 'active', 'ended', 'cancelled') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_auctions_amulet` FOREIGN KEY (`amulet_id`) REFERENCES `amulets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_auctions_bidder` FOREIGN KEY (`highest_bidder_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_auction_status_end` (`status`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bids Table (Module 2: Bid History)
CREATE TABLE IF NOT EXISTS `bids` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `auction_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `bid_amount` DECIMAL(12, 2) NOT NULL,
  `triggered_anti_snipe` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bids_auction` FOREIGN KEY (`auction_id`) REFERENCES `auctions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bids_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_bids_auction_created` (`auction_id`, `created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Orders Table (Module 1: Shopping Cart, Slip Upload & Order Management)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `total_amount` DECIMAL(12, 2) NOT NULL,
  `slip_image_path` VARCHAR(255) DEFAULT NULL,
  `shipping_address` TEXT NOT NULL,
  `order_status` ENUM('pending_slip_verify', 'preparing_shipment', 'shipped', 'cancelled') NOT NULL DEFAULT 'pending_slip_verify',
  `tracking_number` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `amulet_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12, 2) NOT NULL,
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_amulet` FOREIGN KEY (`amulet_id`) REFERENCES `amulets` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed initial Thai Amulet Categories
INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
  (1, 'พระสมเด็จ (Phra Somdej)', 'phra_somdej', 'พระสมเด็จเนื้อผงพุทธคุณยอดนิยม'),
  (2, 'พระเหรียญ (Monk Coins)', 'phra_rian', 'เหรียญคณาจารย์ยอดนิยมทั่วประเทศ'),
  (3, 'เครื่องรางของขลัง (Sacred Talismans)', 'khrueang_rang', 'ตะกรุด เบี้ยแก้ เขี้ยวเสือ และเครื่องรางมหาอำนาจ');

