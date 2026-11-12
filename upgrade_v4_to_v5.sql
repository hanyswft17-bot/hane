USE hani_software;
ALTER TABLE topup_requests ADD COLUMN target_username VARCHAR(190) NULL AFTER amount_sdg;
ALTER TABLE topup_requests ADD COLUMN target_email VARCHAR(190) NULL AFTER target_username;
