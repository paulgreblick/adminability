-- Adminability v3.8: Brainstorm item details
-- Adds optional long-form notes and free-text timing to brainstorm items.
-- Idempotent: wrap ALTERs in a way that tolerates pre-existing columns.

-- SQLite has no "ADD COLUMN IF NOT EXISTS"; the migrate runner auto-detects
-- applied migrations so this file is only executed once. If you run the raw
-- SQL twice, the second run will error on the ALTERs — that's expected.

ALTER TABLE brainstorm_items ADD COLUMN notes TEXT;
ALTER TABLE brainstorm_items ADD COLUMN timing TEXT;
