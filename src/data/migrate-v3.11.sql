-- Adminability v3.11: Upskilling
-- Shared list of learning links (YouTube videos, articles, etc.) to watch/read later.

CREATE TABLE IF NOT EXISTS upskilling_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    url TEXT NOT NULL,
    title TEXT,
    notes TEXT,
    youtube_id TEXT,
    assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
    status TEXT DEFAULT 'unwatched' CHECK(status IN ('unwatched','watching','watched')),
    sort_order INTEGER DEFAULT 0,
    created_by INTEGER NOT NULL REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_upskilling_status ON upskilling_items(status);
CREATE INDEX IF NOT EXISTS idx_upskilling_assigned ON upskilling_items(assigned_to);
