-- Full Data Sync: Local to Live
-- Run this in phpMyAdmin on BigScoots AFTER running:
--   1. sync-to-live.sql (video tracker tables)
--   2. notes-upgrade.sql (notes table upgrades)
--
-- This syncs all your local data to live.
-- Generated: 2025-12-15

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- VIDEO TRACKER DATA
-- =====================================================

-- Clear and repopulate video data
TRUNCATE TABLE video_progress;
TRUNCATE TABLE videos;
TRUNCATE TABLE workflow_steps;
TRUNCATE TABLE video_categories;

-- Video Categories (1 category)
INSERT INTO video_categories (id, name, description, sort_order) VALUES
(1, 'Affirmations', 'Faceless YouTube affirmation videos', 0);

-- Workflow Steps (12 steps)
INSERT INTO workflow_steps (id, name, phase, sort_order) VALUES
(1, 'Script Draft', 'writing', 1),
(2, 'Script Final', 'writing', 2),
(3, 'Base Recording', 'audio', 3),
(4, 'Editing', 'audio', 4),
(5, 'PowerPoint Created', 'video', 5),
(6, 'PowerPoint Assembled', 'video', 6),
(7, 'Title Confirmed', 'publish', 7),
(8, 'Thumbnail Created', 'publish', 8),
(9, 'Description Created', 'publish', 9),
(10, 'IG Comments Created', 'publish', 10),
(11, 'Uploaded to YouTube', 'final', 11),
(12, 'Comments Pinned', 'final', 12);

-- Videos (16 videos)
INSERT INTO videos (id, category_id, title) VALUES
(1, 1, 'Manifestation'),
(2, 1, 'Stress'),
(3, 1, 'Overwhelm'),
(4, 1, 'Anxiety'),
(5, 1, 'Abundance'),
(6, 1, 'Happiness'),
(7, 1, 'Self Love'),
(8, 1, 'Morning Positive Energy'),
(9, 1, 'Money'),
(10, 1, 'Healing from the Past'),
(11, 1, 'Worry'),
(12, 1, 'Peace and Calm'),
(13, 1, 'Positive Life Changes'),
(14, 1, 'Success'),
(15, 1, 'Wealth'),
(16, 1, 'Health');

-- Video Progress (initialize all as not_started)
INSERT INTO video_progress (video_id, step_id, status)
SELECT v.id, ws.id, 'not_started'
FROM videos v CROSS JOIN workflow_steps ws;

-- =====================================================
-- NOTES DATA
-- =====================================================

-- Note Projects (4 projects)
TRUNCATE TABLE note_projects;
INSERT INTO note_projects (id, name, color, sort_order) VALUES
(1, 'General', 'gray', 1),
(2, 'Affirmations Project', 'purple', 2),
(3, 'Website', 'blue', 3),
(4, 'Ideas', 'yellow', 4);

-- Notes (preserve existing, add test notes)
-- Note: Using INSERT IGNORE so it won't fail if notes already exist
INSERT IGNORE INTO notes (id, project_id, parent_id, title, content, type, status, priority, is_pinned, created_by) VALUES
(2, 1, NULL, 'Test', 'Test', 'note', 'idea', 'normal', 0, 1),
(3, 1, NULL, 'Test', 'Test 123', 'task', 'idea', 'normal', 0, 1);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Done! All data synced.
-- =====================================================
