-- Migration: Convert Knowledge Base from Categories to Tags
-- Run this on both local and live databases

-- 1. Create tags table
CREATE TABLE IF NOT EXISTS doc_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL,
    color VARCHAR(20) DEFAULT 'gray',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (slug)
);

-- 2. Create pivot table for doc-tag relationships
CREATE TABLE IF NOT EXISTS doc_tag_map (
    doc_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (doc_id, tag_id),
    FOREIGN KEY (doc_id) REFERENCES docs(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES doc_tags(id) ON DELETE CASCADE
);

-- 3. Migrate existing categories to tags (preserve your data)
INSERT IGNORE INTO doc_tags (name, slug, color)
SELECT name, slug, COALESCE(color, 'gray') FROM doc_categories;

-- 4. Migrate existing doc-category relationships to doc-tag relationships
INSERT IGNORE INTO doc_tag_map (doc_id, tag_id)
SELECT d.id, t.id
FROM docs d
JOIN doc_categories c ON d.category_id = c.id
JOIN doc_tags t ON t.slug = c.slug;

-- 5. Remove category_id and doc_type from docs table
ALTER TABLE docs DROP FOREIGN KEY IF EXISTS docs_ibfk_1;
ALTER TABLE docs DROP COLUMN IF EXISTS category_id;
ALTER TABLE docs DROP COLUMN IF EXISTS doc_type;

-- 6. Drop old categories table (optional - uncomment when ready)
-- DROP TABLE IF EXISTS doc_categories;

-- Add some starter tags if none exist
INSERT IGNORE INTO doc_tags (name, slug, color) VALUES
('Reference', 'reference', 'blue'),
('Process', 'process', 'green'),
('Guide', 'guide', 'purple');
