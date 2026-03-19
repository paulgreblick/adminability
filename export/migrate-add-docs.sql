-- Migration: Add Knowledge Base (Docs) Feature
-- Safe to run multiple times (uses IF NOT EXISTS)

-- Create doc_categories table
CREATE TABLE IF NOT EXISTS doc_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    description VARCHAR(255),
    icon VARCHAR(50) DEFAULT 'folder',
    color VARCHAR(20) DEFAULT 'gray',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create docs table
CREATE TABLE IF NOT EXISTS docs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255),
    content LONGTEXT,
    doc_type ENUM('reference', 'process', 'workflow', 'guide') DEFAULT 'reference',
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    sort_order INT DEFAULT 0,
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES doc_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES docs(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add docs permissions (ignore if already exist)
INSERT IGNORE INTO permissions (name, description) VALUES
('docs.view', 'View documents'),
('docs.create', 'Create documents'),
('docs.edit', 'Edit documents'),
('docs.delete', 'Delete documents');

-- Grant docs permissions to admin role (role_id = 1)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE name LIKE 'docs.%';

-- Add default categories
INSERT IGNORE INTO doc_categories (name, slug, icon, color, sort_order) VALUES
('Reference', 'reference', 'book', 'blue', 1),
('Processes', 'processes', 'list', 'green', 2),
('Workflows', 'workflows', 'flow', 'purple', 3),
('Guides', 'guides', 'map', 'orange', 4);
