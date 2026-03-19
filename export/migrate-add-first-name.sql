-- Migration: Add first_name column to users
-- Extracts first name from existing name field

-- Add first_name column
ALTER TABLE users ADD COLUMN first_name VARCHAR(50) AFTER name;

-- Populate from existing name (everything before first space)
UPDATE users SET first_name = SUBSTRING_INDEX(name, ' ', 1);

-- Make it NOT NULL after populating
ALTER TABLE users MODIFY first_name VARCHAR(50) NOT NULL;
