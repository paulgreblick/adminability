# Adminability - Project Documentation

A password-protected admin dashboard for managing projects, notes, and tracking workflows. Built with PHP, MySQL, and Tailwind CSS v4.

**Live URL:** https://adminability.ac
**Local URL:** http://adminability.test:8080
**Hosting:** BigScoots (shared hosting)

---

## Quick Start

```bash
# Build CSS and copy files to dist/
npm run build

# Production build (minified CSS)
npm run production
```

**Local development:** Edit files in `/src`, run `npm run build`, test at `adminability.test:8080`
**Deploy:** Upload contents of `/dist` folder to server via FileZilla

> **IMPORTANT:** After making ANY changes to files in `/src`, you must run `npm run build` (or `npm run production` for minified CSS) before testing. The `/dist` folder is what gets served locally and deployed.

---

## Project Structure

```
adminability/
├── src/                          # Source files (edit here)
│   ├── assets/
│   │   ├── css/styles.css        # Tailwind source CSS
│   │   └── js/scripts.js         # Main JavaScript (mobile menu toggle)
│   ├── includes/
│   │   ├── auth.php              # Authentication, sessions, RBAC, rate limiting
│   │   ├── db.php                # Database connection (auto-detects env)
│   │   ├── dashboard-layout.php  # Sidebar layout wrapper (for dashboard pages)
│   │   ├── dashboard-footer.php  # Dashboard footer closing tags
│   │   ├── head.php              # SEO-optimized HTML head (for public pages)
│   │   ├── nav.php               # Responsive navigation (for public pages)
│   │   ├── footer.php            # Footer with social links (for public pages)
│   │   └── scripts.php           # JavaScript includes (for public pages)
│   ├── index.php                 # Landing page (redirects appropriately)
│   ├── dashboard.php             # Main dashboard
│   ├── videos.php                # Video tracker (main list)
│   ├── video.php                 # Single video detail page
│   ├── docs.php                  # Knowledge base (tree navigation)
│   ├── doc.php                   # Single document view/edit
│   ├── notes.php                 # Notes/ideas board
│   ├── users.php                 # User management
│   ├── roles.php                 # Role management
│   ├── TreePlane.php             # Login page (obscured URL)
│   ├── logout.php
│   ├── template.php              # Starter template for new public pages
│   └── .htaccess                 # URL rewriting, security headers, HSTS
├── dist/                         # Built files (deploy this)
├── export/                       # SQL files for database setup
│   ├── database-setup.sql        # Core tables (users, roles, permissions, notes)
│   ├── video-tracker-tables.sql  # Video tracker schema
│   ├── docs-setup.sql            # Knowledge base tables (doc_categories, docs)
│   ├── sync-to-live.sql          # Video tracker sync to production
│   ├── notes-upgrade.sql         # Enhanced notes features
│   ├── full-data-sync.sql        # Complete data sync for deployment
│   ├── db.php.production         # Production database config reference
│   ├── video-tracker-migration.sql   # Video tracker migration
│   ├── deploy-video-tracker-live.sql # Video tracker deploy script v1
│   ├── deploy-video-tracker-v2.sql   # Video tracker deploy script v2
│   ├── video-data-export.sql     # Partial video data export
│   ├── video-data-complete.sql   # Complete video data export
│   ├── live-backup.sql           # Backup of live database
│   ├── migrate-add-docs.sql      # Docs migration for live
│   ├── migrate-add-first-name.sql # Add first_name column migration
│   └── migrations/
│       └── add-knowledge-base.sql    # Knowledge base schema migration
├── projects/youtube/ai/affirmations/
│   └── video-tracker-plan.md     # Original planning doc
├── deploy.md                     # Deployment guide
├── security-plan.md              # Security planning documentation
├── implementation.md             # Implementation notes
├── instructions.md               # Troubleshooting notes (e.g., 404 after login fix)
├── ssh.md                        # SSH access documentation
├── package.json                  # Tailwind CLI build scripts
└── project.md                    # This file
```

---

## Dashboard Sections

The dashboard sidebar is organized into these sections:

### 1. Progress
- **Affirmations** (Video Tracker) - YouTube video production tracking
- Future: More project trackers

