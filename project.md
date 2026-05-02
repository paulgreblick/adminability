# Adminability - Project Documentation

A password-protected admin dashboard for managing projects, tasks, notes, docs, tab bundles, and uptime checks. Built with PHP, SQLite, and Tailwind CSS v4.

**Live URL:** https://adminability.ac
**Local URL:** http://adminability.ac.test:8080
**Hosting:** BigScoots (shared hosting)
**Local Dev:** Homebrew AMP (Apache/MySQL/PHP)

---

## Quick Start

```bash
# Build CSS and copy files to dist/
npm run build

# Production build (minified CSS)
npm run production
```

**Local development:** Edit files in `/src`, run `npm run build`, test at `adminability.ac.test:8080`
**Deploy:** Upload `/dist` to server via FTP deploy tool, then fix permissions: `chmod 644 ~/adminability.ac/*.php ~/adminability.ac/.htaccess`

> **IMPORTANT:** After making ANY changes to files in `/src`, you must run `npm run build` (or `npm run production` for minified CSS) before testing. The `/dist` folder is what gets served locally and deployed.

> **DB note:** `src/data/adminability.db` is excluded from the build — `dist/data/` never receives the DB file. `db.php` falls back to the `src` copy when the `dist` copy is missing, so local dev and source share one canonical DB. On live, the deployed DB file is authoritative.

---

## Project Structure

```
adminability/
├── src/                          # Source files (edit here)
│   ├── assets/
│   │   ├── css/styles.css        # Tailwind source CSS
│   │   ├── js/scripts.js         # Main JavaScript (dark mode, sidebar toggle, toasts)
│   │   └── images/               # Favicons and OG images
│   ├── data/
│   │   ├── adminability.db       # SQLite database file (not deployed)
│   │   ├── schema.sql            # SQLite schema (baseline)
│   │   ├── migrate-v3.sql        # Add projects + tasks
│   │   ├── migrate-v3.2.sql      # Drop videos, add project_id to notes/docs, add tab_sets
│   │   ├── migrate-v3.3.sql      # Add monitors (uptime)
│   │   ├── migrate-v3.4.sql      # Add checklist_items + task_dependencies
│   │   ├── migrate-v3.5.sql      # Add projects.parent_id (sub-projects)
│   │   ├── migrate-v3.6.sql      # Add monitor_urls (uptime bundles)
│   │   ├── migrate-v3.7.sql      # Add brainstorm_items, procedure_subjects, procedures, procedure_steps
│   │   ├── migrate-v3.8.sql      # Add brainstorm_items.notes + .timing
│   │   ├── migrate-v3.9.sql      # Add brainstorm_items.assigned_to (FK users)
│   │   ├── migrate-v3.10.sql     # Add brainstorm_steps (sub-steps per item)
│   │   ├── migrate-v3.11.sql     # Add upskilling_items (learning links)
│   │   └── .htaccess             # Blocks web access to .db and .sql files
│   ├── includes/
│   │   ├── auth.php              # Authentication, sessions, rate limiting (no RBAC)
│   │   ├── db.php                # SQLite PDO connection (WAL, foreign keys, src fallback)
│   │   ├── layout.php            # Authenticated page shell (layout_start/layout_end)
│   │   ├── sidebar.php           # Sidebar nav (9 items) + user section
│   │   ├── _procedure_row.php    # Partial: single row for procedure list
│   │   ├── head.php              # SEO-optimized HTML head (for public pages)
│   │   ├── nav.php               # Responsive navigation (for public pages)
│   │   ├── footer.php            # Footer with social links (for public pages)
│   │   └── scripts.php           # JavaScript includes (for public pages)
│   ├── api/                      # JSON endpoints (POST + CSRF required)
│   │   ├── tasks.php             # Task CRUD, status toggles, checklists, deps
│   │   ├── notes.php             # Notes CRUD
│   │   ├── tabs.php              # Tab set + URL CRUD + reorder
│   │   ├── monitors.php          # Uptime monitor CRUD + live checks
│   │   ├── brainstorm.php        # Brainstorm item CRUD + reorder
│   │   ├── procedures.php        # Subjects + procedures + steps CRUD + reorder
│   │   ├── upskilling.php        # Upskilling links CRUD + status changes
│   │   └── .htaccess             # Deny non-php files
│   ├── admin/
│   │   └── index.php             # Login page (at /admin/)
│   ├── index.php                 # Landing page (redirects appropriately)
│   ├── dashboard.php             # Main dashboard (greeting, my tasks, stats)
│   ├── projects.php              # Projects list (top-level only)
│   ├── project.php               # Single project view (tasks, notes, docs, sub-projects)
│   ├── tasks.php                 # Tasks list with filters
│   ├── brainstorm.php            # Shared quick list (flat, reorderable, no projects)
│   ├── notes.php                 # Notes/ideas board
│   ├── docs.php                  # Knowledge base (tag filtering)
│   ├── doc.php                   # Single document view/edit
│   ├── procedures.php            # Procedures list (grouped by subject)
│   ├── procedure.php             # Single procedure view (ordered steps)
│   ├── upskilling.php            # Upskilling — saved learning links (YT-aware)
│   ├── tabs.php                  # Tab Opener (bundles of URLs)
│   ├── uptime.php                # Uptime monitor dashboard
│   ├── migrate.php               # Migration runner UI (/migrate)
│   ├── logout.php
│   └── .htaccess                 # URL rewriting, security headers, HSTS
├── dist/                         # Built files (deploy this)
├── export/                       # Historical SQL files (v1 MySQL migration artifacts)
├── ftp-tool/                     # FTP deploy tool (CLI + GUI)
│   ├── deploy-cli.py             # CLI deploy script (5 concurrent FTP connections)
│   ├── deploy-gui.py             # PySide6 GUI version
│   ├── Deploy-DEV.command        # macOS double-click launcher (dev)
│   ├── Deploy-LIVE.command       # macOS double-click launcher (live)
│   ├── deploy-config.json        # Project-specific config (not committed)
│   ├── ftp-credentials.json      # FTP password (not committed)
│   ├── CLAUDE.md                 # Deploy tool instructions for Claude
│   └── logs/                     # Deploy log files (not committed)
├── prose/                        # Page content/copy in markdown
├── brief/                        # Project briefs / planning notes
├── projects/                     # Per-project working content (e.g. projects/youtube/ai)
├── Initial/                      # Project initialization files
├── deploy.md                     # Deployment guide
├── security-plan.md              # Security planning documentation
├── implementation.md             # Implementation notes
├── instructions.md               # Troubleshooting notes
├── ssh.md                        # SSH access documentation
├── v2-proposal.md                # v2 redesign proposal (historical)
├── links.txt                     # Quick link reference
├── package.json                  # Tailwind CLI build scripts
└── project.md                    # This file
```

