-- Migration: Add Knowledge Base / Docs Feature
-- Safe to run on live - creates NEW tables only, won't touch existing data
-- Run this in phpMyAdmin on BigScoots

-- =====================================================
-- STEP 1: Create doc_categories table (new table)
-- =====================================================
CREATE TABLE IF NOT EXISTS doc_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    icon VARCHAR(50) DEFAULT 'folder',
    color VARCHAR(20) DEFAULT 'gray',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slug (slug)
);

-- =====================================================
-- STEP 2: Create docs table (new table)
-- =====================================================
CREATE TABLE IF NOT EXISTS docs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content LONGTEXT,
    doc_type ENUM('reference', 'process', 'workflow', 'guide') DEFAULT 'reference',
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    sort_order INT DEFAULT 0,
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES doc_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES docs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_slug_category (category_id, slug)
);

-- Indexes for performance
CREATE INDEX idx_docs_category ON docs(category_id);
CREATE INDEX idx_docs_parent ON docs(parent_id);
CREATE INDEX idx_docs_status ON docs(status);

-- =====================================================
-- STEP 3: Insert default categories
-- =====================================================
INSERT IGNORE INTO doc_categories (id, name, slug, description, icon, color, sort_order) VALUES
(1, 'Reference', 'reference', 'Reference documentation and specs', 'book', 'blue', 1),
(2, 'Processes', 'processes', 'Step-by-step procedures and SOPs', 'clipboard', 'green', 2),
(3, 'Workflows', 'workflows', 'How to complete specific tasks', 'workflow', 'purple', 3),
(4, 'Guides', 'guides', 'Tutorials and how-to guides', 'lightbulb', 'yellow', 4);

-- =====================================================
-- STEP 4: Add docs permissions (uses INSERT IGNORE - safe to re-run)
-- =====================================================
INSERT IGNORE INTO permissions (name, description) VALUES
('docs.view', 'View documentation'),
('docs.create', 'Create documents'),
('docs.edit', 'Edit documents'),
('docs.delete', 'Delete documents');

-- =====================================================
-- STEP 5: Give all existing roles access to docs
-- =====================================================
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE p.name LIKE 'docs.%';

-- =====================================================
-- Done! Knowledge Base is ready to use.
-- =====================================================
