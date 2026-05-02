-- Adminability v3.3 migration
-- Adds Uptime monitoring table

CREATE TABLE IF NOT EXISTS monitors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    url TEXT NOT NULL,
    last_status TEXT DEFAULT 'unknown' CHECK(last_status IN ('up','down','unknown')),
    last_status_code INTEGER,
    last_response_time_ms INTEGER,
    last_checked_at TEXT,
    last_error TEXT,
    sort_order INTEGER DEFAULT 0,
    created_by INTEGER REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_monitors_status ON monitors(last_status);
