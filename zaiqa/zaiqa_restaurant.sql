-- ============================================================
-- zaiqa_restaurant.sql
-- Complete database dump for Zaiqa Restaurant website
-- Import via: mysql -u root -p < zaiqa_restaurant.sql
-- Or use phpMyAdmin → Import → select this file
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Create & select database
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `zaiqa_restaurant`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `zaiqa_restaurant`;

-- ------------------------------------------------------------
-- Drop tables if they exist (clean install order matters)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `gallery`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `admin_users`;

-- ============================================================
-- TABLE: settings
-- ============================================================
CREATE TABLE `settings` (
  `id`         INT           NOT NULL AUTO_INCREMENT,
  `key`        VARCHAR(100)  NOT NULL,
  `value`      TEXT          DEFAULT NULL,
  `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`key`, `value`) VALUES
  ('restaurant_name',  'Zaiqa Restaurant'),
  ('tagline',          'Three Cuisines. One Destination.'),
  ('phone',            '0303 6417714'),
  ('address',          'Gulgasht Colony, Multan, Punjab, Pakistan'),
  ('hours_weekday',    'Mon–Thu: 12:00 PM – 11:00 PM'),
  ('hours_weekend',    'Fri–Sun: 11:00 AM – 12:00 AM'),
  ('hero_video_url',   'https://assets.mixkit.co/videos/preview/mixkit-cooking-a-meat-barbecue-in-a-restaurant-34702-large.mp4'),
  ('google_maps_embed','https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3472.8!2d71.4785!3d30.1968!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sGulgasht+Colony%2C+Multan!5e0!3m2!1sen!2spk!4v1'),
  ('currency',         'Rs'),
  ('delivery_radius',  '10'),
  ('min_order',        '500');

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE `categories` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `slug`       VARCHAR(50)  NOT NULL,
  `name`       VARCHAR(100) NOT NULL,
  `emoji`      VARCHAR(10)  DEFAULT NULL,
  `sort_order` INT          NOT NULL DEFAULT 0,
  `active`     TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`slug`, `name`, `emoji`, `sort_order`) VALUES
  ('desi',    'Desi & Pakistani', '🍛', 1),
  ('fast',    'Fast Food',        '🍔', 2),
  ('chinese', 'Chinese',          '🍜', 3),
  ('drinks',  'Drinks',           '🥤', 4),
  ('dessert', 'Desserts',         '🍮', 5);

-- ============================================================
-- TABLE: menu_items
-- ============================================================
CREATE TABLE `menu_items` (
  `id`           INT            NOT NULL AUTO_INCREMENT,
  `category_id`  INT            NOT NULL,
  `name`         VARCHAR(150)   NOT NULL,
  `description`  TEXT           DEFAULT NULL,
  `price`        DECIMAL(10,2)  NOT NULL,
  `image_url`    VARCHAR(500)   DEFAULT NULL,
  `badge`        VARCHAR(50)    DEFAULT NULL,
  `is_available` TINYINT(1)     NOT NULL DEFAULT 1,
  `is_featured`  TINYINT(1)     NOT NULL DEFAULT 0,
  `sort_order`   INT            NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menu_category` (`category_id`),
  KEY `idx_menu_featured` (`is_featured`),
  KEY `idx_menu_available` (`is_available`),
  CONSTRAINT `fk_menu_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`, `image_url`, `badge`, `is_featured`) VALUES
  -- ── Desi & Pakistani (category 1) ──────────────────────────
  (1, 'Lahori Karahi',       'Slow-cooked mutton in rich tomato masala with ginger & green chilli.',       2500.00, 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=600&q=80', 'spicy',   1),
  (1, 'Seekh Kebab Platter', 'Charcoal-grilled minced beef skewers served with chutney & raita.',          2200.00, 'https://images.unsplash.com/photo-1574894709920-11b28e7367e3?w=600&q=80', 'popular', 1),
  (1, 'Mutton Biryani',      'Fragrant basmati layered with spiced mutton, fried onions & saffron.',       2500.00, 'https://images.unsplash.com/photo-1589302168068-964664d93dc0?w=600&q=80', 'popular', 1),
  (1, 'Nihari',              'Traditional slow-cooked beef shank stew, served with naan & lemon.',         2200.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600&q=80', NULL,      0),
  (1, 'Chicken Handi',       'Tender chicken in creamy handi-style aromatic gravy. Served with naan.',     2000.00, 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=600&q=80', NULL,      0),
  (1, 'Chicken Tikka',       'Marinated chicken chunks grilled in a clay oven (tandoor).',                 2200.00, 'https://images.unsplash.com/photo-1596797038530-2c107229654b?w=600&q=80', 'popular', 0),
  (1, 'Daal Makhni',         'Slow-cooked black lentils in a buttery, spiced tomato base.',                1200.00, 'https://images.unsplash.com/photo-1606491956689-2ea866880c84?w=600&q=80', 'veg',     0),
  (1, 'Fish Curry',          'Fresh river fish in tangy desi masala with mustard seeds.',                  3000.00, 'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?w=600&q=80', 'spicy',   0),
  -- ── Fast Food (category 2) ─────────────────────────────────
  (2, 'Zaiqa Special Burger','Double beef patty, special sauce, cheddar cheese, caramelised onions.',      2500.00, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&q=80', 'popular', 1),
  (2, 'Crispy Chicken Burger','Crunchy fried chicken fillet with coleslaw & chipotle mayo.',               2000.00, 'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?w=600&q=80', NULL,      0),
  (2, 'BBQ Chicken Pizza',   'Smoky BBQ sauce, grilled chicken, mozzarella & jalapeños. 12 inch.',         3200.00, 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&q=80', 'popular', 0),
  (2, 'Loaded Cheese Fries', 'Crispy fries loaded with cheese sauce, beef mince & pickled chilli.',        1500.00, 'https://images.unsplash.com/photo-1555992336-03a23c7b20ee?w=600&q=80', NULL,      0),
  (2, 'Club Sandwich',       'Triple-decker with grilled chicken, egg, lettuce & tomato.',                 1800.00, 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?w=600&q=80', NULL,      0),
  (2, 'Zinger Wrap',         'Spicy crispy chicken wrapped in a toasted tortilla with coleslaw.',          1600.00, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=600&q=80', 'spicy',   0),
  (2, 'Family Meal Deal',    '4 burgers + large fries + 4 drinks — best value for families!',              5000.00, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&q=80', 'value',   1),
  (2, 'Shawarma Roll',       'Juicy grilled chicken shawarma with garlic sauce in a soft roll.',           1500.00, 'https://images.unsplash.com/photo-1609167830220-7164aa360951?w=600&q=80', 'popular', 0),
  -- ── Chinese (category 3) ───────────────────────────────────
  (3, 'Chicken Manchurian',  'Crispy chicken tossed in a tangy, garlicky Manchurian sauce.',               2200.00, 'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=600&q=80', 'popular', 1),
  (3, 'Beef Chilli Dry',     'Wok-tossed beef strips with bell peppers, soy & chilli.',                   2800.00, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600&q=80', 'spicy',   0),
  (3, 'Chicken Fried Rice',  'Wok-fried basmati with egg, vegetables & soy sauce.',                       1800.00, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600&q=80', NULL,      0),
  (3, 'Hakka Noodles',       'Stir-fried noodles with shredded chicken & crunchy vegetables.',             1800.00, 'https://images.unsplash.com/photo-1569050467447-ce54b3bbc37d?w=600&q=80', NULL,      0),
  (3, 'Hot & Sour Soup',     'Classic tangy-spicy broth with tofu, mushrooms & egg ribbons.',              1200.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600&q=80', NULL,      0),
  (3, 'Spring Rolls (6 pcs)','Crispy golden rolls stuffed with spiced chicken & vegetables.',              1200.00, 'https://images.unsplash.com/photo-1525755662778-989d0524087e?w=600&q=80', NULL,      0),
  -- ── Drinks (category 4) ────────────────────────────────────
  (4, 'Mango Lassi',         'Chilled blend of mango pulp, yogurt & a hint of cardamom.',                  400.00, 'https://images.unsplash.com/photo-1541614101331-1a5a3a194e92?w=600&q=80', NULL,      0),
  (4, 'Fresh Lemonade',      'Freshly squeezed lemon with mint, salt, sugar & chilled water.',             350.00, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=600&q=80', NULL,      0),
  (4, 'Rooh Afza Sharbat',   'Classic rose sharbat with milk — a Pakistani summer staple.',                300.00, 'https://images.unsplash.com/photo-1572490122747-3f3c40c1d2e5?w=600&q=80', NULL,      0),
  (4, 'Doodh Pati Chai',     'Strong milky tea brewed with cardamom & cinnamon.',                          200.00, 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=600&q=80', NULL,      0),
  -- ── Desserts (category 5) ──────────────────────────────────
  (5, 'Gajar Halwa',         'Slow-cooked carrot pudding with milk, sugar & cardamom.',                    600.00, 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=600&q=80', 'popular', 0),
  (5, 'Gulab Jamun (4 pcs)', 'Soft fried milk-dough balls soaked in rose sugar syrup.',                    500.00, 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=600&q=80', NULL,      0),
  (5, 'Brownie Sundae',      'Warm chocolate brownie with vanilla ice cream & fudge sauce.',               800.00, 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=600&q=80', 'new',     0),
  (5, 'Kheer',               'Rice pudding with saffron, rose water & crushed almonds.',                   450.00, 'https://images.unsplash.com/photo-1517093157656-b9eccef91cb1?w=600&q=80', NULL,      0);

-- ============================================================
-- TABLE: gallery
-- ============================================================
CREATE TABLE `gallery` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(100) DEFAULT NULL,
  `image_url`  VARCHAR(500) NOT NULL,
  `span_class` VARCHAR(20)  DEFAULT NULL,
  `sort_order` INT          NOT NULL DEFAULT 0,
  `active`     TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_gallery_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gallery` (`title`, `image_url`, `span_class`, `sort_order`) VALUES
  ('Mutton Biryani',  'https://images.unsplash.com/photo-1589302168068-964664d93dc0?w=600&q=80', 'tall', 1),
  ('Special Burger',  'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?w=500&q=80', NULL,   2),
  ('Hakka Noodles',   'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=500&q=80',   NULL,   3),
  ('Lahori Karahi',   'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=800&q=80', 'wide', 4),
  ('BBQ Pizza',       'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=500&q=80', NULL,   5),
  ('Hot & Sour Soup', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=500&q=80',   NULL,   6),
  ('Desserts',        'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=600&q=80', 'tall', 7),
  ('Seekh Kebab',     'https://images.unsplash.com/photo-1574894709920-11b28e7367e3?w=500&q=80', NULL,   8);

-- ============================================================
-- TABLE: reservations
-- ============================================================
CREATE TABLE `reservations` (
  `id`               INT          NOT NULL AUTO_INCREMENT,
  `full_name`        VARCHAR(150) NOT NULL,
  `phone`            VARCHAR(30)  NOT NULL,
  `email`            VARCHAR(150) DEFAULT NULL,
  `reservation_date` DATE         NOT NULL,
  `reservation_time` TIME         NOT NULL,
  `guests`           VARCHAR(30)  NOT NULL,
  `cuisine_pref`     VARCHAR(50)  NOT NULL DEFAULT 'All / Mixed',
  `special_requests` TEXT         DEFAULT NULL,
  `status`           ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_res_date`   (`reservation_date`),
  KEY `idx_res_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: orders
-- ============================================================
CREATE TABLE `orders` (
  `id`               INT            NOT NULL AUTO_INCREMENT,
  `order_ref`        VARCHAR(20)    NOT NULL,
  `customer_name`    VARCHAR(150)   NOT NULL,
  `customer_phone`   VARCHAR(30)    NOT NULL,
  `customer_email`   VARCHAR(150)   DEFAULT NULL,
  `delivery_address` TEXT           DEFAULT NULL,
  `order_type`       ENUM('delivery','takeaway','dine-in') NOT NULL DEFAULT 'delivery',
  `subtotal`         DECIMAL(10,2)  NOT NULL,
  `delivery_fee`     DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `total`            DECIMAL(10,2)  NOT NULL,
  `status`           ENUM('new','confirmed','preparing','ready','delivered','cancelled') NOT NULL DEFAULT 'new',
  `notes`            TEXT           DEFAULT NULL,
  `created_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_ref` (`order_ref`),
  KEY `idx_orders_status`     (`status`),
  KEY `idx_orders_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: order_items
-- ============================================================
CREATE TABLE `order_items` (
  `id`           INT            NOT NULL AUTO_INCREMENT,
  `order_id`     INT            NOT NULL,
  `menu_item_id` INT            DEFAULT NULL,   -- nullable: item may be deleted later
  `item_name`    VARCHAR(150)   NOT NULL,
  `item_price`   DECIMAL(10,2)  NOT NULL,
  `quantity`     INT            NOT NULL DEFAULT 1,
  `subtotal`     DECIMAL(10,2)  NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order` (`order_id`),
  CONSTRAINT `fk_oi_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: reviews
-- ============================================================
CREATE TABLE `reviews` (
  `id`          INT          NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `location`    VARCHAR(100) DEFAULT NULL,
  `rating`      TINYINT      NOT NULL DEFAULT 5,
  `review_text` TEXT         NOT NULL,
  `is_approved` TINYINT(1)   NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_approved` (`is_approved`),
  KEY `idx_reviews_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reviews` (`name`, `location`, `rating`, `review_text`, `is_approved`, `is_featured`) VALUES
  ('Ahmed Raza',   'Multan',   5, 'Best karahi in Multan, hands down. The flavours are incredible and the portion sizes are generous. We come here every week!',                               1, 1),
  ('Sara Khan',    'Gulgasht', 5, 'Zaiqa has everything — desi, fast food, Chinese. Amazing food quality and great service. The biryani and burgers are must-tries!',                        1, 1),
  ('Usman Ali',    'Multan',   5, 'We had a family dinner here and everyone loved it. The Manchurian and seekh kebabs were the highlight. Highly recommended!',                               1, 1),
  ('Fatima Malik', 'Multan',   5, 'The Chicken Handi and Gajar Halwa are to die for. Best family restaurant in Gulgasht — always fresh and hot!',                                           1, 0);

-- ============================================================
-- TABLE: admin_users
-- ============================================================
CREATE TABLE `admin_users` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(80)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name`     VARCHAR(150) DEFAULT NULL,
  `role`          ENUM('superadmin','manager','staff') NOT NULL DEFAULT 'staff',
  `last_login`    TIMESTAMP    NULL DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password for 'admin' account is: zaiqa2024
-- Hash generated with bcrypt (rounds=12), compatible with PHP password_verify()
-- To change the password later, run in phpMyAdmin:
--   UPDATE admin_users SET password_hash = '<new_hash>' WHERE username = 'admin';
-- Get a new hash with: php -r "echo password_hash('NEW_PASSWORD', PASSWORD_BCRYPT);"
INSERT INTO `admin_users` (`username`, `password_hash`, `full_name`, `role`) VALUES
  ('admin', '$2y$12$KrJOSldKeHHsL.JOWSrKWuuypMru0x4ud9PVJb1f6/OK1O1T8CWEe', 'Restaurant Admin', 'superadmin');

-- ============================================================
-- Re-enable FK checks
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF FILE
-- zaiqa_restaurant database ready.
-- Admin login → http://localhost/zaiqa/admin/login.php
--   username : admin
--   password : zaiqa2024
-- ============================================================
