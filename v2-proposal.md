# Adminability v2 Proposal

## The Problem with v1

After reviewing the entire codebase, here's what's not pulling its weight:

1. **Dashboard is empty** - Mostly placeholder "Add Project" cards. Not useful as a landing page.
2. **Sidebar has dead sections** - "Tracking: Coming soon" has been there since launch.
3. **RBAC is over-engineered for 2 people** - Full role/permission system, user management pages. You don't need that complexity.
4. **Notes and Docs overlap** - Both store text. The distinction between a "note" and a "doc" is fuzzy.
5. **Video tracker is too rigid** - Hardcoded to one specific workflow. Can't easily adapt if your process changes.
6. **No "what's next?" view** - You open the app and have to dig to find what needs doing.

---

## v2 Vision: A Shared Command Center

Two people. One YouTube channel. The app should answer one question when you open it: **"What do we need to do?"**

---

## Proposed Pages

### 1. Dashboard (Home)

What you see when you log in. No placeholder cards. Real information.

```
┌─────────────────────────────────────────────────┐
│  Good morning, Paul                             │
│                                                 │
│  IN PROGRESS (3)              UP NEXT (2)       │
│  ┌─────────────────────┐     ┌────────────────┐ │
│  │ Self Love #2         │     │ Abundance #3   │ │
│  │ ◐ Audio Recording    │     │ Ready to start │ │
│  ├─────────────────────┤     ├────────────────┤ │
│  │ Morning Energy #1    │     │ Healing #1     │ │
│  │ ◐ Video Editing      │     │ Ready to start │ │
│  ├─────────────────────┤     └────────────────┘ │
│  │ Wealth #1            │                       │
│  │ ◐ Thumbnail          │     RECENTLY DONE (5) │
│  └─────────────────────┘     ✓ Money #2         │
│                               ✓ Happiness #1     │
│  PINNED NOTES                 ✓ Peace #3         │
│  ┌─────────────────────┐                        │
│  │ 📌 Channel art specs │                        │
│  │ 📌 Intro music links  │                        │
│  └─────────────────────┘                        │
│                                                 │
│  STATS                                          │
│  12 of 32 videos complete (37%)                 │
│  ████████░░░░░░░░░░░░░                          │
└─────────────────────────────────────────────────┘
```

**Key idea:** The dashboard is auto-generated from your data. No manual curation needed.

---

### 2. Videos (Production Tracker)

Keep this — it's the core of the app. But simplify.

**What stays:**
- Table with phase status circles (Writing, Audio, Video, Publish, Published)
- Click to expand and see/update individual steps
- AJAX status cycling
- Category filter pills
- Add video / add category

**What changes:**
- Remove the separate `video.php` detail page — the expandable row does everything it did, in-context
- Or keep it but make it simpler (just the workflow steps + notes, no sidebar with status badge/details/danger zone)

---

### 3. Notes