### 2. Docs
- **Knowledge Base** - Tree-structured documentation with categories, nested docs, and rich content
- **Notes** - Quick notes/ideas board with priorities and status

### 3. Tracking
- Coming soon - For tracking published content, analytics, etc.

### 4. Admin
- **Users** - User management (CRUD)
- **Roles** - Role and permission management

---

## Database Schema

### Core Tables (database-setup.sql)

**users**
- id, email, password_hash, name, first_name, role_id, is_active, created_at, updated_at, last_login

**roles**
- id, name, description, created_at
- Default roles: super_admin, admin, editor, viewer

**permissions**
- id, name, description
- Format: `resource.action` (e.g., `videos.edit`, `notes.create`)

**role_permissions**
- role_id, permission_id (many-to-many)

**notes**
- id, title, content, status (idea/in_progress/done), priority (low/normal/high), created_by, timestamps

**login_attempts** / **ip_lockouts**
- Rate limiting tables for brute force protection

### Video Tracker Tables

**workflow_steps** (dynamic work types)
```sql
- id INT PRIMARY KEY
- name VARCHAR(100)           -- e.g., "Research", "Audio Record"
- phase ENUM('writing', 'production', 'publishing')
- sort_order INT
- created_at TIMESTAMP
```

**video_categories** (video groupings)
```sql
- id INT PRIMARY KEY
- name VARCHAR(100)           -- e.g., "Self Love", "Manifestation"
- description VARCHAR(255)
- sort_order INT
- created_at TIMESTAMP
```

Pre-populated with 16 affirmation topics:
1. Morning Positive Energy
2. Self Love
3. Abundance
4. Manifestation
5. Money
6. Wealth
7. Success
8. Happiness
9. Health
10. Peace and Calm
11. Stress
12. Anxiety
13. Worry
14. Overwhelm
15. Healing from the Past
16. Positive Life Changes

**videos**
```sql
- id INT PRIMARY KEY
- category_id INT (FK to video_categories)
- title VARCHAR(255)
- notes TEXT
- folder_link VARCHAR(500)
- youtube_url VARCHAR(255)
- published_at DATETIME
- created_at, updated_at TIMESTAMP
```

