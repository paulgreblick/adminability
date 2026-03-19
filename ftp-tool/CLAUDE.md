# FTP Deploy Tool

## !! NEW PROJECT SETUP - DO THIS FIRST !!

**If `deploy-config.json` does NOT exist yet, or still has another project's domain, you MUST set it up before deploying.**

### Step 1: Create the config from the template
```bash
cp ftp-tool/deploy-config.template.json ftp-tool/deploy-config.json
```

### Step 2: Edit `deploy-config.json`
Replace `DOMAIN_HERE` with the actual project domain (e.g. `example.com`):
- `"project_domain"`: set to `"example.com"`
- `"remote_path"` for dev: set to `"/dev.example.com"`
- `"remote_path"` for live: set to `"/example.com"`

The host and user should stay the same for all projects.

### Step 3: Credentials are already included
`ftp-credentials.json` already contains the FTP password. No prompting needed.

### Step 4: Verify the connection works
```bash
python3 ftp-tool/deploy-cli.py --verify
```
This uses the saved password and shows the remote folder contents for both dev and live, so you can confirm the paths are correct.

### Step 5: Add to .gitignore
```
ftp-tool/deploy-config.json
ftp-tool/ftp-credentials.json
ftp-tool/logs/
```

---

## Deploying from Claude Code

**IMPORTANT: Do NOT run deploy-cli.py directly from Claude Code.** Output buffering makes it appear to hang with no progress visible.

Instead, open a Terminal window so the user can see real-time progress:

```bash
# Deploy ALL files to dev
osascript -e 'tell application "Terminal" to do script "cd \"'$(pwd)'\" && python3 ftp-tool/deploy-cli.py dev -y"'

# Deploy ALL files to live
osascript -e 'tell application "Terminal" to do script "cd \"'$(pwd)'\" && python3 ftp-tool/deploy-cli.py live -y"'

# Deploy SPECIFIC files (much faster - use when only a few files changed)
osascript -e 'tell application "Terminal" to do script "cd \"'$(pwd)'\" && python3 ftp-tool/deploy-cli.py dev -y --only file1.php file2.php"'
```

**IMPORTANT: After EVERY deploy, you MUST check the log to confirm success.** The Terminal window may close before the user can read it. Wait a few seconds, then read the latest log:

```bash
# For --only deploys (1-10 files), wait 3-5 seconds
sleep 5 && cat "$(ls -t ftp-tool/logs/*.log | head -1)"

# For full deploys (hundreds of files), wait longer
sleep 60 && cat "$(ls -t ftp-tool/logs/*.log | head -1)"
```

Always report the result to the user: how many files uploaded, any failures, and time taken.

### When to use --only vs full deploy

**Use `--only` when:**
- You know which specific files changed (e.g. you just edited 2 PHP files)
- The user says "deploy just those files" or "deploy those two pages"
- Speed matters — `--only` takes seconds instead of minutes

**Use full deploy when:**
- Many files changed (CSS rebuild, new images, bulk edits)
- You're not sure what changed
- First deploy to a new environment

### How --only finds files

Pass filenames or paths relative to `dist/` (space-separated, NOT comma-separated):
- `--only about.php` — finds `dist/about.php`
- `--only insights/emotional-healing.php` — finds `dist/insights/emotional-healing.php`
- `--only about.php contact.php sessions.php` — multiple files

If a filename isn't found at the exact path, it searches `dist/` recursively by filename.

## CLI Usage (from Terminal directly)

```bash
python3 ftp-tool/deploy-cli.py dev          # Deploy all to dev (with confirmation)
python3 ftp-tool/deploy-cli.py dev -y       # Deploy all to dev (skip confirmation)
python3 ftp-tool/deploy-cli.py live -y      # Deploy all to live
python3 ftp-tool/deploy-cli.py dev -y --only about.php contact.php   # Specific files
python3 ftp-tool/deploy-cli.py --verify     # Verify remote folders
python3 ftp-tool/deploy-cli.py dev --dry-run   # Preview without uploading
```

## What This Folder Contains

- `deploy-cli.py` — CLI deploy script (5 concurrent FTP connections, logging)
- `deploy-gui.py` — PySide6 GUI version (optional, requires PySide6)
- `Deploy-DEV.command` / `Deploy-LIVE.command` — macOS double-click launchers for GUI
- `deploy-config.template.json` — Generic config template (commit this)
- `deploy-config.json` — Project-specific config (DO NOT commit)
- `ftp-credentials.json` — Saved FTP password (DO NOT commit, auto-created)
- `logs/` — Deploy log files (DO NOT commit, auto-created)
