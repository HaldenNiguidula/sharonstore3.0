-- ============================================================
-- Sharon Store Web-Based Inventory & Sales Forecasting System
-- Database Schema + Seed Data
-- Version 2.0 — Fixed & Complete
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `sharonstore_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `sharonstore_db`;

-- ============================================================
-- TABLE: tbl_users
-- ============================================================
DROP TABLE IF EXISTS `tbl_users`;
CREATE TABLE `tbl_users` (
  `user_id`    INT(11)      NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `full_name`  VARCHAR(100) NOT NULL,
  `role`       ENUM('admin','cashier') NOT NULL DEFAULT 'cashier',
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tbl_categories
-- ============================================================
DROP TABLE IF EXISTS `tbl_categories`;
CREATE TABLE `tbl_categories` (
  `category_id`   INT(11)      NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(100) NOT NULL,
  `description`   TEXT         DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tbl_inventory
-- ============================================================
DROP TABLE IF EXISTS `tbl_inventory`;
CREATE TABLE `tbl_inventory` (
  `item_id`             INT(11)         NOT NULL AUTO_INCREMENT,
  `barcode`             VARCHAR(50)     DEFAULT NULL,
  `item_name`           VARCHAR(150)    NOT NULL,
  `category_id`         INT(11)         NOT NULL,
  `unit`                ENUM('pieces','packs','kg') NOT NULL DEFAULT 'pieces',
  `price`               DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `cost_price`          DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `stock_qty`           DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `low_stock_threshold` INT(11)         NOT NULL DEFAULT 10,
  `expiry_date`         DATE            DEFAULT NULL,
  `is_active`           TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `uq_barcode` (`barcode`),
  KEY `fk_inv_category` (`category_id`),
  CONSTRAINT `fk_inv_category`
    FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`category_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tbl_transactions
