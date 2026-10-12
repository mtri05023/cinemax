USE cinemax;

ALTER TABLE promotions
    ADD COLUMN IF NOT EXISTS usage_limit INT NOT NULL DEFAULT 0 AFTER min_amount;
