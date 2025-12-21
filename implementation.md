# Implementation & Deployment Guide

## Overview

```
LOCAL (your Mac)                    LIVE SERVER
┌─────────────────┐                ┌─────────────────┐
│  /src           │   deploy →     │  /public_html   │
│  (edit here)    │                │  (or /var/www)  │
├─────────────────┤                └─────────────────┘
│  /dist          │                        ↑
│  (built files)  │────────────────────────┘
└─────────────────┘
```

You edit in `/src`, run `npm run build`, then upload `/dist` contents to your server.

---

## Deployment Options

### Option 1: Manual FTP/SFTP (Simple)
Upload the contents of your `dist/` folder to your server's web root.

**Tools:**
- FileZilla (free)
- Cyberduck (free)
- Transmit (Mac, paid)

**Steps:**
1. Run `npm run build` locally
2. Connect to your server via SFTP
3. Upload everything in `dist/` to your server's web root

**Pros:** Simple, no setup
**Cons:** Manual, easy to miss files

---

### Option 2: Git + Deploy Script (Recommended)
Push to GitHub, pull on server, run build there.

**Initial Server Setup:**
```bash
# On your server, clone the repo
cd /var/www
git clone https://github.com/yourusername/adminability.git
cd adminability
npm install

# Build
npm run build

# Point web root to dist folder
```

**To Deploy Updates:**
```bash
# On your server
cd /var/www/adminability
git pull
npm run build
```

**Even Better - Create a deploy script on your server:**
```bash
#!/bin/bash
# /var/www/adminability/deploy.sh
cd /var/www/adminability
git pull origin main
npm run build
echo "Deployed at $(date)"
```

Then just SSH in and run: `./deploy.sh`

---

### Option 3: GitHub Actions (Automated)
Automatically deploy when you push to GitHub.

I can set this up if you want - it would:
1. Watch for pushes to `main` branch
2. Build the project
3. Deploy to your server via SSH

---

## Database Sync

**Important:** The database is NOT part of the code deployment.

### Initial Setup on Live Server:
```sql
-- Run the same SQL that created your local database
CREATE DATABASE adminability;
-- Then create tables (I can give you the full SQL script)
```

### Ongoing:
- Database changes (new tables, columns) need to be run manually on live
- User data, notes, etc. are separate on local vs live
- Consider this: local = development, live = production (different data)

**I can create a `database-schema.sql` file with all the table definitions if helpful.**

---

## Recommended Workflow

### Daily Development:
```
1. Edit files in /src
2. Run: npm run build
3. Test locally at adminability.test:8080
4. When ready, deploy to live
```

### Deploying to Live:
```
1. Commit your changes: git add . && git commit -m "description"
2. Push to GitHub: git push
3. SSH to server and pull: git pull && npm run build
   (or run your deploy script)
4. Test on live site
```

---

## Server Requirements

Your live server needs:
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Node.js 18+ (for building CSS)
- Apache with mod_rewrite enabled
- SSL certificate

---

## File Structure on Server

```
/var/www/adminability/          # or wherever your host puts sites
├── src/                        # Source files (from git)
├── dist/                       # Built files (Apache serves this)
├── node_modules/               # npm packages (don't upload manually)
├── package.json
└── .env                        # Database credentials (create on server, don't commit)
```

**Apache virtual host should point to `/dist` folder.**

---

## Quick Commands Reference

| Task | Command |
|------|---------|
| Build for development | `npm run build` |
| Build for production (minified) | `npm run production` |
| Clean dist folder | `npm run clean` |

---

## Setting Up Git (If Not Done)

```bash
# In your project folder
cd /Users/paulgreblick/Paul/Projects/Web/Other/adminability

# Initialize git
git init

# Add all files
git add .

# First commit
git commit -m "Initial commit"

# Create repo on GitHub, then:
git remote add origin https://github.com/yourusername/adminability.git
git push -u origin main
```

---

## Next Steps

1. **Tell me your hosting situation** - Do you have a server already? Shared hosting? VPS?
2. **Set up Git** - If you want version control (recommended)
3. **I'll create the database schema SQL** - So you can set up the live database
4. **Choose deployment method** - FTP, Git pull, or automated

What's your hosting plan?
