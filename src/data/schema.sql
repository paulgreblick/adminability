-- Adminability v2 Schema (SQLite)

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

-- Video Categories
CREATE TABLE IF NOT EXISTS video_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);

-- Videos
CREATE TABLE IF NOT EXISTS videos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER REFERENCES video_categories(id),
    title TEXT NOT NULL,
    notes TEXT,
    folder_link TEXT,
    youtube_url TEXT,
    published_at TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Workflow Steps
CREATE TABLE IF NOT EXISTS workflow_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phase TEXT NOT NULL CHECK(phase IN ('writing','audio','video','publish','final')),
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);

-- Video Progress
CREATE TABLE IF NOT EXISTS video_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    video_id INTEGER NOT NULL REFERENCES videos(id) ON DELETE CASCADE,
    step_id INTEGER NOT NULL REFERENCES workflow_steps(id),
    status TEXT DEFAULT 'not_started' CHECK(status IN ('not_started','in_progress','complete')),
    updated_at TEXT DEFAULT (datetime('now')),
    UNIQUE(video_id, step_id)
);

-- Notes (simplified - no projects table)
CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT NOT NULL,
    type TEXT DEFAULT 'note' CHECK(type IN ('note','idea','task','question')),
    status TEXT DEFAULT 'active' CHECK(status IN ('active','done','archived')),
    priority TEXT DEFAULT 'normal' CHECK(priority IN ('low','normal','high')),
    is_pinned INTEGER DEFAULT 0,
    created_by INTEGER REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

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
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Doc Tag Map
CREATE TABLE IF NOT EXISTS doc_tag_map (
    doc_id INTEGER NOT NULL REFERENCES docs(id) ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES doc_tags(id) ON DELETE CASCADE,
    PRIMARY KEY (doc_id, tag_id)
);

-- Login Attempts (rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL,
    email TEXT,
    attempted_at TEXT DEFAULT (datetime('now'))
);
