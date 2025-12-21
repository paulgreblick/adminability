# Deployment Guide

How to deploy Adminability from Mac Mini to servers.

---

## Deploy to Ubuntu LAN Server

### Prerequisites (on Ubuntu)
```bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-pdo libapache2-mod-php
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Step 1: Export Database (on Mac)
```bash
mysqldump -u root paulgreb_adminability > ~/adminability.sql
```

### Step 2: Copy Files to Ubuntu
```bash
# Replace 'user' and 'ubuntu-server' with your Ubuntu username and IP/hostname
rsync -avz /Users/paulgreblick/Paul/Projects/Web/Other/adminability/dist/ user@ubuntu-server:/var/www/adminability/

# Copy the SQL file
scp ~/adminability.sql user@ubuntu-server:~/
```

### Step 3: Set Up Database (on Ubuntu)
```bash
# Create database
sudo mysql -e "CREATE DATABASE paulgreb_adminability;"

# Create user (change password as needed)
sudo mysql -e "CREATE USER 'adminability'@'localhost' IDENTIFIED BY 'your_password_here';"
sudo mysql -e "GRANT ALL PRIVILEGES ON paulgreb_adminability.* TO 'adminability'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Import data
sudo mysql paulgreb_adminability < ~/adminability.sql
```

### Step 4: Update Database Config (on Ubuntu)
Edit `/var/www/adminability/includes/db.php` and update the local credentials:
```php
if ($isLocal) {
    $db_user = 'adminability';
    $db_pass = 'your_password_here';
}
```

### Step 5: Set Permissions (on Ubuntu)
```bash
sudo chown -R www-data:www-data /var/www/adminability
sudo chmod -R 755 /var/www/adminability
```

### Step 6: Configure Apache (on Ubuntu)
```bash
sudo nano /etc/apache2/sites-available/adminability.conf
```

Add this config:
```apache
<VirtualHost *:80>
    ServerName adminability.local
    DocumentRoot /var/www/adminability

    <Directory /var/www/adminability>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/adminability_error.log
    CustomLog ${APACHE_LOG_DIR}/adminability_access.log combined
</VirtualHost>
```

Enable the site:
```bash
sudo a2ensite adminability.conf
sudo systemctl reload apache2
```

### Step 7: Create .htaccess (on Ubuntu)
Create `/var/www/adminability/.htaccess`:
```apache
RewriteEngine On
RewriteBase /

# Remove .php extension
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^(.*)$ $1.php [L]
```

### Step 8: Access the Site
Access via Ubuntu's IP address: `http://192.168.x.x/`

### Updating Ubuntu Server
After making changes on Mac:
```bash
npm run build
rsync -avz /Users/paulgreblick/Paul/Projects/Web/Other/adminability/dist/ user@ubuntu-server:/var/www/adminability/
```

---

## Deploy to BigScoots (Live Server)

---

## Quick Deploy (Files Only)

When you've made code changes and just need to update files:

```bash
# 1. Build the project
npm run production

# 2. Upload via FileZilla
```

**FileZilla Settings:**
- Host: `ftp.adminability.ac` (or check BigScoots for SFTP details)
- Upload contents of `/dist` folder to `/public_html`

---

## Full Deploy (Files + Database + Data)

When you've added new features with database changes:

### Step 1: Build
```bash
npm run production
```

### Step 2: Upload Files
1. Open FileZilla
2. Connect to BigScoots server
3. Upload everything in `/dist` to `/public_html`

### Step 3: Run Database Migrations
1. Log into BigScoots cPanel
2. Open phpMyAdmin
3. Select database: `paulgreb_adminability`
4. Go to SQL tab
5. Run the SQL files in order (see table below)

### Step 4: Sync Data (if needed)
If you have local data that needs to go live, run `export/full-data-sync.sql`

---

## SQL Files Reference

Run these in order based on what you need:

| File | Purpose | When to Run |
|------|---------|-------------|
| `database-setup.sql` | Core tables (users, roles, permissions, notes) | Fresh install only |
| `video-tracker-tables.sql` | Video tracker schema (categories, videos, workflow_steps, progress) | Fresh install or video tracker setup |
| `docs-setup.sql` | Knowledge base tables (doc_categories, docs) + permissions | Docs feature setup |
| `sync-to-live.sql` | Video tracker tables + data | Video tracker deployment |
| `notes-upgrade.sql` | Enhanced notes (projects, types, replies) | Notes upgrade |
| `full-data-sync.sql` | All local data (videos, notes, progress) | After schema changes |
| `migrations/*.sql` | Incremental schema changes | As needed for specific updates |

