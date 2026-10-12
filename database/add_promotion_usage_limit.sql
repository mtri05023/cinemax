ALTER TABLE promotions
    ADD COLUMN usage_limit INT NOT NULL DEFAULT 0 AFTER min_amount;
