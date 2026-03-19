#!/usr/bin/env python3
"""
Deploy CLI - FTP deployment with concurrent uploads and logging.

Usage:
  python3 deploy-cli.py dev              Deploy all files to dev site
  python3 deploy-cli.py live             Deploy all files to live site
  python3 deploy-cli.py --verify         Check remote folders (both dev & live)
  python3 deploy-cli.py dev --dry-run    Preview without uploading
  python3 deploy-cli.py dev --only about.php contact.php
  python3 deploy-cli.py dev --only insights/emotional-healing.php
"""

import ftplib
import json
import os
import sys
import getpass
import time
import threading
from datetime import datetime
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor, as_completed

# Paths
SCRIPT_DIR = Path(__file__).parent
CONFIG_PATH = SCRIPT_DIR / "deploy-config.json"
TEMPLATE_PATH = SCRIPT_DIR / "deploy-config.template.json"
CREDS_PATH = SCRIPT_DIR / "ftp-credentials.json"
LOGS_DIR = SCRIPT_DIR / "logs"

# Concurrent upload settings
MAX_CONNECTIONS = 5

# Thread-safe logging
_log_file = None
_log_lock = threading.Lock()
_counter_lock = threading.Lock()
_upload_counter = 0
_total_files = 0

def log(msg):
    """Print to console and write to log file (thread-safe)."""
    with _log_lock:
        print(msg, flush=True)
        if _log_file:
            _log_file.write(msg + "\n")
            _log_file.flush()

