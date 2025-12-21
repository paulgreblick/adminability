-- Database Setup for paulgreb_adminability
-- Run this in phpMyAdmin on BigScoots

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);

CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT NOT NULL,
    status ENUM('idea', 'in_progress', 'done') DEFAULT 'idea',
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at)
);

CREATE TABLE ip_lockouts (
    ip_address VARCHAR(45) PRIMARY KEY,
    locked_until TIMESTAMP NOT NULL,
    attempt_count INT DEFAULT 0
);

-- Default roles
INSERT INTO roles (name, description) VALUES
('super_admin', 'Full access to all features'),
('admin', 'Administrative access'),
('editor', 'Can view and edit content'),
('viewer', 'Read-only access');

-- Default permissions
INSERT INTO permissions (name, description) VALUES
('users.view', 'View users'),
('users.create', 'Create users'),
('users.edit', 'Edit users'),
('users.delete', 'Delete users'),
('roles.view', 'View roles'),
('roles.manage', 'Manage roles and permissions'),
('dashboard.view', 'View dashboard'),
('notes.view', 'View notes'),
('notes.create', 'Create notes'),
('notes.edit', 'Edit notes'),
('notes.delete', 'Delete notes');

-- Assign permissions to roles
INSERT INTO role_permissions (role_id, permission_id) SELECT 1, id FROM permissions;
INSERT INTO role_permissions (role_id, permission_id) SELECT 2, id FROM permissions WHERE name IN ('users.view', 'users.create', 'users.edit', 'roles.view', 'dashboard.view', 'notes.view', 'notes.create', 'notes.edit', 'notes.delete');
INSERT INTO role_permissions (role_id, permission_id) SELECT 3, id FROM permissions WHERE name IN ('dashboard.view', 'notes.view', 'notes.create', 'notes.edit');
INSERT INTO role_permissions (role_id, permission_id) SELECT 4, id FROM permissions WHERE name IN ('dashboard.view', 'notes.view');

-- Admin user (paul@paulgreblick.com - same password as local)
INSERT INTO users (email, password_hash, name, role_id) VALUES
('paul@paulgreblick.com', '$2y$12$F/mgVio/tX9BEPzjcbqPJe1yjX9tHJfoL1Epu9YHgN1VWvBEZpzgC', 'Paul Greblick', 1);