**video_progress** (tracks each video's status per workflow step)
```sql
- id INT PRIMARY KEY
- video_id INT (FK to videos, ON DELETE CASCADE)
- step_id INT (FK to workflow_steps, ON DELETE RESTRICT)
- status ENUM('not_started', 'in_progress', 'complete')
- updated_at TIMESTAMP
- UNIQUE (video_id, step_id)
```

Note: `ON DELETE RESTRICT` on step_id prevents deleting workflow steps that have progress recorded.

### Knowledge Base Tables (docs-setup.sql)

**doc_categories**
```sql
- id INT PRIMARY KEY
- name VARCHAR(100)           -- e.g., "Reference", "Processes"
- slug VARCHAR(100) UNIQUE
- description VARCHAR(255)
- icon VARCHAR(50)            -- e.g., "folder", "book"
- color VARCHAR(20)           -- e.g., "blue", "green"
- sort_order INT
- created_at TIMESTAMP
```

Default categories: Reference, Processes, Workflows, Guides

**docs**
```sql
- id INT PRIMARY KEY
- category_id INT (FK to doc_categories)
- parent_id INT (FK to docs, self-referential for nesting)
- title VARCHAR(255)
- slug VARCHAR(255)
- content LONGTEXT             -- Markdown/rich content
- doc_type ENUM('reference', 'process', 'workflow', 'guide')
- status ENUM('draft', 'published', 'archived')
- sort_order INT
- created_by, updated_by INT (FK to users)
- created_at, updated_at TIMESTAMP
```

### Default Workflow Steps (15 total)

**Writing Phase (4 steps):**
1. Research
2. First Draft
3. Review/Edit
4. Final Script

**Production Phase (5 steps):**
5. Slides
6. Audio Record
7. Audio Edit
8. Video Compile
9. Video Edit

**Publishing Phase (6 steps):**
10. Thumbnail
11. SEO Title
12. Description
13. Tags
14. Upload
15. Publish

---

## Key Features

### Authentication & Security
- Bcrypt password hashing (PASSWORD_DEFAULT)
- Session-based auth with 30-minute inactivity timeout
- CSRF protection on all forms
- Rate limiting: 5 attempts, then 15-minute IP lockout
- IP tracking (supports Cloudflare CF-Connecting-IP header)
- Session regeneration on login (prevents session fixation)
- Secure/HttpOnly/SameSite=Strict cookies in production
- HTTPS forced via .htaccess (skipped for .test/localhost)
- HSTS header in production (1 year, includeSubDomains)
- Security headers: X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy
- PHP version hidden (X-Powered-By removed)

### Role-Based Access Control (RBAC)
Permissions are checked with `hasPermission('permission.name')` or `requirePermission()`.

Current permissions:
- `users.view`, `users.create`, `users.edit`, `users.delete`
- `roles.view`, `roles.manage`
- `notes.view`, `notes.create`, `notes.edit`, `notes.delete`
- `videos.view`, `videos.create`, `videos.edit`, `videos.delete`
- `docs.view`, `docs.create`, `docs.edit`, `docs.delete`
- `dashboard.view`

### Video Tracker Features
- **Category View:** All videos in a flat table with status circles
- **Work Type View:** Filter videos by which step they need completed
- **AJAX Status Updates:** Click status circles to cycle through states without page refresh
- **Manage Work Types:** Add/delete workflow steps from the UI (with database constraints)
- **Progress Tracking:** Overall and per-phase completion percentages

Status cycle: `○ not_started → ◐ in_progress → ● complete → ○ not_started`

### Knowledge Base Features
- **Tree Navigation:** Collapsible categories with nested documents
- **Document Types:** Reference, Process, Workflow, Guide
- **Status Tracking:** Draft, Published, Archived
- **Hierarchical Docs:** Documents can have parent-child relationships
- **Categories:** Customizable with colors and icons
- **Rich Content:** Supports long-form documentation

### Notes Features
- Priority levels (low/normal/high)
- Status tracking (idea/in_progress/done)
- Filtering by status
- "Copy All Notes" for sharing with Claude

---

## Database Connection

`src/includes/db.php` auto-detects the environment:

```php
$isLocal = (
    strpos($_SERVER['HTTP_HOST'] ?? '', '.test') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
);

if ($isLocal) {
    $db_user = 'root';
    $db_pass = '';
} else {
    $db_user = 'paulgreb_admin';
    $db_pass = 'lu@PGm1964';  // Production password
}
```

Database name: `paulgreb_adminability`

---

## Deployment

### Files
1. Run `npm run production` locally
2. Upload `/dist` contents to server via FileZilla

### Database Updates
For new tables or schema changes:
1. Create SQL migration file in `/export`
2. Run in phpMyAdmin on BigScoots

**Important:** The users table data differs between local and live. SQL migrations should:
- Use `INSERT IGNORE` or `ON DUPLICATE KEY` for data
- Only modify schema, not user data
- Add new permissions with: `INSERT IGNORE INTO permissions...`

---

## Current State

### Implemented
- Full RBAC authentication system
- Session hardening (secure cookies, timeout, regeneration)
- Rate limiting with IP lockout
- User management (CRUD)
- Notes/ideas board
- Knowledge base with tree navigation
- Video tracker with dynamic workflow steps
- AJAX status updates (no page refresh)
- Dashboard with Progress/Docs/Tracking/Admin sections
- Clean URLs (no .php extension)
- Security headers (HSTS, X-Frame-Options, etc.)
- HTTPS redirect in production
- Gzip compression and browser caching

### Future Plans (from video-tracker-plan.md)
- Dashboard stats and charts
- "What's next?" suggestions
- Bulk actions
- Activity logging
- Drag-and-drop reordering
- Mobile improvements
- CSV export

---

## Technical Stack

- **Backend:** PHP 8.0+
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **CSS:** Tailwind CSS v4 (via @tailwindcss/cli)
- **Server:** Apache with mod_rewrite
- **Build:** npm scripts (rsync for file copying)

---

## Users

**Local:**
- paul@paulgreblick.com (super_admin)

**Live:**
- paul@paulgreblick.com (super_admin)
- Second user exists on live only

---

## Public Page Template System

For creating public-facing pages (non-dashboard), use `template.php` as a starter. The system includes:

**Includes:**
- `head.php` - SEO-optimized HTML head with Open Graph and Twitter Cards
- `nav.php` - Responsive navigation with mobile menu
- `footer.php` - Footer with social media links
- `scripts.php` - JavaScript includes and closing tags

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
$custom_head = '';  // Additional head content
$custom_scripts = '';  // Additional scripts
```

---

## Common Tasks

### Add a new permission
```sql
INSERT INTO permissions (name, description) VALUES ('resource.action', 'Description');
-- Then assign to roles:
INSERT INTO role_permissions (role_id, permission_id) SELECT 1, id FROM permissions WHERE name = 'resource.action';
```

### Add a new workflow step
Use the "Manage Work Types" button in the video tracker UI, or:
```sql
INSERT INTO workflow_steps (name, phase, sort_order) VALUES ('Step Name', 'production', 10);
-- Then add to all existing videos:
INSERT INTO video_progress (video_id, step_id, status) SELECT id, LAST_INSERT_ID(), 'not_started' FROM videos;
```

### Add a new page
1. Create `src/newpage.php`
2. Include dashboard layout:
   ```php
   $dashboard_title = 'Page Title';
   $current_dashboard_page = 'newpage';
   include 'includes/dashboard-layout.php';
   requirePermission('permission.name');
   // ... page content
   <?php include 'includes/dashboard-footer.php'; ?>
   ```
3. Add to sidebar in `src/includes/dashboard-layout.php`
4. Run `npm run build` to copy files to `/dist` before testing

---

## File Reference

### Dashboard Files
| File | Purpose |
|------|---------|
| `src/includes/auth.php` | Authentication, session management, RBAC, rate limiting |
| `src/includes/db.php` | PDO database connection with environment detection |
| `src/includes/dashboard-layout.php` | Sidebar navigation and page wrapper |
| `src/includes/dashboard-footer.php` | Footer closing tags and dark mode script |
| `src/index.php` | Landing page (redirect logic) |
| `src/dashboard.php` | Main dashboard with project cards |
| `src/videos.php` | Video tracker dashboard (list view, AJAX updates) |
| `src/video.php` | Single video detail/edit page |
| `src/docs.php` | Knowledge base tree navigation |
| `src/doc.php` | Single document view/edit page |
| `src/notes.php` | Notes/ideas CRUD |
| `src/users.php` | User management |
| `src/roles.php` | Role and permission management |
| `src/TreePlane.php` | Login page (obscured URL) |
| `src/.htaccess` | URL rewriting, security headers, HTTPS redirect, HSTS |

### Public Page Template Files
| File | Purpose |
|------|---------|
| `src/template.php` | Starter template for new public pages |
| `src/includes/head.php` | SEO-optimized HTML head with OG/Twitter cards |
| `src/includes/nav.php` | Responsive navigation with mobile menu |
| `src/includes/footer.php` | Footer with social media links |
| `src/includes/scripts.php` | JavaScript includes and closing tags |
| `src/assets/js/scripts.js` | Main JavaScript (mobile menu toggle) |

### Database Files
| File | Purpose |
|------|---------|
| `export/database-setup.sql` | Core schema (run first on new install) |
| `export/video-tracker-tables.sql` | Video tracker schema |
| `export/docs-setup.sql` | Knowledge base schema |
| `export/notes-upgrade.sql` | Enhanced notes features |
| `export/sync-to-live.sql` | Production data sync for video tracker |
| `export/full-data-sync.sql` | Complete data sync for deployment |
| `export/video-tracker-migration.sql` | Video tracker migration script |
| `export/deploy-video-tracker-live.sql` | Video tracker deploy script v1 |
| `export/deploy-video-tracker-v2.sql` | Video tracker deploy script v2 |
| `export/live-backup.sql` | Backup of live database |
| `export/migrate-add-docs.sql` | Docs migration for live |
| `export/migrate-add-first-name.sql` | Add first_name column migration |
| `export/migrations/add-knowledge-base.sql` | Knowledge base schema migration |

### Documentation Files
| File | Purpose |
|------|---------|
| `project.md` | This file - main project documentation |
| `deploy.md` | Deployment guide and procedures |
| `security-plan.md` | Security planning documentation |
| `implementation.md` | Implementation notes |
| `instructions.md` | Troubleshooting notes (e.g., 404 after login fix) |
| `ssh.md` | SSH access documentation |
