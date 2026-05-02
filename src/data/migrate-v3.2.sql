-- Adminability v3.2 migration
-- - Drop all video-related tables
-- - Add project_id to notes and docs
-- - Create Tab Opener tables

-- Drop video tables (in dependency order)
DROP TABLE IF EXISTS video_progress;
DROP TABLE IF EXISTS videos;
DROP TABLE IF EXISTS workflow_steps;
DROP TABLE IF EXISTS video_categories;

-- Add project_id to notes (nullable, ON DELETE SET NULL via the FK in app logic)
-- SQLite doesn't fully enforce FK constraints retroactively but the column works
ALTER TABLE notes ADD COLUMN project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL;
ALTER TABLE docs ADD COLUMN project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_notes_project_id ON notes(project_id);
CREATE INDEX IF NOT EXISTS idx_docs_project_id ON docs(project_id);

-- Tab Opener
CREATE TABLE IF NOT EXISTS tab_sets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    color TEXT DEFAULT 'indigo',
    assigned_to INTEGER REFERENCES users(id),
    created_by INTEGER NOT NULL REFERENCES users(id),
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS tab_set_urls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    set_id INTEGER NOT NULL REFERENCES tab_sets(id) ON DELETE CASCADE,
    url TEXT NOT NULL,
    label TEXT,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_tab_set_urls_set ON tab_set_urls(set_id);
