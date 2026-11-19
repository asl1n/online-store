-- Run once on an existing database (new installs already have this column).
ALTER TABLE products ADD COLUMN discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER price;
