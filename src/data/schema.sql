-- Adminability v3.2 Schema (SQLite)

-- Users
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    first_name TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    last_login TEXT
);

-- Login Attempts (rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL,
    email TEXT,
    attempted_at TEXT DEFAULT (datetime('now'))
);

-- Projects (cross-cutting: link tasks, notes, docs)
CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    color TEXT DEFAULT 'indigo',
    status TEXT DEFAULT 'active' CHECK(status IN ('active','archived')),
    created_by INTEGER REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Tasks
CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL,
    status TEXT DEFAULT 'todo' CHECK(status IN ('todo','in_progress','done')),
    priority TEXT DEFAULT 'normal' CHECK(priority IN ('low','normal','high','urgent')),
    due_date TEXT,
    created_by INTEGER NOT NULL REFERENCES users(id),
    assigned_to INTEGER REFERENCES users(id),
    sort_order INTEGER DEFAULT 0,
    completed_at TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX IF NOT EXISTS idx_tasks_project_id ON tasks(project_id);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status);

-- Notes
CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT NOT NULL,
    type TEXT DEFAULT 'note' CHECK(type IN ('note','idea','task','question')),
    status TEXT DEFAULT 'active' CHECK(status IN ('active','done','archived')),
    priority TEXT DEFAULT 'normal' CHECK(priority IN ('low','normal','high')),
    is_pinned INTEGER DEFAULT 0,
    project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL,
    created_by INTEGER REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_notes_project_id ON notes(project_id);

-- Doc Tags
CREATE TABLE IF NOT EXISTS doc_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    color TEXT DEFAULT 'gray'
);

-- Docs
CREATE TABLE IF NOT EXISTS docs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    content TEXT,
    status TEXT DEFAULT 'published' CHECK(status IN ('draft','published','archived')),
    sort_order INTEGER DEFAULT 0,
    project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_docs_project_id ON docs(project_id);

-- Doc Tag Map (many-to-many)
CREATE TABLE IF NOT EXISTS doc_tag_map (
    doc_id INTEGER NOT NULL REFERENCES docs(id) ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES doc_tags(id) ON DELETE CASCADE,
    PRIMARY KEY (doc_id, tag_id)
);

-- Tab Opener: a "set" is a named bundle of URLs to open all at once
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
