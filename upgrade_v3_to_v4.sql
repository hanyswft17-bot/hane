USE hani_software;
ALTER TABLE users ADD COLUMN wallet_balance DECIMAL(14,2) NOT NULL DEFAULT 0;
ALTER TABLE orders ADD COLUMN admin_reply TEXT NULL;
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
