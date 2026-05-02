-- Adminability v3.7: Brainstorm list + Procedures

-- Shared quick brainstorm / to-do list (no project, no priority)
CREATE TABLE IF NOT EXISTS brainstorm_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    text TEXT NOT NULL,
    is_done INTEGER DEFAULT 0,
    sort_order INTEGER DEFAULT 0,
    created_by INTEGER REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Procedure subjects (reusable categories, e.g. "YouTube workflow", "Email marketing")
CREATE TABLE IF NOT EXISTS procedure_subjects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    color TEXT DEFAULT 'indigo',
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);

-- Procedures
CREATE TABLE IF NOT EXISTS procedures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    subject_id INTEGER REFERENCES procedure_subjects(id) ON DELETE SET NULL,
    project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL,
    sort_order INTEGER DEFAULT 0,
    created_by INTEGER REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_procedures_subject ON procedures(subject_id);
CREATE INDEX IF NOT EXISTS idx_procedures_project ON procedures(project_id);

-- Procedure steps (ordered list per procedure)
CREATE TABLE IF NOT EXISTS procedure_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    procedure_id INTEGER NOT NULL REFERENCES procedures(id) ON DELETE CASCADE,
    text TEXT NOT NULL,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_procedure_steps_proc ON procedure_steps(procedure_id);
