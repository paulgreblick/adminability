-- Adminability v3.10: Brainstorm sub-steps
-- Each brainstorm item can have an ordered list of sub-steps (independent checkboxes).

CREATE TABLE IF NOT EXISTS brainstorm_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    brainstorm_id INTEGER NOT NULL REFERENCES brainstorm_items(id) ON DELETE CASCADE,
    text TEXT NOT NULL,
    is_done INTEGER DEFAULT 0,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_brainstorm_steps_item ON brainstorm_steps(brainstorm_id);
