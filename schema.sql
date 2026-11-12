CREATE DATABASE IF NOT EXISTS hani_software CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hani_software;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
 ('usd_rate','3000')
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  display_name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NULL UNIQUE,
  email VARCHAR(190) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  wallet_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CHECK (phone IS NOT NULL OR email IS NOT NULL)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  category VARCHAR(100) NOT NULL,
  name_ar VARCHAR(190) NOT NULL,
  name_en VARCHAR(190) NOT NULL,
  description_ar TEXT NULL,
  description_en TEXT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  currency ENUM('USD','SDG') NOT NULL DEFAULT 'USD',
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  customer_note TEXT NULL,
  price_at_order DECIMAL(12,2) NOT NULL,
  currency_at_order ENUM('USD','SDG') NOT NULL,
  usd_rate_at_order DECIMAL(12,2) NOT NULL,
  price_sdg_at_order DECIMAL(12,2) NOT NULL,
  status ENUM('new','processing','completed','rejected') NOT NULL DEFAULT 'new',
  admin_reply TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_orders_service FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB;

-- Create the first administrator securely using the one-time setup script described in README_AR.md.


CREATE TABLE IF NOT EXISTS wallet_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NULL,
  topup_id BIGINT UNSIGNED NULL,
  transaction_type ENUM('topup','service_charge','refund','admin_adjustment') NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  balance_after DECIMAL(14,2) NOT NULL,
  description VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_wallet_order FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS topup_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  amount_sdg DECIMAL(14,2) NOT NULL,
  target_username VARCHAR(190) NULL,
  target_email VARCHAR(190) NULL,
  payment_reference VARCHAR(190) NULL,
  customer_note TEXT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_reply TEXT NULL,
  processed_by BIGINT UNSIGNED NULL,
  processed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_topup_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_topup_admin FOREIGN KEY (processed_by) REFERENCES users(id)
) ENGINE=InnoDB;
