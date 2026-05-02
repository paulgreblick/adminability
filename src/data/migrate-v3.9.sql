-- Adminability v3.9: Brainstorm items — per-item person assignment
-- Nullable: NULL = "unassigned / anyone".

ALTER TABLE brainstorm_items ADD COLUMN assigned_to INTEGER REFERENCES users(id);
CREATE INDEX IF NOT EXISTS idx_brainstorm_assigned ON brainstorm_items(assigned_to);