---

## Dashboard Sections

The sidebar has 10 navigation items (defined in `src/includes/sidebar.php`):

1. **Dashboard** — Greeting, my open tasks, stats, pinned notes
2. **Projects** — Top-level project list, with sub-project drilldown
3. **Tasks** — Filterable task list (mine, active, done, blocked, by project/assignee)
4. **Brainstorm** — Shared quick list for ideas and small to-dos (flat, reorderable, no projects)
5. **Notes** — Quick notes/ideas board (flat list, optional project link)
6. **Docs** — Knowledge base (tag filtering, optional project link)
7. **Procedures** — Step-by-step reference procedures, grouped by subject
8. **Upskilling** — Saved learning links (YouTube-aware previews); per-person, three-status (unwatched/watching/watched)
9. **Tab Opener** — Named URL bundles that open all at once
10. **Uptime** — Manual URL status checks (up/down/unknown)

User avatar, dark mode toggle, and logout live in the sidebar footer.

---

## Database

### Overview

**Engine:** SQLite (single file at `src/data/adminability.db`)
**Schema baseline:** `src/data/schema.sql`
**Migrations:** `src/data/migrate-v3*.sql` — idempotent, applied in order
**Connection:** `src/includes/db.php` — PDO with WAL mode and foreign keys enabled