Simplify. One flat list. No "projects" grouping (you only have 2 users — you don't need sub-categorization).

```
┌─────────────────────────────────────────────────┐
│  Notes                              [+ New Note]│
│                                                 │
│  Filter: All | Pinned | Ideas | Tasks           │
│                                                 │
│  📌 Channel art specs - 1920x1080, use...       │
│  📌 Intro music links - audiojungle.net/...     │
│  ─────────────────────────────────────          │
│  💡 Try guided meditation format                │
│  ✅ Set up end screens on all videos   [done]   │
│  📝 Research best posting times for YT          │
│  💡 Collab idea: reach out to @mindful...       │
└─────────────────────────────────────────────────┘
```

**What changes:**
- Drop "note_projects" categories — use simple type icons instead (note/idea/task)
- Pinned notes float to top
- Inline editing (click to edit, no modal)
- Tasks can be marked done without opening anything

---

### 4. Docs (Knowledge Base)

Keep the current split-panel layout — it works well. The Quill editor is good.

This is for longer reference material: processes, style guides, how-to docs.

**What changes:**
- Nothing major. This page is solid.
- Maybe add a search/filter text input above the doc list

---

### 5. Remove These Pages

| Page | Why |
|------|-----|
| `users.php` | 2 users. Manage directly in the database if ever needed. |
| `roles.php` | Same. Hardcode the 2 roles or just give both users full access. |
| `video.php` (detail page) | Redundant with the expandable row on videos.php |

**Keep auth.php** — you still need login protection. Just remove the admin UI for managing users/roles.

---

## Proposed Sidebar

```
┌──────────────┐
│  Admin       │
│              │
│  ■ Dashboard │
│  ▶ Videos    │
│  ✎ Notes     │
│  📖 Docs     │
│              │
│              │
│              │
│  Paul        │
│  ☾  🚪       │
└──────────────┘
```

4 items. Clean. No empty sections. No "Coming soon."

---

## SQLite Migration

As part of v2, the database moves from MySQL to SQLite.

### New Schema (simplified)

```sql
-- Users (keep simple - just 2 rows)
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    first_name TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now')),
    last_login TEXT
);

-- Videos
CREATE TABLE video_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    sort_order INTEGER DEFAULT 0
);

CREATE TABLE videos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER REFERENCES video_categories(id),
    title TEXT NOT NULL,
    notes TEXT,
    folder_link TEXT,
    youtube_url TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE workflow_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phase TEXT NOT NULL CHECK(phase IN ('writing','audio','video','publish','final')),
    sort_order INTEGER DEFAULT 0
);

CREATE TABLE video_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    video_id INTEGER NOT NULL REFERENCES videos(id) ON DELETE CASCADE,
    step_id INTEGER NOT NULL REFERENCES workflow_steps(id),
    status TEXT DEFAULT 'not_started' CHECK(status IN ('not_started','in_progress','complete')),
    updated_at TEXT DEFAULT (datetime('now')),
    UNIQUE(video_id, step_id)
);

-- Notes (simplified - no projects table)
CREATE TABLE notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT NOT NULL,
    type TEXT DEFAULT 'note' CHECK(type IN ('note','idea','task')),
    status TEXT DEFAULT 'active' CHECK(status IN ('active','done','archived')),
    priority TEXT DEFAULT 'normal' CHECK(priority IN ('low','normal','high')),
    is_pinned INTEGER DEFAULT 0,
    created_by INTEGER REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Docs
CREATE TABLE doc_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    color TEXT DEFAULT 'gray'
);

CREATE TABLE docs (
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

CREATE TABLE doc_tag_map (
    doc_id INTEGER NOT NULL REFERENCES docs(id) ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES doc_tags(id) ON DELETE CASCADE,
    PRIMARY KEY (doc_id, tag_id)
);

-- Auth (simplified - no roles/permissions tables)
CREATE TABLE login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL,
    email TEXT,
    attempted_at TEXT DEFAULT (datetime('now'))
);
```

### What gets dropped
- `roles` table — hardcode admin access for both users
- `permissions` table — both users get full access
- `role_permissions` table — not needed
- `ip_lockouts` table — simplify rate limiting into login_attempts
- `note_projects` table — notes are flat
- `doc_categories` table — already migrated to tags
- `parent_id` on docs — you weren't using nested docs

### Auth simplification
Instead of RBAC permission checks everywhere, just use `requireLogin()`. Both users can do everything. Remove all `hasPermission()` / `requirePermission()` calls and the permission-gated sidebar links.

---

## Migration Plan

1. Export live MySQL data (you provide the SQL dump)
2. Build SQLite database with new schema
3. Write PHP migration script to import your data
4. Update `db.php` to use SQLite PDO (`sqlite:/path/to/adminability.db`)
5. Update all SQL queries for SQLite compatibility (no ENUM, datetime differences)
6. Rebuild pages with v2 design
7. Test locally
8. Deploy database file + new PHP files

---

## What You Keep

- Tailwind CSS v4 + dark mode
- Session-based auth with login page (TreePlane.php)
- CSRF protection on all forms
- Rate limiting on login
- .htaccess security headers
- FTP deploy tool
- Clean URLs (no .php extensions)

## Summary of Changes

| Area | v1 | v2 |
|------|----|----|
| Database | MySQL (shared hosting) | SQLite (single file) |
| Users | RBAC with roles/permissions | Simple login, both users = admin |
| Dashboard | Placeholder cards | Auto-generated from real data |
| Sidebar | 6 items + empty sections | 4 items, clean |
| Videos | Same | Keep, minor cleanup |
| Notes | Projects + categories + modals | Flat list, inline editing |
| Docs | Split panel + Quill | Keep as-is |
| User mgmt | Full CRUD pages | Removed (manage in DB) |
| Deployment | MySQL migrations via phpMyAdmin | Upload .db file |
