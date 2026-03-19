# SSH Deployment Guide - BigScoots

## Connection Details

```
Host: [domain.com]
Username: paulgreb
Password: lu@PGb1964
Port: 2222
```

## Quick Deploy Commands

### Deploy to Live Site (www)
```bash
sshpass -p 'lu@PGb1964' rsync -avz -e "ssh -p 2222" ./dist/ paulgreb@[domain.com]:~/[domain.com]/
```

### Deploy to Dev Site
```bash
sshpass -p 'lu@PGb1964' rsync -avz -e "ssh -p 2222" ./dist/ paulgreb@[domain.com]:~/dev.[domain.com]/
```

### Deploy with Delete (removes files not in source)
```bash
sshpass -p 'lu@PGb1964' rsync -avz --delete -e "ssh -p 2222" ./dist/ paulgreb@[domain.com]:~/[domain.com]/
```

## SSH Commands

### Connect to Server
```bash
sshpass -p 'lu@PGb1964' ssh -p 2222 paulgreb@[domain.com]
```

### Run Single Command
```bash
sshpass -p 'lu@PGb1964' ssh -p 2222 paulgreb@[domain.com] "ls -la ~/[domain.com]/"
```

### Check What's on Server
```bash
sshpass -p 'lu@PGb1964' ssh -p 2222 paulgreb@[domain.com] "ls -la ~/"
```

## Directory Structure on BigScoots

```
/home/paulgreb/
├── [domain.com]/           # Live site (www)
├── dev.[domain.com]/       # Dev subdomain
├── test.[domain.com]/      # Test subdomain
└── public_html/            # Main account site (paulgreblick.com)
```

## Common Rsync Options

| Option | Description |
|--------|-------------|
| `-a` | Archive mode (preserves permissions, timestamps) |
| `-v` | Verbose output |
| `-z` | Compress during transfer |
| `--delete` | Delete files on destination not in source |
| `--dry-run` | Preview what would be transferred |
| `--exclude='*.log'` | Exclude files matching pattern |

### Preview Deploy (Dry Run)
```bash
sshpass -p 'lu@PGb1964' rsync -avz --dry-run -e "ssh -p 2222" ./dist/ paulgreb@[domain.com]:~/[domain.com]/
```

### Exclude Files
```bash
sshpass -p 'lu@PGb1964' rsync -avz --exclude='.DS_Store' --exclude='*.log' -e "ssh -p 2222" ./dist/ paulgreb@[domain.com]:~/[domain.com]/
```

## Fix Permissions After Deploy

If files aren't accessible (403 errors):
```bash
sshpass -p 'lu@PGb1964' ssh -p 2222 paulgreb@[domain.com] "chmod -R 644 ~/[domain.com]/*.php && chmod -R 644 ~/[domain.com]/assets/**/*"
```

Or fix all at once:
```bash
sshpass -p 'lu@PGb1964' ssh -p 2222 paulgreb@[domain.com] "find ~/[domain.com] -type f -exec chmod 644 {} \; && find ~/[domain.com] -type d -exec chmod 755 {} \;"
```

## Database Commands (If Needed)

### Export Database
```bash
sshpass -p 'lu@PGb1964' ssh -p 2222 paulgreb@[domain.com] "mysqldump -u [db_user] -p'[db_pass]' [db_name]" > backup.sql
```

### Import Database
```bash
sshpass -p 'lu@PGb1964' ssh -p 2222 paulgreb@[domain.com] "mysql -u [db_user] -p'[db_pass]' [db_name]" < migration.sql
```

### Run SQL Query
```bash
sshpass -p 'lu@PGb1964' ssh -p 2222 paulgreb@[domain.com] "mysql -u [db_user] -p'[db_pass]' [db_name] -e 'SHOW TABLES;'"
```

## Example: Full Deploy Workflow

```bash
# 1. Build production
npm run production

# 2. Preview what will be deployed
sshpass -p 'lu@PGb1964' rsync -avz --dry-run -e "ssh -p 2222" ./dist/ paulgreb@example.com:~/example.com/

# 3. Deploy
sshpass -p 'lu@PGb1964' rsync -avz -e "ssh -p 2222" ./dist/ paulgreb@example.com:~/example.com/

# 4. Fix permissions if needed
sshpass -p 'lu@PGb1964' ssh -p 2222 paulgreb@example.com "find ~/example.com -type f -exec chmod 644 {} \;"

# 5. Verify
curl -I https://example.com
```

## Notes

- Replace `[domain.com]` with actual domain
- Dev sites are at `dev.[domain.com]` folder
- Always build before deploying (`npm run production` or similar)
- Use `--dry-run` first if unsure
- Check file permissions if you get 403 errors