---

## Data Sync Process

**Problem:** Files deploy via FileZilla, but data lives in the database.

**Solution:** When you have local data to sync:

1. Ask Claude to "export my local data for live deployment"
2. Claude will generate/update `export/full-data-sync.sql`
3. Run that SQL in phpMyAdmin on BigScoots

**What gets synced:**
- Video categories
- Videos (titles)
- Workflow steps
- Video progress
- Note projects
- Notes and replies

**What does NOT get synced (stays separate):**
- Users (local has 1, live has 2)
- Login attempts/lockouts
- Sessions

---

## Database Migrations (Without Losing Data)

When you add new database features locally but dev/live servers already have data you want to keep.

### The Golden Rules

1. **Never use DROP TABLE** - destroys all data
2. **Use ALTER TABLE** - modifies structure, keeps data
3. **Use CREATE TABLE IF NOT EXISTS** - safe for new tables
4. **Use INSERT IGNORE** - won't fail if data exists

### Writing Migration Files

Create files in `export/migrations/` with date prefix:

**Example: `export/migrations/2024-01-15-add-doc-tags.sql`**
```sql
-- Migration: Add tags to docs
-- Safe to run multiple times

-- Add new column (won't fail if exists)
ALTER TABLE docs ADD COLUMN tags VARCHAR(255) DEFAULT NULL;

-- Add index (wrap in procedure to check if exists)
CREATE INDEX idx_docs_tags ON docs(tags);
```

### Safe Patterns

**Adding a column:**
```sql
ALTER TABLE docs ADD COLUMN new_field VARCHAR(255) DEFAULT NULL;
```

**Adding a new table:**
```sql
CREATE TABLE IF NOT EXISTS doc_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);
```

**Adding default/seed data:**
```sql
-- Won't duplicate if already exists
INSERT IGNORE INTO categories (id, name) VALUES
(1, 'Reference'),
(2, 'Guides');
```

**Updating existing rows:**
```sql
-- Only updates rows where field is null
UPDATE docs SET new_field = 'default' WHERE new_field IS NULL;
```

### Unsafe Patterns (Avoid These)

```sql
-- BAD: Destroys all data
DROP TABLE docs;
CREATE TABLE docs (...);

-- BAD: Loses column data
ALTER TABLE docs DROP COLUMN old_field;

-- BAD: Duplicates data on re-run
INSERT INTO categories (name) VALUES ('Reference');
```

### Workflow

1. Make schema changes locally, test them
2. Write a migration file with only the ALTER/CREATE statements
3. Copy migration file to server
4. Run migration in phpMyAdmin or via command line:
   ```bash
   mysql -u user -p database_name < migration-file.sql
   ```
5. Your existing data is preserved

---

## Current Deployment Checklist

For deploying the latest version:

1. [ ] `npm run production`
2. [ ] Upload `/dist` to BigScoots via FileZilla
3. [ ] Run `sync-to-live.sql` in phpMyAdmin (video tracker)
4. [ ] Run `docs-setup.sql` in phpMyAdmin (knowledge base)
5. [ ] Run `notes-upgrade.sql` in phpMyAdmin (notes features)
6. [ ] Run `full-data-sync.sql` in phpMyAdmin (all data)
7. [ ] Test at https://adminability.ac

---

## First-Time Setup (New Server)

1. Create database `paulgreb_adminability` in cPanel
2. Create database user and assign to database
3. Run SQL files in order:
   - `database-setup.sql` (core tables)
   - `video-tracker-tables.sql` (video tracker)
   - `docs-setup.sql` (knowledge base)
   - `notes-upgrade.sql` (enhanced notes)
4. Upload `/dist` contents to `/public_html`

---

## After Deploying

1. Visit https://adminability.ac
2. Clear browser cache if needed (Cmd+Shift+R)
3. Test the feature you deployed
4. Check browser console for errors

---

## Rollback

If something breaks:

**Files:** Re-upload the previous version from your local `/dist` folder

**Database:** BigScoots has daily backups - contact support or restore via cPanel

---

## Common Issues

### "Page not found" after deploy
- Check `.htaccess` was uploaded
- Verify mod_rewrite is enabled on server

### CSS not updating
- Clear browser cache
- Check `/dist/assets/css/styles.css` was uploaded

### Database errors
- Verify database credentials in `includes/db.php`
- Check table exists in phpMyAdmin
- Look at PHP error logs in cPanel

### Data not showing
- Did you run the data sync SQL?
- Check table has data in phpMyAdmin

### Login not working
- Check `login_attempts` and `ip_lockouts` tables exist
- Clear cookies and try again
