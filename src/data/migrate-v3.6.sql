-- Adminability v3.6: Uptime monitors become URL bundles
-- Each monitor is now a named set containing many URLs (like tab_sets → tab_set_urls)

CREATE TABLE IF NOT EXISTS monitor_urls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    monitor_id INTEGER NOT NULL REFERENCES monitors(id) ON DELETE CASCADE,
    label TEXT,
    url TEXT NOT NULL,
    last_status TEXT DEFAULT 'unknown' CHECK(last_status IN ('up','down','unknown')),
    last_status_code INTEGER,
    last_response_time_ms INTEGER,
    last_checked_at TEXT,
    last_error TEXT,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_monitor_urls_monitor ON monitor_urls(monitor_id);

-- Migrate any pre-existing single-URL monitors into the new table (idempotent).
-- Only inserts when the monitor has no monitor_urls rows yet.
INSERT INTO monitor_urls (monitor_id, url, last_status, last_status_code, last_response_time_ms, last_checked_at, last_error, sort_order, created_at)
SELECT m.id, m.url, m.last_status, m.last_status_code, m.last_response_time_ms, m.last_checked_at, m.last_error, 0, m.created_at
FROM monitors m
WHERE m.url IS NOT NULL
  AND m.url != ''
  AND NOT EXISTS (SELECT 1 FROM monitor_urls mu WHERE mu.monitor_id = m.id);
