#!/usr/bin/env python3
"""
Deploy Tool - PySide6 GUI for FTP deployment with safety features.
"""

import sys
import json
import os
import ftplib
from pathlib import Path
from datetime import datetime
from PySide6.QtWidgets import (
    QApplication, QMainWindow, QWidget, QVBoxLayout, QHBoxLayout,
    QLabel, QPushButton, QLineEdit, QTextEdit, QComboBox,
    QCheckBox, QGroupBox, QMessageBox, QProgressBar, QDialog,
    QFormLayout, QDialogButtonBox
)
from PySide6.QtCore import Qt, QThread, Signal
from PySide6.QtGui import QFont

SCRIPT_DIR = Path(__file__).parent
CONFIG_PATH = SCRIPT_DIR / "deploy-config.json"
CREDS_PATH = SCRIPT_DIR / "ftp-credentials.json"
LOG_PATH = SCRIPT_DIR / "deploy.log"

DEFAULT_CONFIG = {
    "targets": {
        "dev": {
            "name": "Dev Site",
            "host": "",
            "user": "",
            "remote_path": "",
            "description": "Development server"
        },
        "live": {
            "name": "Live Site",
            "host": "",
            "user": "",
            "remote_path": "",
            "description": "Production server"
        }
    },
    "local_path": "../dist",
    "default_excludes": [".DS_Store", "Thumbs.db", ".git"]
}


class SettingsDialog(QDialog):
    """Dialog for configuring FTP settings"""

    def __init__(self, config, parent=None):
        super().__init__(parent)
        self.setWindowTitle("FTP Settings")
        self.setMinimumWidth(500)
        self.config = config

        layout = QVBoxLayout(self)

        # FTP Host
        host_group = QGroupBox("FTP Server")
        host_layout = QFormLayout(host_group)

        self.host_input = QLineEdit()
        self.host_input.setText(config['targets']['dev'].get('host', ''))
        self.host_input.setPlaceholderText("ftp.example.com")
        host_layout.addRow("Host:", self.host_input)

        self.user_input = QLineEdit()
        self.user_input.setText(config['targets']['dev'].get('user', ''))
        self.user_input.setPlaceholderText("username")
        host_layout.addRow("Username:", self.user_input)

        layout.addWidget(host_group)

        # Dev path
        dev_group = QGroupBox("Dev Site")
        dev_layout = QFormLayout(dev_group)

        self.dev_path_input = QLineEdit()
        self.dev_path_input.setText(config['targets']['dev'].get('remote_path', ''))
        self.dev_path_input.setPlaceholderText("/dev.example.com")
        dev_layout.addRow("Remote Path:", self.dev_path_input)

        layout.addWidget(dev_group)

        # Live path
        live_group = QGroupBox("Live Site")
        live_layout = QFormLayout(live_group)

        self.live_path_input = QLineEdit()
        self.live_path_input.setText(config['targets']['live'].get('remote_path', ''))
        self.live_path_input.setPlaceholderText("/example.com")
        live_layout.addRow("Remote Path:", self.live_path_input)

        layout.addWidget(live_group)

        # Local path
        local_group = QGroupBox("Local")
        local_layout = QFormLayout(local_group)

        self.local_path_input = QLineEdit()
        self.local_path_input.setText(config.get('local_path', '../dist'))
        self.local_path_input.setPlaceholderText("../dist")
        local_layout.addRow("Build folder:", self.local_path_input)

        layout.addWidget(local_group)

        # Buttons
        buttons = QDialogButtonBox(QDialogButtonBox.Save | QDialogButtonBox.Cancel)
        buttons.accepted.connect(self.accept)
        buttons.rejected.connect(self.reject)
        layout.addWidget(buttons)

    def get_config(self):
        """Return updated config"""
        self.config['targets']['dev']['host'] = self.host_input.text()
        self.config['targets']['dev']['user'] = self.user_input.text()
        self.config['targets']['dev']['remote_path'] = self.dev_path_input.text()

        self.config['targets']['live']['host'] = self.host_input.text()
        self.config['targets']['live']['user'] = self.user_input.text()
        self.config['targets']['live']['remote_path'] = self.live_path_input.text()

        self.config['local_path'] = self.local_path_input.text()

        return self.config