> **Note:** `schema.sql` is currently at the v3.2 baseline and does NOT yet include the v3.3–v3.11 additions (`monitors`, `monitor_urls`, `checklist_items`, `task_dependencies`, `projects.parent_id`, `brainstorm_items` + `notes`/`timing`/`assigned_to`, `brainstorm_steps`, `procedure_subjects`, `procedures`, `procedure_steps`, `upskilling_items`). The live DB has all of them; consult `sqlite3 adminability.db ".schema"` for the canonical state.

### Tables (current — v3.11)

**users** — id, email, password_hash, name, first_name, is_active, created_at, updated_at, last_login

**login_attempts** — rate limiting (ip_address, email, attempted_at)

**projects** — id, name, description, color, status (active/archived), parent_id (FK to projects, SET NULL), created_by, timestamps

**tasks** — id, title, description, project_id (FK, SET NULL), status (todo/in_progress/done), priority (low/normal/high/urgent), due_date, created_by, assigned_to, sort_order, completed_at, timestamps

**checklist_items** — id, task_id (FK, CASCADE), text, is_done, sort_order, created_at

**task_dependencies** — task_id (FK, CASCADE), depends_on_id (FK, CASCADE), PK(task_id, depends_on_id)

**notes** — id, title, content, type (note/idea/task/question), status (active/done/archived), priority (low/normal/high), is_pinned, project_id (FK, SET NULL), created_by, timestamps

**docs** — id, title, slug (UNIQUE), content, status (draft/published/archived), sort_order, project_id (FK, SET NULL), created_by, updated_by, timestamps

**doc_tags** — id, name, slug (UNIQUE), color

**doc_tag_map** — doc_id (FK, CASCADE), tag_id (FK, CASCADE), PK(doc_id, tag_id)

**tab_sets** — id, name, description, color, assigned_to, created_by, sort_order, timestamps

**tab_set_urls** — id, set_id (FK, CASCADE), url, label, sort_order, created_at

**monitors** — id, name, sort_order, created_by, timestamps (a monitor is a named "bundle" of URLs; per-URL status lives in `monitor_urls`)

**monitor_urls** — id, monitor_id (FK, CASCADE), label, url, last_status (up/down/unknown), last_status_code, last_response_time_ms, last_checked_at, last_error, sort_order, created_at

**brainstorm_items** — id, text, timing (free-text, optional), notes (optional long-form), assigned_to (FK users, SET NULL, NULL = unassigned), is_done, sort_order, created_by, timestamps (flat shared list, reorderable, no project link)

**brainstorm_steps** — id, brainstorm_id (FK brainstorm_items, CASCADE), text, is_done, sort_order, created_at (ordered sub-steps per brainstorm item; independent checkboxes)

**procedure_subjects** — id, name, slug (UNIQUE), color, sort_order, created_at (reusable categories for procedures)

**procedures** — id, title, description, subject_id (FK, SET NULL), project_id (FK, SET NULL), sort_order, created_by, timestamps

**procedure_steps** — id, procedure_id (FK, CASCADE), text, sort_order, created_at

**upskilling_items** — id, url, title (auto-fetched for YouTube via oEmbed), notes, youtube_id (extracted if YT), assigned_to (FK users, SET NULL), status (unwatched/watching/watched), sort_order, created_by, timestamps

### Migration History

| Version | Changes |
|---------|---------|
| v2 | MySQL → SQLite; removed RBAC (`roles`, `permissions`, `role_permissions`); flattened notes (removed `note_projects`); simplified rate limiting |
| v3 | Added `projects` + `tasks` |
| v3.2 | Dropped all video tables (`videos`, `video_progress`, `workflow_steps`, `video_categories`); added `project_id` to notes & docs; added `tab_sets` + `tab_set_urls` |
| v3.3 | Added `monitors` (uptime) |
| v3.4 | Added `checklist_items` + `task_dependencies` |
| v3.5 | Added `projects.parent_id` for sub-projects |
| v3.6 | Added `monitor_urls` — uptime monitors become bundles of URLs (many URLs per monitor) |
| v3.7 | Added `brainstorm_items`, `procedure_subjects`, `procedures`, `procedure_steps` |
| v3.8 | Added `brainstorm_items.notes` + `brainstorm_items.timing` |
| v3.9 | Added `brainstorm_items.assigned_to` (FK users, nullable = unassigned) |
| v3.10 | Added `brainstorm_steps` (ordered sub-steps per item, independent checkboxes) |
| v3.11 | Added `upskilling_items` (saved learning links: URL + title + youtube_id + status + assignee) |