-- ============================================================
DROP TABLE IF EXISTS `tbl_transactions`;
CREATE TABLE `tbl_transactions` (
  `transaction_id`   INT(11)       NOT NULL AUTO_INCREMENT,
  `transaction_code` VARCHAR(30)   NOT NULL,
  `user_id`          INT(11)       NOT NULL,
  `total_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_tendered`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `change_amount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`           VARCHAR(20)   NOT NULL DEFAULT 'completed',
  `transaction_date` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `uq_txn_code` (`transaction_code`),
  KEY `fk_txn_user` (`user_id`),
  CONSTRAINT `fk_txn_user`
    FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tbl_transaction_details
-- ============================================================
DROP TABLE IF EXISTS `tbl_transaction_details`;
CREATE TABLE `tbl_transaction_details` (
  `detail_id`      INT(11)       NOT NULL AUTO_INCREMENT,
  `transaction_id` INT(11)       NOT NULL,
  `item_id`        INT(11)       NOT NULL,
  `quantity`       DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit_price`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`detail_id`),
  KEY `fk_det_txn`  (`transaction_id`),
  KEY `fk_det_item` (`item_id`),
  CONSTRAINT `fk_det_txn`
    FOREIGN KEY (`transaction_id`) REFERENCES `tbl_transactions` (`transaction_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_det_item`
    FOREIGN KEY (`item_id`) REFERENCES `tbl_inventory` (`item_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tbl_audit_logs
-- ============================================================
DROP TABLE IF EXISTS `tbl_audit_logs`;
CREATE TABLE `tbl_audit_logs` (
  `log_id`      INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)      DEFAULT NULL,
  `action`      VARCHAR(100) NOT NULL,
  `module`      VARCHAR(50)  NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `log_time`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `fk_log_user` (`user_id`),
  CONSTRAINT `fk_log_user`
    FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tbl_forecasts
-- ============================================================
DROP TABLE IF EXISTS `tbl_forecasts`;
CREATE TABLE `tbl_forecasts` (
  `forecast_id`     INT(11)       NOT NULL AUTO_INCREMENT,
  `item_id`         INT(11)       NOT NULL,
  `forecast_period` DATE          NOT NULL,
  `predicted_qty`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `method`          VARCHAR(10)   NOT NULL DEFAULT 'SMA',
  `generated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`forecast_id`),
  KEY `fk_fc_item` (`item_id`),
  CONSTRAINT `fk_fc_item`
    FOREIGN KEY (`item_id`) REFERENCES `tbl_inventory` (`item_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA: Categories
-- ============================================================
INSERT INTO `tbl_categories` (`category_name`, `description`) VALUES
('Canned Goods',          'Canned and preserved food products'),
('Dry Commodities',       'Rice, sugar, flour and dry staples'),
('Household Essentials',  'Cleaning supplies and household items'),
('Beverages',             'Drinks, juices and water products'),
('Snacks & Confectionery','Chips, crackers and sweet items'),
('Personal Care',         'Hygiene and personal care products'),
('Condiments & Sauces',   'Vinegar, soy sauce, ketchup, etc.');

-- ============================================================
-- SEED DATA: Users
-- Passwords: admin → admin123  |  cashier1/2 → cashier123
-- Generated via: password_hash('admin123', PASSWORD_BCRYPT, ['cost'=>12])
-- ============================================================
INSERT INTO `tbl_users` (`username`, `password`, `full_name`, `role`, `is_active`) VALUES
('admin',
 '$2y$12$OjAYuzFVRjq0FjUKQVj0w.oLsNGMwEKP4LmLLnmrqkBvNX85sOyZu',
 'Sharon Dela Cruz', 'admin', 1),
('cashier1',
 '$2y$12$m4Tl/rD.jvxJGGfg7JcGvesaGEJL3Ee0mhJy0RFx0HrJSuBl7tXaC',
 'Maria Santos', 'cashier', 1),
('cashier2',
 '$2y$12$m4Tl/rD.jvxJGGfg7JcGvesaGEJL3Ee0mhJy0RFx0HrJSuBl7tXaC',
 'Jose Reyes', 'cashier', 1);

-- ============================================================
-- SEED DATA: Inventory (30 items)
-- ============================================================
INSERT INTO `tbl_inventory`
  (`barcode`,`item_name`,`category_id`,`unit`,`price`,`cost_price`,`stock_qty`,`low_stock_threshold`,`expiry_date`)
VALUES
('4800012345001','Ligo Sardines in Tomato Sauce 155g',   1,'pieces', 18.00, 13.00,120,20,'2027-03-15'),
('4800012345002','555 Corned Beef 260g',                  1,'pieces', 65.00, 50.00, 85,15,'2027-06-20'),
('4800012345003','Century Tuna Flakes in Oil 180g',       1,'pieces', 42.00, 32.00, 95,20,'2027-09-10'),
('4800012345004','Argentina Corned Beef 150g',            1,'pieces', 48.00, 37.00, 70,15,'2027-08-05'),
('4800012345005','Del Monte Tomato Sauce 250g',           1,'pieces', 28.00, 20.00, 60,10,'2026-12-31'),
('4800023456001','Sinandomeng Rice 5kg',                  2,'packs', 280.00,240.00, 50,10,'2026-12-01'),
('4800023456002','White Sugar 1kg',                       2,'kg',     65.00, 52.00, 30, 5, NULL),
('4800023456003','All-Purpose Flour 1kg',                 2,'kg',     55.00, 43.00, 25, 5,'2027-01-15'),
('4800023456004','Table Salt 500g',                       2,'packs',  15.00, 10.00, 80,15, NULL),
('4800023456005','Brown Sugar 1kg',                       2,'kg',     72.00, 58.00, 20, 5, NULL),
('4800034567001','Tide Detergent Powder 1kg',             3,'packs',  95.00, 75.00, 45,10,'2028-01-01'),
('4800034567002','Joy Dishwashing Liquid 500ml',          3,'pieces', 85.00, 65.00, 38,10,'2028-06-01'),
('4800034567003','Ariel Detergent 1kg',                   3,'packs', 102.00, 80.00, 40,10,'2028-01-01'),
('4800034567004','Domex Toilet Bowl Cleaner 500ml',       3,'pieces', 78.00, 60.00, 25, 5,'2028-06-01'),
('4800034567005','Mr. Clean Multipurpose 500ml',          3,'pieces', 88.00, 68.00, 22, 5,'2028-06-01'),
('4800045678001','Coca-Cola 1.5L',                        4,'pieces', 65.00, 50.00, 72,15,'2026-10-01'),
('4800045678002','Royal Tru-Orange 1.5L',                 4,'pieces', 60.00, 46.00, 55,15,'2026-10-01'),
('4800045678003','Nestea Iced Tea 1L',                    4,'pieces', 45.00, 34.00, 88,20,'2026-11-30'),
('4800045678004','Absolute Water 500ml',                  4,'pieces', 15.00, 10.00,150,30,'2027-06-01'),
('4800045678005','Milo Powdered Chocolate 400g',          4,'pieces',145.00,115.00, 35, 8,'2027-12-31'),
('4800056789001','Nova Country Cheddar 22g',              5,'pieces', 12.00,  8.00,200,30,'2026-09-15'),
('4800056789002','Piattos Cheese 85g',                    5,'pieces', 38.00, 28.00, 95,20,'2026-10-30'),
('4800056789003','Boy Bawang Cornick 100g',               5,'pieces', 22.00, 15.00,140,25,'2026-11-30'),
('4800056789004','Oishi Marty\'s Crackers 135g',          5,'pieces', 30.00, 22.00, 78,15,'2026-12-31'),
('4800067890001','Palmolive Shampoo 200ml',               6,'pieces', 98.00, 75.00, 42,10,'2028-12-31'),
('4800067890002','Safeguard Soap 135g',                   6,'pieces', 45.00, 33.00, 88,15,'2028-12-31'),
('4800067890003','Colgate Toothpaste 150ml',              6,'pieces', 72.00, 55.00, 65,15,'2028-12-31'),
('4800078901001','Datu Puti Vinegar 1L',                  7,'pieces', 38.00, 28.00, 55,10,'2028-01-01'),
('4800078901002','Silver Swan Soy Sauce 1L',              7,'pieces', 42.00, 32.00, 48,10,'2028-01-01'),
('4800078901003','UFC Banana Ketchup 320g',               7,'pieces', 52.00, 40.00, 38, 8,'2027-06-30');

-- ============================================================
-- SEED DATA: Sample Transactions (6 months historical data)
-- This stored procedure generates 200 realistic transactions
-- for testing the BI dashboard and sales forecasting.
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_GenerateSampleData`;

DELIMITER $$
CREATE PROCEDURE `sp_GenerateSampleData`()
BEGIN
  DECLARE i         INT DEFAULT 1;
  DECLARE txn_date  DATE;
  DECLARE txn_code  VARCHAR(30);
  DECLARE txn_id    INT;
  DECLARE uid       INT;
  DECLARE n_items   INT;
  DECLARE j         INT;
  DECLARE iid       INT;
  DECLARE qty       DECIMAL(10,2);
  DECLARE iprice    DECIMAL(10,2);
  DECLARE total     DECIMAL(10,2);
  DECLARE tendered  DECIMAL(10,2);
  DECLARE day_seq   INT;

  WHILE i <= 200 DO
    SET txn_date  = DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 180) DAY);
    SET day_seq   = (SELECT COUNT(*) FROM tbl_transactions WHERE DATE(transaction_date) = txn_date) + 1;
    SET txn_code  = CONCAT('TXN-', DATE_FORMAT(txn_date,'%Y%m%d'), '-', LPAD(day_seq, 4, '0'));
    SET uid       = IF(RAND() > 0.5, 2, 3);
    SET total     = 0.00;

    -- Insert transaction header
    INSERT INTO `tbl_transactions`
      (`transaction_code`,`user_id`,`total_amount`,`amount_tendered`,`change_amount`,`status`,`transaction_date`)
    VALUES
      (txn_code, uid, 0.00, 0.00, 0.00, 'completed', txn_date);

    SET txn_id  = LAST_INSERT_ID();
    SET n_items = FLOOR(1 + RAND() * 5);
    SET j       = 1;

    WHILE j <= n_items DO
      SET iid    = FLOOR(1 + RAND() * 30);
      SET qty    = FLOOR(1 + RAND() * 6);
      SELECT price INTO iprice FROM tbl_inventory WHERE item_id = iid LIMIT 1;

      IF iprice IS NOT NULL THEN
        INSERT INTO `tbl_transaction_details`
          (`transaction_id`,`item_id`,`quantity`,`unit_price`,`subtotal`)
        VALUES
          (txn_id, iid, qty, iprice, qty * iprice);
        SET total = total + (qty * iprice);
      END IF;

      SET j = j + 1;
    END WHILE;

    SET tendered = CEIL(total / 50) * 50;
    UPDATE `tbl_transactions`
    SET `total_amount` = total, `amount_tendered` = tendered, `change_amount` = (tendered - total)
    WHERE `transaction_id` = txn_id;

    SET i = i + 1;
  END WHILE;
END$$
DELIMITER ;

CALL `sp_GenerateSampleData`();
DROP PROCEDURE IF EXISTS `sp_GenerateSampleData`;