class DeployWorker(QThread):
    """Background thread for FTP operations"""
    log = Signal(str)
    progress = Signal(int, int)  # (current, total)
    finished = Signal(bool, str)

    def __init__(self, target, local_path, password, excludes, dry_run):
        super().__init__()
        self.target = target
        self.local_path = local_path
        self.password = password
        self.excludes = excludes
        self.dry_run = dry_run
        self.upload_count = 0
        self.skip_count = 0
        self.total_files = 0
        self.log_lines = []

    def count_files(self, directory):
        """Count total files to upload (excluding excluded files)"""
        count = 0
        for item in directory.iterdir():
            if item.name in self.excludes:
                continue
            if item.is_file():
                count += 1
            elif item.is_dir():
                count += self.count_files(item)
        return count

    def log_msg(self, msg):
        """Log to both GUI and file buffer"""
        self.log.emit(msg)
        self.log_lines.append(msg)

    def write_log_file(self, success):
        """Write session to log file"""
        timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        with open(LOG_PATH, 'a') as f:
            f.write(f"\n{'=' * 60}\n")
            f.write(f"[{timestamp}] {'DRY RUN - ' if self.dry_run else ''}Deploy to {self.target['name']}\n")
            f.write(f"Status: {'SUCCESS' if success else 'FAILED'}\n")
            f.write(f"{'=' * 60}\n")
            for line in self.log_lines:
                f.write(line + "\n")

    def run(self):
        success = False
        try:
            self.log_msg(f"{'=' * 50}")
            self.log_msg(f"{'DRY RUN - ' if self.dry_run else ''}DEPLOYING TO: {self.target['name']}")
            self.log_msg(f"{'=' * 50}")
            self.log_msg(f"Local: {self.local_path}")
            self.log_msg(f"Remote: {self.target['remote_path']}")
            if self.excludes:
                self.log_msg(f"Excluding: {', '.join(self.excludes)}")
            self.log_msg("")

            # Count files first
            self.log_msg("Counting files...")
            self.total_files = self.count_files(self.local_path)
            self.log_msg(f"Found {self.total_files} files to upload")
            self.log_msg("")
            self.progress.emit(0, self.total_files)

            # Connect with TLS
            self.log_msg(f"Connecting to {self.target['host']} (TLS)...")
            ftp = ftplib.FTP_TLS(self.target['host'], timeout=30)
            ftp.login(self.target['user'], self.password)
            ftp.prot_p()  # Switch to secure data connection
            self.log_msg("Connected securely!")
            self.log_msg("")

            # Ensure remote dir exists
            self.ensure_remote_dir(ftp, self.target['remote_path'])

            # Upload
            self.upload_directory(ftp, self.local_path, self.target['remote_path'])

            ftp.quit()

            self.log_msg("")
            self.log_msg(f"{'=' * 50}")
            if self.dry_run:
                self.log_msg("DRY RUN COMPLETE")
                self.log_msg(f"Would upload: {self.upload_count} files")
            else:
                self.log_msg("DEPLOY COMPLETE!")
                self.log_msg(f"Uploaded: {self.upload_count} files")
            self.log_msg(f"Skipped: {self.skip_count} files")
            self.log_msg(f"{'=' * 50}")

            success = True
            self.finished.emit(True, "Complete!")

        except Exception as e:
            self.log_msg(f"\nERROR: {e}")
            self.finished.emit(False, str(e))

        finally:
            self.write_log_file(success)

    def ensure_remote_dir(self, ftp, path):
        dirs = path.split("/")
        current = ""
        for d in dirs:
            if not d:
                continue
            current += "/" + d
            try:
                ftp.cwd(current)
            except:
                try:
                    ftp.mkd(current)
                    ftp.cwd(current)
                    self.log_msg(f"  Created: {current}")
                except:
                    pass

    def upload_directory(self, ftp, local_dir, remote_dir):
        for item in sorted(local_dir.iterdir()):
            if item.name in self.excludes:
                self.log_msg(f"  SKIP: {item.name}")
                self.skip_count += 1
                continue

            remote_path = f"{remote_dir}/{item.name}"

            if item.is_file():
                if self.dry_run:
                    self.log_msg(f"  Would upload: {item.name}")
                else:
                    self.log_msg(f"  Uploading: {item.name}")
                    with open(item, 'rb') as f:
                        ftp.storbinary(f'STOR {remote_path}', f)
                    # Ensure web server can read the file
                    try:
                        ftp.sendcmd(f'SITE CHMOD 644 {remote_path}')
                    except:
                        pass
                self.upload_count += 1
                self.progress.emit(self.upload_count, self.total_files)

            elif item.is_dir():
                self.log_msg(f"  Directory: {item.name}/")
                if not self.dry_run:
                    try:
                        ftp.mkd(remote_path)
                    except:
                        pass
                self.upload_directory(ftp, item, remote_path)