---

## Key Features

### Authentication & Security
- Bcrypt password hashing (PASSWORD_DEFAULT)
- Session-based auth — session stays valid until end of login calendar day (America/New_York); re-login required after midnight
- CSRF protection on all forms and API endpoints
- Rate limiting: 5 attempts, then 15-minute lockout (`login_attempts` table)
- IP tracking (supports Cloudflare `CF-Connecting-IP` header)
- Session regeneration on login (prevents session fixation)
- Secure/HttpOnly/SameSite=Strict cookies in production
- HTTPS forced via `.htaccess` (skipped for `.test`/localhost)
- HSTS header in production (1 year, includeSubDomains)
- Security headers: X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy
- PHP version hidden (X-Powered-By removed)
- `.htaccess` in `src/data/` blocks web access to `.db` and `.sql` files
- **No RBAC** — all authenticated users have full access. Use `requireLogin()` instead of permission checks.

### Login
- Login page at `/admin/` (`src/admin/index.php`)
- Redirects to `/dashboard` on success
- `requireLogin()` redirects unauthenticated users to `/admin/`

### Projects
- Top-level list + drilldown into individual project pages
- **Sub-projects** via `parent_id` (one level deep in current UI)
- Each project page shows tasks, notes, docs, and sub-projects
- Rollup counts (tasks, notes, docs, sub-projects) on list view
- Status: active / archived

