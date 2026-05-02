-- Adminability v3.5: Sub-projects (parent_id on projects)
-- Purely additive — no data loss

ALTER TABLE projects ADD COLUMN parent_id INTEGER REFERENCES projects(id) ON DELETE SET NULL;
CREATE INDEX IF NOT EXISTS idx_projects_parent ON projects(parent_id);
