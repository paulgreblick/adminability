-- Notes System Upgrade
-- Run this in phpMyAdmin on BigScoots
-- Adds projects, hierarchy, types, pinning, better author tracking

-- =====================================================
-- STEP 1: Create note_projects table
-- =====================================================
CREATE TABLE IF NOT EXISTS note_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) DEFAULT 'gray',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default projects (only if empty)
INSERT INTO note_projects (name, color, sort_order)
SELECT * FROM (
    SELECT 'General' as name, 'gray' as color, 1 as sort_order UNION ALL
    SELECT 'Affirmations Project', 'purple', 2 UNION ALL
    SELECT 'Website', 'blue', 3 UNION ALL
    SELECT 'Ideas', 'yellow', 4
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM note_projects LIMIT 1);

-- =====================================================
-- STEP 2: Add new columns to notes table
-- =====================================================

-- Add project_id column
ALTER TABLE notes ADD COLUMN project_id INT DEFAULT 1 AFTER id;

-- Add parent_id for threaded replies
ALTER TABLE notes ADD COLUMN parent_id INT DEFAULT NULL AFTER project_id;

-- Add type column (note, idea, task, question)
ALTER TABLE notes ADD COLUMN type ENUM('note', 'idea', 'task', 'question') DEFAULT 'note' AFTER content;

-- Add is_pinned column
ALTER TABLE notes ADD COLUMN is_pinned TINYINT(1) DEFAULT 0 AFTER priority;

-- Add updated_by column
ALTER TABLE notes ADD COLUMN updated_by INT DEFAULT NULL AFTER created_by;

-- =====================================================
-- STEP 3: Add foreign keys and indexes
-- =====================================================

-- Foreign keys (ignore errors if they already exist)
ALTER TABLE notes ADD FOREIGN KEY (project_id) REFERENCES note_projects(id) ON DELETE SET NULL;
ALTER TABLE notes ADD FOREIGN KEY (parent_id) REFERENCES notes(id) ON DELETE CASCADE;
ALTER TABLE notes ADD FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;

-- Indexes for performance
CREATE INDEX idx_notes_project ON notes(project_id);
CREATE INDEX idx_notes_parent ON notes(parent_id);

-- =====================================================
-- Done! New features:
-- - Projects/Areas to organize notes
-- - Types: note, idea, task, question
-- - Threaded replies (parent_id)
-- - Pin important notes
-- - Track who edited notes (updated_by)
-- =====================================================