### Tasks
- Filters: mine, active, done, blocked (has unfinished deps), all
- Project, assignee, priority, due-date filters
- Checklists per task (inline check/uncheck)
- Task dependencies (task can't be unblocked until `depends_on` tasks are done)
- AJAX status toggle via `/api/tasks.php`

### Notes
- Flat list, filterable
- Types: note, idea, task, question
- Priority: low / normal / high
- Status: active / done / archived
- Pin to top
- Optional project link

### Docs (Knowledge Base)
- Flat list with many-to-many colored tags
- Status: draft / published / archived
- Split view: left list, right content view/edit
- Optional project link

### Tab Opener
- Named bundles of URLs
- One-click "open all" opens every URL in a new tab
- Per-bundle assignment (for filtering "mine")

### Uptime
- Manual URL checks (not a background cron — checks run on demand)
- **Bundle pattern:** a monitor is a named set of URLs (like Tab Opener), each URL tracked individually
- Per-URL: last status (up/down/unknown), HTTP code, response time, error, checked-at
- "Check" per monitor runs every URL in that bundle; "Check All" runs all bundles in parallel
- URLs preserve their last-checked status across edits (matched by URL string)

### Brainstorm
- Shared flat list for quick ideas and small to-dos (not scoped to a project)
- Inline add, check/uncheck, drag-to-reorder
- Per-item **person** assignment (Paul, Anita, or unassigned) shown as a colored pill
- Per-item **timing** (free text, e.g. "ASAP", "complete by Fri") shown as a badge
- Per-item **notes** (optional long-form detail) shown as a preview below the item
- Edit icon on each row opens an inline panel for editing text + person + timing + notes
- **Sub-steps:** per-item ordered step list with independent checkboxes; chevron + progress pill (e.g. `2/5`) appears on the row only when steps exist; expanded view lets you check, inline-edit, delete, reorder, and add more; first step is added from the Edit panel
- **Filter buttons** in the header: All · Paul · Anita · Unassigned
- **Hide completed** toggle in the header (persisted in localStorage)
- **Print** button uses `window.print()` with print-only CSS (hides sidebar, edit icons, drag handles)
- **Cross-user sync via polling** — each open tab calls `/api/brainstorm.php` with `action=state_hash` every 25s; if the server hash differs and the user isn't mid-edit, the tab soft-reloads (open steps panels persist across the reload via `sessionStorage`). Own mutations bump the baseline hash so you never reload on your own change. Polling pauses while the tab is hidden.
- **Mobile/touch refinements** in `styles.css` under the `@media (hover: none)` block: drag handles + delete buttons stay visible on touch devices, step checkboxes grow to 20×20, and the add-new form stacks on <480px screens.
- "Clear done" bulk action
- Created-by attribution shown next to each item

### Procedures
- Step-by-step reference procedures, grouped by reusable subjects
- Subjects (e.g. "YouTube workflow") have a color and slug; procedures can also link to a project
- Per-procedure: ordered steps, inline add/reorder/delete
- List view groups by subject; single-procedure page shows the full step list

### Upskilling
- Shared list of learning links (YouTube videos, articles, podcasts) for Paul + Anita
- Paste any URL — YouTube links auto-extract `youtube_id` and show the `hqdefault.jpg` thumbnail; non-YT links show a generic placeholder
- Title auto-fetched server-side via YouTube oEmbed when a YT URL is added with no title (4s timeout, best-effort — falls back to manual entry)
- Card grid layout (1/2/3 columns responsive); thumbnail + title link out to the URL in a new tab
- **Three statuses**: `unwatched` → `watching` → `watched` (cycle button on each card, or pick from the inline edit panel)
- **Filters**: person (All / Paul / Anita / Unassigned) and status (with counts) — independent and combinable via `?person=&status=` query params
- Sort: `watching` first, then `unwatched`, then `watched`; within each group, newest first
- Inline edit panel per card (URL, title, person, status, notes)
- Created-by attribution shown next to the assignee pill

---

## API Endpoints

All endpoints under `src/api/` are **POST-only** and require a valid CSRF token. They return JSON and enforce `isLoggedIn()`.

| Endpoint | Purpose |
|----------|---------|
| `/api/tasks.php` | Task CRUD, status toggle, checklist items, dependencies |
| `/api/notes.php` | Note CRUD, pin/unpin, status changes |
| `/api/tabs.php` | Tab set + URL CRUD + reorder |
| `/api/monitors.php` | Monitor CRUD + live HTTP checks |
| `/api/brainstorm.php` | Brainstorm item + step CRUD, toggle, reorder, and `state_hash` (polling signature) |
| `/api/procedures.php` | Subjects + procedures + steps CRUD + reorder |
| `/api/upskilling.php` | Upskilling link CRUD + `set_status` (with YouTube ID extraction + oEmbed title fetch on create) |

The CSRF token is injected into the page via a `<meta name="csrf-token">` tag in `layout.php` and read by `scripts.js` for AJAX requests.

---

## Layout System

Authenticated pages use a function-based layout shell:

```php
$page_title = 'Tasks';
$current_page = 'tasks';   // For sidebar active state
require_once __DIR__ . '/includes/layout.php';

// ... fetch data using $pdo, $currentUser, etc ...

layout_start();
?>

<!-- page HTML -->

<?php
layout_end();
```

`layout.php` calls `requireLogin()`, exposes `$pdo` + `$currentUser` + `$csrf`, and includes `sidebar.php` between `layout_start()` and `layout_end()`.

`sidebar.php` reads `$current_page` to highlight the active nav item.

---

## Database Connection

`src/includes/db.php` connects to SQLite with a local-dev fallback:

```php
$db_path = __DIR__ . '/../data/adminability.db';

// Dev fallback: if running from /dist and the DB wasn't copied, use /src
if (!file_exists($db_path)) {
    $srcFallback = __DIR__ . '/../../src/data/adminability.db';
    if (file_exists($srcFallback)) $db_path = $srcFallback;
}

$pdo = new PDO("sqlite:$db_path", null, null, [...]);
$pdo->exec('PRAGMA journal_mode=WAL');
$pdo->exec('PRAGMA foreign_keys=ON');
```

---

## Deployment

### Files
1. Run `npm run production` locally
2. Upload `/dist` contents to server via FTP deploy tool or FileZilla
   - See `ftp-tool/CLAUDE.md` for deploy tool usage
   - **The SQLite DB is excluded** — the live DB is authoritative and must not be overwritten

### Database Updates
For schema changes:
1. Write a new migration file: `src/data/migrate-vX.Y.sql` (idempotent — use `CREATE TABLE IF NOT EXISTS` / `ALTER TABLE`)
2. Apply locally (options):
   - Visit `/migrate` in the browser — click "Apply pending migrations" (recommended)
   - Or: `sqlite3 src/data/adminability.db < src/data/migrate-vX.Y.sql`
3. Deploy the app (which ships the new `.sql` file into `dist/data/`), then visit `/migrate` on the live site and click apply
4. Update the schema baseline in `src/data/schema.sql` when convenient

The `/migrate` runner tracks applied versions in a `schema_migrations` table. On first use, it auto-detects already-applied migrations by inspecting existing tables/columns, so it won't re-run ALTER statements that would fail on pre-existing schema.

---

## Current State

### Implemented
- Session-based authentication (no RBAC), daily expiry at midnight local time, rate limiting
- Session hardening (secure cookies, regeneration, HSTS in production)
- Projects with sub-projects (one-level)
- Tasks with checklists and dependencies
- Notes (flat, project-linkable)
- Knowledge base with tag filtering (project-linkable)
- Tab Opener (URL bundles)
- Uptime monitoring (manual checks)
- Dashboard with greeting, my tasks, stats
- 9-item sidebar navigation
- JSON API under `/api/` with CSRF enforcement
- SQLite database (single file, WAL mode)
- Clean URLs (no .php extension)
- Gzip compression and browser caching
- Dark mode toggle with no-flash inline script
- Mobile sidebar (backdrop + slide-in)

### Potential Future Work
- Background cron for uptime checks (currently on-demand only)
- Multi-level sub-project nesting (currently one level)
- Activity log / audit trail
- Bulk actions on tasks
- Inline note editing
- `schema.sql` consolidation (currently only v3.2 baseline — needs merge of v3.3–v3.11)

---

## Technical Stack

- **Backend:** PHP 8.0+
- **Database:** SQLite (WAL mode, foreign keys)
- **CSS:** Tailwind CSS v4 (via `@tailwindcss/cli`) + `@tailwindcss/typography`
- **Server:** Apache with mod_rewrite
- **Build:** npm scripts (rsync for file copying)

---

## Users

**Local + Live:**
- paul@paulgreblick.com (admin)
- anita@angelsandinsights.com (admin)

All users have full access — no role distinctions.

---

## Public Page Template System

For creating public-facing pages (non-dashboard), the `includes/` folder still has the reusable pieces:

**Includes:**
- `head.php` — SEO-optimized HTML head with Open Graph and Twitter Cards
- `nav.php` — Responsive navigation with mobile menu
- `footer.php` — Footer with social media links
- `scripts.php` — JavaScript includes and closing tags

**Template variables:**
```php
// Required
$page_title = 'Page Title | Site Name';
$page_description = 'Page description for SEO';
$page_keywords = 'keyword1, keyword2';
$page_author = 'Author Name';
$current_page = 'page-slug';  // For nav active state

// Optional
$site_name = 'Site Name';
$og_image = '/assets/images/og-image.jpg';
$custom_head = '';
$custom_scripts = '';
```

> The old `src/template.php` starter file was removed in v3.2. Copy an existing public page (or re-create from scratch using `head.php` + `nav.php` + `footer.php` + `scripts.php`) if you need a new public page.

---

## Common Tasks

### Add a new authenticated page
1. Create `src/newpage.php`
2. Use the layout shell:
   ```php
   <?php
   $page_title = 'New Page';
   $current_page = 'newpage';
   require_once __DIR__ . '/includes/layout.php';
   // ... data fetching ...
   layout_start();
   ?>
   <!-- page HTML -->
   <?php layout_end(); ?>
   ```
3. Add a nav entry to `$nav` in `src/includes/sidebar.php`
4. Add a URL-rewrite rule in `src/.htaccess` if you want a clean URL
5. Run `npm run build`

### Add a schema change
1. Create `src/data/migrate-vX.Y.sql` (idempotent!)
2. Apply locally: `sqlite3 src/data/adminability.db < src/data/migrate-vX.Y.sql`
3. On deploy, apply the same file to the live DB
4. (Optional) Fold into `schema.sql` baseline

---

## File Reference

### Core Application Files
| File | Purpose |
|------|---------|
| `src/includes/auth.php` | Authentication, session management, rate limiting, CSRF |
| `src/includes/db.php` | SQLite PDO connection (WAL, FK, src fallback) |
| `src/includes/layout.php` | Authenticated page shell (`layout_start`/`layout_end`) |
| `src/includes/sidebar.php` | Sidebar navigation (9 items) + user section |
| `src/includes/_procedure_row.php` | Partial: single row for procedure list |
| `src/admin/index.php` | Login page |
| `src/index.php` | Landing/redirect |
| `src/dashboard.php` | Dashboard home |
| `src/projects.php` | Projects list |
| `src/project.php` | Single project view |
| `src/tasks.php` | Tasks list + filters |
| `src/brainstorm.php` | Shared brainstorm/quick list |
| `src/notes.php` | Notes board |
| `src/docs.php` | Knowledge base list |
| `src/doc.php` | Single doc view/edit |
| `src/procedures.php` | Procedures list (grouped by subject) |
| `src/procedure.php` | Single procedure view (ordered steps) |
| `src/upskilling.php` | Upskilling — saved learning links (YT-aware) |
| `src/tabs.php` | Tab Opener |
| `src/uptime.php` | Uptime dashboard |
| `src/migrate.php` | Migration runner UI (`/migrate`) |
| `src/logout.php` | Session destroy + redirect |
| `src/.htaccess` | URL rewriting, security headers, HTTPS redirect, HSTS |

### API Files
| File | Purpose |
|------|---------|
| `src/api/tasks.php` | Task CRUD + checklists + dependencies |
| `src/api/notes.php` | Notes CRUD |
| `src/api/tabs.php` | Tab set + URL CRUD + reorder |
| `src/api/monitors.php` | Uptime monitor CRUD + live checks |
| `src/api/brainstorm.php` | Brainstorm item + step CRUD, toggle, reorder, `state_hash` (polling) |
| `src/api/procedures.php` | Subjects + procedures + steps CRUD + reorder |
| `src/api/upskilling.php` | Upskilling link CRUD + status changes (YT ID + oEmbed title) |
| `src/api/.htaccess` | Deny non-php files |

### Public Page Template Files
| File | Purpose |
|------|---------|
| `src/includes/head.php` | SEO-optimized HTML head |
| `src/includes/nav.php` | Responsive public nav |
| `src/includes/footer.php` | Public footer with social links |
| `src/includes/scripts.php` | Public JS includes |
| `src/assets/js/scripts.js` | Dark mode, sidebar toggle, toasts |

### Data Files
| File | Purpose |
|------|---------|
| `src/data/adminability.db` | SQLite database (not deployed; live is authoritative) |
| `src/data/schema.sql` | Schema baseline (currently v3.2 — behind live) |
| `src/data/migrate-v3.sql` | Projects + tasks |
| `src/data/migrate-v3.2.sql` | Drop videos, add project links, tab opener |
| `src/data/migrate-v3.3.sql` | Monitors (uptime) |
| `src/data/migrate-v3.4.sql` | Checklists + task dependencies |
| `src/data/migrate-v3.5.sql` | Sub-projects (parent_id) |
| `src/data/migrate-v3.6.sql` | Uptime monitors become URL bundles (monitor_urls) |
| `src/data/migrate-v3.7.sql` | Brainstorm items + procedures (subjects, procedures, steps) |
| `src/data/migrate-v3.8.sql` | Brainstorm items: notes + timing columns |
| `src/data/migrate-v3.9.sql` | Brainstorm items: assigned_to (FK users, nullable) |
| `src/data/migrate-v3.10.sql` | Brainstorm sub-steps (`brainstorm_steps` table) |
| `src/data/migrate-v3.11.sql` | Upskilling — saved learning links (`upskilling_items` table) |
| `src/data/.htaccess` | Blocks web access to .db and .sql files |

### Historical / Reference
| File | Purpose |
|------|---------|
| `export/` | v1 MySQL artifacts + migration scripts (historical) |
| `v2-proposal.md` | v2 redesign proposal (historical) |
| `deploy.md` | Deployment guide |
| `security-plan.md` | Security planning |
| `implementation.md` | Implementation notes |
| `instructions.md` | Troubleshooting notes |
| `ssh.md` | SSH access |