def start_log(target_key, domain):
    """Create a timestamped log file."""
    global _log_file
    LOGS_DIR.mkdir(exist_ok=True)
    timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
    log_path = LOGS_DIR / f"deploy-{target_key}-{timestamp}.log"
    _log_file = open(log_path, 'w')
    log(f"Deploy log: {log_path}")
    log(f"Started: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    log(f"Project: {domain}")
    log(f"Target: {target_key}")
    log("")
    return log_path

def end_log():
    """Close the log file."""
    global _log_file
    if _log_file:
        log(f"\nFinished: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        _log_file.close()
        _log_file = None

def load_config():
    if not CONFIG_PATH.exists():
        print("=" * 60)
        print("ERROR: deploy-config.json not found!")
        print("=" * 60)
        print()
        print("This FTP folder needs to be configured for this project.")
        print()
        print("Setup steps:")
        print("  1. Copy deploy-config.template.json to deploy-config.json")
        print("  2. Edit deploy-config.json and set:")
        print('     - "project_domain": "yourdomain.com"')
        print('     - Dev path: "/dev.yourdomain.com"')
        print('     - Live path: "/yourdomain.com"')
        print("  3. Run: python3 deploy-cli.py --verify")
        print()
        sys.exit(1)

    with open(CONFIG_PATH) as f:
        return json.load(f)

def check_project_domain(config):
    """Verify project_domain is set and return it"""
    domain = config.get("project_domain")
    if not domain:
        print("=" * 60)
        print("ERROR: project_domain not configured!")
        print("=" * 60)
        print()
        print("Edit deploy-config.json and set project_domain to your domain.")
        print('Example: "project_domain": "example.com"')
        print()
        print("Then run: python3 deploy-cli.py --verify")
        print()
        sys.exit(1)
    return domain

def load_password():
    """Load saved password if exists"""
    try:
        if CREDS_PATH.exists():
            with open(CREDS_PATH) as f:
                return json.load(f).get("password", "")
    except:
        pass
    return ""

def save_password(password):
    """Save password to file"""
    with open(CREDS_PATH, 'w') as f:
        json.dump({"password": password}, f)
    os.chmod(CREDS_PATH, 0o600)
    print("  Password saved for next time.")

def list_remote_root(ftp, remote_path):
    """List files and folders at the root of remote_path"""
    items = []
    try:
        ftp.cwd(remote_path)
        lines = []
        ftp.retrlines('LIST', lines.append)

        for line in lines:
            parts = line.split()
            if len(parts) >= 9:
                name = ' '.join(parts[8:])
                is_dir = line.startswith('d')
                items.append((name, is_dir))
            elif len(parts) >= 1:
                items.append((parts[-1], False))
    except Exception as e:
        return None, str(e)

    return items, None

def verify_remote_servers(config, password):
    """Connect to both dev and live and show root contents"""
    domain = check_project_domain(config)

    print()
    print("=" * 60)
    print(f"VERIFYING FTP CONFIGURATION FOR: {domain}")
    print("=" * 60)
    print()

    for target_key in ['dev', 'live']:
        target = config['targets'][target_key]
        print(f"[{target['name'].upper()}]")
        print(f"  Host: {target['host']}")
        print(f"  Path: {target['remote_path']}")
        print()

        try:
            ftp = ftplib.FTP_TLS(target['host'], timeout=30)
            ftp.login(target['user'], password)
            ftp.prot_p()

            items, error = list_remote_root(ftp, target['remote_path'])

            if error:
                print(f"  ERROR: {error}")
            elif not items:
                print("  (empty folder)")
            else:
                print("  Root contents:")
                for name, is_dir in sorted(items):
                    icon = "[DIR]" if is_dir else "     "
                    print(f"    {icon} {name}")

            ftp.quit()

        except Exception as e:
            print(f"  Connection ERROR: {e}")

        print()

    print("=" * 60)
    print("Verify the folders above are correct for this project.")
    print("If wrong, edit deploy-config.json and run --verify again.")
    print("=" * 60)

def make_ftp_connection(host, user, password):
    """Create a new FTP_TLS connection."""
    ftp = ftplib.FTP_TLS(host, timeout=30)
    ftp.login(user, password)
    ftp.prot_p()
    return ftp

def collect_files(local_dir, remote_dir, excludes):
    """Walk the local directory and collect all files and directories to process."""
    files = []
    dirs = []

    for item in sorted(local_dir.iterdir()):
        if item.name in excludes:
            continue

        remote_path = f"{remote_dir}/{item.name}"

        if item.is_file():
            files.append((item, remote_path))
        elif item.is_dir():
            dirs.append((item, remote_path))
            sub_files, sub_dirs = collect_files(item, remote_path, excludes)
            files.extend(sub_files)
            dirs.extend(sub_dirs)

    return files, dirs

def collect_only_files(local_dir, remote_dir, only_names):
    """Find specific files in local_dir by name or relative path."""
    files = []
    not_found = []

    for name in only_names:
        # Search for the file in dist/
        target = local_dir / name
        if target.is_file():
            remote_path = f"{remote_dir}/{name}"
            files.append((target, remote_path))
        else:
            # Try searching recursively by filename
            matches = list(local_dir.rglob(name))
            if matches:
                for match in matches:
                    rel = match.relative_to(local_dir)
                    remote_path = f"{remote_dir}/{rel}"
                    files.append((match, remote_path))
            else:
                not_found.append(name)

    return files, not_found

def ensure_remote_dirs(ftp, dirs):
    """Create all remote directories (must be sequential on one connection)."""
    for local_path, remote_path in dirs:
        try:
            ftp.mkd(remote_path)
        except:
            pass  # Already exists

def upload_single_file(local_path, remote_path, host, user, password):
    """Upload a single file using its own FTP connection from the pool."""
    global _upload_counter
    ftp = None
    try:
        ftp = make_ftp_connection(host, user, password)
        with open(local_path, 'rb') as f:
            ftp.storbinary(f'STOR {remote_path}', f)
        # Ensure web server can read the file
        try:
            ftp.sendcmd(f'SITE CHMOD 644 {remote_path}')
        except:
            pass
        ftp.quit()

        with _counter_lock:
            _upload_counter += 1
            count = _upload_counter

        log(f"  [{count}/{_total_files}] {local_path.name}")
        return True
    except Exception as e:
        log(f"  ERROR uploading {local_path.name}: {e}")
        if ftp:
            try:
                ftp.quit()
            except:
                pass
        return False

def main():
    global _upload_counter, _total_files

    # Parse args
    args = sys.argv[1:]

    if not args or args[0] in ['-h', '--help', 'help']:
        print("\nDeploy CLI - Upload dist/ to server via FTP")
        print(f"  Uses {MAX_CONNECTIONS} concurrent connections for fast uploads")
        print("\nUsage:")
        print("  python3 deploy-cli.py dev              Deploy all files to dev site")
        print("  python3 deploy-cli.py live             Deploy all files to live site")
        print("  python3 deploy-cli.py --verify         Check remote folders (RECOMMENDED FIRST)")
        print("  python3 deploy-cli.py dev --dry-run    Preview without uploading")
        print("  python3 deploy-cli.py live --exclude index.php   Skip index.php")
        print("  python3 deploy-cli.py dev --only about.php contact.php")
        print("                                         Deploy only specific files")
        print("  python3 deploy-cli.py dev --only insights/emotional-healing.php")
        print("                                         Paths relative to dist/ also work")
        print()
        return

    # Handle --verify command
    if args[0] == '--verify' or args[0] == '-v':
        config = load_config()
        saved_password = load_password()
        if saved_password:
            print(f"Using saved password (delete {CREDS_PATH.name} to reset)")
            password = saved_password
        else:
            password = getpass.getpass("FTP Password: ")
            save_it = input("Save password for next time? [y/N]: ").lower().strip()
            if save_it == 'y':
                save_password(password)
        verify_remote_servers(config, password)
        return

    target_key = args[0]
    dry_run = '--dry-run' in args or '-n' in args
    auto_yes = '--yes' in args or '-y' in args

    # Parse --exclude and --only arguments
    extra_excludes = []
    only_files = []
    i = 0
    while i < len(args):
        if args[i] == '--exclude' and i + 1 < len(args):
            extra_excludes.append(args[i + 1])
            i += 2
        elif args[i] == '--only':
            i += 1
            while i < len(args) and not args[i].startswith('-'):
                only_files.append(args[i])
                i += 1
        else:
            i += 1

    # Load config
    config = load_config()

    # Verify project_domain is set
    domain = check_project_domain(config)

    if target_key not in config['targets']:
        print(f"Error: Unknown target '{target_key}'. Use 'dev' or 'live'.")
        return 1

    target = config['targets'][target_key]
    local_path = (SCRIPT_DIR / config['local_path']).resolve()
    excludes = config.get('default_excludes', []) + extra_excludes

    # Check local path
    if not local_path.exists():
        print(f"Error: Local path not found: {local_path}")
        print("Run 'npm run build' first.")
        return 1

    # Get password
    saved_password = load_password()
    if saved_password:
        print(f"Using saved password (delete {CREDS_PATH.name} to reset)")
        password = saved_password
    else:
        password = getpass.getpass("FTP Password: ")
        save_it = input("Save password for next time? [y/N]: ").lower().strip()
        if save_it == 'y':
            save_password(password)

    # Start log file
    log_path = start_log(target_key, domain)
    start_time = time.time()

    # Confirm
    log("")
    log("=" * 50)
    log(f"{'DRY RUN - ' if dry_run else ''}DEPLOY TO: {target['name']}")
    log("=" * 50)
    log(f"Project: {domain}")
    log(f"Local:   {local_path}")
    log(f"Remote:  {target['remote_path']}")
    log(f"Host:    {target['host']}")
    if only_files:
        log(f"Only: {', '.join(only_files)}")
    if excludes:
        log(f"Excluding: {', '.join(excludes)}")
    log("")

    if not dry_run and not auto_yes:
        confirm = input(f"Deploy to {target['name']}? [y/N]: ").lower().strip()
        if confirm != 'y':
            log("Cancelled.")
            end_log()
            return

    try:
        # Step 1: Collect files
        if only_files:
            log("Finding specified files...")
            files, not_found = collect_only_files(local_path, target['remote_path'], only_files)
            dirs = []
            if not_found:
                log(f"  WARNING: Not found in dist/: {', '.join(not_found)}")
            if not files:
                log("No matching files found. Nothing to deploy.")
                end_log()
                return
        else:
            log("Scanning files...")
            files, dirs = collect_files(local_path, target['remote_path'], excludes)
        _total_files = len(files)
        _upload_counter = 0
        log(f"Found {_total_files} files in {len(dirs)} directories")
        log("")

        if dry_run:
            for local_file, remote_file in files:
                log(f"  Would upload: {local_file.name}")
            log("")
            log("=" * 50)
            log("DRY RUN COMPLETE")
            log(f"Would upload: {_total_files} files")
            log("=" * 50)
            end_log()
            return

        # Step 2: Create all directories first (single connection)
        log("Creating directories...")
        ftp = make_ftp_connection(target['host'], target['user'], password)
        ensure_remote_dirs(ftp, dirs)
        ftp.quit()
        log(f"Directories ready ({len(dirs)} checked)")
        log("")

        # Step 3: Upload files concurrently
        log(f"Uploading {_total_files} files ({MAX_CONNECTIONS} concurrent connections)...")
        log("")

        failed = 0
        with ThreadPoolExecutor(max_workers=MAX_CONNECTIONS) as executor:
            futures = {
                executor.submit(
                    upload_single_file,
                    local_file, remote_file,
                    target['host'], target['user'], password
                ): local_file
                for local_file, remote_file in files
            }

            for future in as_completed(futures):
                if not future.result():
                    failed += 1

        elapsed = time.time() - start_time
        log("")
        log("=" * 50)
        log("DEPLOY COMPLETE!")
        log(f"Uploaded: {_total_files - failed} files")
        if failed:
            log(f"Failed: {failed} files")
        log(f"Time: {int(elapsed)}s")
        log("=" * 50)

    except Exception as e:
        log(f"\nError: {e}")
        end_log()
        return 1

    end_log()

if __name__ == "__main__":
    sys.exit(main() or 0)