class DeployApp(QMainWindow):
    def __init__(self, target_lock=None):
        super().__init__()
        self.target_lock = target_lock

        # Set window title based on target
        if target_lock == 'dev':
            self.setWindowTitle("Deploy to DEV")
        elif target_lock == 'live':
            self.setWindowTitle("Deploy to LIVE (Production)")
        else:
            self.setWindowTitle("Deploy Tool")

        self.setMinimumSize(600, 700)

        # Load config
        self.load_config()
        self.load_credentials()

        self.init_ui()
        self.worker = None

        # Auto-open settings if not configured
        if self.needs_setup():
            QMessageBox.information(
                self, "Welcome",
                "Welcome! Let's set up your FTP connection.\n\n"
                "You'll need your FTP host, username, and the remote paths for dev and live sites."
            )
            self.open_settings()

    def load_config(self):
        try:
            with open(CONFIG_PATH) as f:
                self.config = json.load(f)
        except FileNotFoundError:
            self.config = DEFAULT_CONFIG.copy()

    def save_config(self):
        with open(CONFIG_PATH, 'w') as f:
            json.dump(self.config, f, indent=4)

    def needs_setup(self):
        """Check if config needs initial setup"""
        dev = self.config['targets'].get('dev', {})
        return not dev.get('host') or not dev.get('remote_path')

    def open_settings(self):
        """Open settings dialog"""
        dialog = SettingsDialog(self.config, self)
        if dialog.exec() == QDialog.Accepted:
            self.config = dialog.get_config()
            self.save_config()
            self.refresh_targets()
            self.log_text.append("Settings saved!")

    def refresh_targets(self):
        """Refresh target dropdown after settings change"""
        self.target_combo.clear()
        for key, target in self.config['targets'].items():
            self.target_combo.addItem(f"{target['name']} - {target['description']}", key)

    def load_credentials(self):
        self.saved_password = ""
        try:
            if CREDS_PATH.exists():
                with open(CREDS_PATH) as f:
                    self.saved_password = json.load(f).get("password", "")
        except:
            pass

    def save_credentials(self, password):
        with open(CREDS_PATH, 'w') as f:
            json.dump({"password": password}, f)
        os.chmod(CREDS_PATH, 0o600)

    def init_ui(self):
        central = QWidget()
        self.setCentralWidget(central)
        layout = QVBoxLayout(central)
        layout.setSpacing(15)
        layout.setContentsMargins(20, 20, 20, 20)

        # Title row with settings button
        title_row = QHBoxLayout()

        title = QLabel("Deploy Tool")
        title.setFont(QFont("Helvetica", 24, QFont.Bold))
        title_row.addWidget(title)

        # DEV/LIVE badge next to title
        if self.target_lock:
            env_label = QLabel(self.target_lock.upper())
            env_label.setFont(QFont("Helvetica", 14, QFont.Bold))
            if self.target_lock == 'dev':
                env_label.setStyleSheet("""
                    background-color: #3b82f6;
                    color: white;
                    padding: 6px 16px;
                    border-radius: 4px;
                    margin-left: 10px;
                """)
            else:  # live
                env_label.setStyleSheet("""
                    background-color: #ef4444;
                    color: white;
                    padding: 6px 16px;
                    border-radius: 4px;
                    margin-left: 10px;
                """)
            title_row.addWidget(env_label)

        title_row.addStretch()

        settings_btn = QPushButton("Settings")
        settings_btn.clicked.connect(self.open_settings)
        title_row.addWidget(settings_btn)

        layout.addLayout(title_row)

        subtitle = QLabel("Upload dist/ to your server via FTP")
        subtitle.setStyleSheet("color: gray;")
        layout.addWidget(subtitle)

        # Target selection
        if self.target_lock:
            # Locked to specific target - show label instead of dropdown
            target = self.config['targets'][self.target_lock]
            target_group = QGroupBox(f"Target: {target['name']}")
            target_layout = QVBoxLayout(target_group)
            target_label = QLabel(f"{target['description']}\nPath: {target['remote_path']}")
            target_label.setStyleSheet("color: gray;")
            target_layout.addWidget(target_label)

            # Hidden combo for compatibility
            self.target_combo = QComboBox()
            self.target_combo.addItem(target['name'], self.target_lock)
            self.target_combo.setVisible(False)
            target_layout.addWidget(self.target_combo)
        else:
            target_group = QGroupBox("1. Select Target")
            target_layout = QVBoxLayout(target_group)

            self.target_combo = QComboBox()
            for key, target in self.config['targets'].items():
                self.target_combo.addItem(f"{target['name']} - {target['description']}", key)
            self.target_combo.currentIndexChanged.connect(self.on_target_change)
            target_layout.addWidget(self.target_combo)

        layout.addWidget(target_group)

        # Password
        pass_group = QGroupBox("2. FTP Password")
        pass_layout = QVBoxLayout(pass_group)

        pass_row = QHBoxLayout()
        self.password_input = QLineEdit()
        self.password_input.setEchoMode(QLineEdit.Password)
        self.password_input.setText(self.saved_password)
        self.password_input.setPlaceholderText("Enter FTP password")
        pass_row.addWidget(self.password_input)

        self.test_btn = QPushButton("Test Connection")
        self.test_btn.clicked.connect(self.test_connection)
        pass_row.addWidget(self.test_btn)
        pass_layout.addLayout(pass_row)

        self.remember_check = QCheckBox("Remember password (saved locally)")
        self.remember_check.setChecked(bool(self.saved_password))
        pass_layout.addWidget(self.remember_check)
        layout.addWidget(pass_group)

        # Exclude files
        exclude_group = QGroupBox("3. Exclude Files")
        exclude_layout = QVBoxLayout(exclude_group)

        self.exclude_checks = {}
        common_excludes = ["index.php", ".htaccess", "robots.txt", "config.php"]
        for filename in common_excludes:
            cb = QCheckBox(filename)
            self.exclude_checks[filename] = cb
            exclude_layout.addWidget(cb)
        layout.addWidget(exclude_group)

        # Options
        options_group = QGroupBox("4. Options")
        options_layout = QVBoxLayout(options_group)

        self.dry_run_check = QCheckBox("DRY RUN - Preview only, don't actually upload")
        self.dry_run_check.setChecked(True)
        options_layout.addWidget(self.dry_run_check)
        layout.addWidget(options_group)

        # Buttons
        btn_layout = QHBoxLayout()

        self.deploy_btn = QPushButton("Deploy")
        self.deploy_btn.setMinimumHeight(40)
        self.deploy_btn.setStyleSheet("font-weight: bold; font-size: 14px;")
        self.deploy_btn.clicked.connect(self.start_deploy)
        btn_layout.addWidget(self.deploy_btn)

        self.cancel_btn = QPushButton("Cancel")
        self.cancel_btn.setEnabled(False)
        btn_layout.addWidget(self.cancel_btn)

        layout.addLayout(btn_layout)

        # Progress
        progress_layout = QHBoxLayout()
        self.progress = QProgressBar()
        self.progress.setRange(0, 100)
        self.progress.setVisible(False)
        progress_layout.addWidget(self.progress)

        self.progress_label = QLabel("")
        self.progress_label.setStyleSheet("font-weight: bold; min-width: 120px;")
        self.progress_label.setVisible(False)
        progress_layout.addWidget(self.progress_label)

        layout.addLayout(progress_layout)

        # Log
        log_group = QGroupBox("Log")
        log_layout = QVBoxLayout(log_group)

        self.log_text = QTextEdit()
        self.log_text.setReadOnly(True)
        self.log_text.setFont(QFont("Monaco", 11))
        log_layout.addWidget(self.log_text)
        layout.addWidget(log_group)

        self.on_target_change()

    def on_target_change(self):
        key = self.target_combo.currentData()
        target = self.config['targets'].get(key)
        if target:
            self.log_text.append(f"Selected: {target['name']} → {target['remote_path']}")

    def get_excludes(self):
        excludes = list(self.config.get('default_excludes', []))
        for filename, cb in self.exclude_checks.items():
            if cb.isChecked():
                excludes.append(filename)
        return excludes

    def test_connection(self):
        if not self.password_input.text():
            QMessageBox.warning(self, "Warning", "Enter password first")
            return

        target_key = self.target_combo.currentData()
        target = self.config['targets'][target_key]

        self.log_text.clear()
        self.log_text.append(f"Testing connection to {target['host']}...")
        QApplication.processEvents()

        try:
            ftp = ftplib.FTP_TLS(target['host'], timeout=10)
            ftp.login(target['user'], self.password_input.text())
            ftp.prot_p()  # Secure data connection
            self.log_text.append(f"Connected securely (TLS)!")
            self.log_text.append(f"Server: {ftp.getwelcome()}")

            try:
                ftp.cwd(target['remote_path'])
                files = ftp.nlst()
                self.log_text.append(f"Remote path exists: {target['remote_path']}")
                self.log_text.append(f"Files: {len(files)} items")
            except:
                self.log_text.append(f"Remote path may not exist: {target['remote_path']}")

            ftp.quit()
            self.log_text.append("\nConnection test successful!")
            QMessageBox.information(self, "Success", "Connection successful!")

        except Exception as e:
            self.log_text.append(f"\nConnection failed: {e}")
            QMessageBox.critical(self, "Error", f"Connection failed:\n{e}")

    def start_deploy(self):
        if not self.password_input.text():
            QMessageBox.warning(self, "Warning", "Enter password first")
            return

        # Save password if requested
        if self.remember_check.isChecked():
            self.save_credentials(self.password_input.text())

        # Check local path
        local_path = (SCRIPT_DIR / self.config['local_path']).resolve()
        if not local_path.exists():
            QMessageBox.critical(self, "Error", f"Local path not found:\n{local_path}\n\nRun 'npm run build' first.")
            return

        target_key = self.target_combo.currentData()
        target = self.config['targets'][target_key]
        excludes = self.get_excludes()
        dry_run = self.dry_run_check.isChecked()

        # Confirm
        mode = "DRY RUN (preview only)" if dry_run else "LIVE UPLOAD"
        exclude_str = f"\nExcluding: {', '.join(excludes)}" if excludes else ""

        reply = QMessageBox.question(
            self, "Confirm Deploy",
            f"Target: {target['name']}\n"
            f"Remote: {target['remote_path']}\n"
            f"Mode: {mode}"
            f"{exclude_str}\n\n"
            f"Continue?",
            QMessageBox.Yes | QMessageBox.No
        )

        if reply != QMessageBox.Yes:
            return

        # Start deploy
        self.log_text.clear()
        self.deploy_btn.setEnabled(False)
        self.progress.setVisible(True)
        self.progress.setRange(0, 100)
        self.progress.setValue(0)
        self.progress_label.setVisible(True)
        self.progress_label.setText("Counting files...")

        self.worker = DeployWorker(
            target, local_path, self.password_input.text(), excludes, dry_run
        )
        self.worker.log.connect(self.log_text.append)
        self.worker.progress.connect(self.update_progress)
        self.worker.finished.connect(self.deploy_finished)
        self.worker.start()

    def update_progress(self, current, total):
        """Update progress bar and label"""
        if total > 0:
            percent = int((current / total) * 100)
            self.progress.setValue(percent)
            remaining = total - current
            self.progress_label.setText(f"{current}/{total} ({remaining} left)")

    def deploy_finished(self, success, message):
        self.deploy_btn.setEnabled(True)
        self.progress.setVisible(False)
        self.progress_label.setVisible(False)

        if success:
            QMessageBox.information(self, "Complete", message)
        else:
            QMessageBox.critical(self, "Error", message)


def main():
    app = QApplication(sys.argv)

    # Check for target argument (dev or live)
    target_lock = None
    if len(sys.argv) > 1 and sys.argv[1] in ['dev', 'live']:
        target_lock = sys.argv[1]

    window = DeployApp(target_lock=target_lock)
    window.show()
    sys.exit(app.exec())


if __name__ == "__main__":
    main()
