-- Video Data Export
-- Run this in phpMyAdmin on BigScoots AFTER deploy-video-tracker-v2.sql
-- This adds the actual video records

-- Insert the category (if not exists)
INSERT IGNORE INTO video_categories (id, name, description, sort_order) VALUES
(1, 'Affirmations', 'Faceless YouTube affirmation videos', 0);

-- Insert the 16 videos
INSERT IGNORE INTO videos (id, category_id, title) VALUES
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

-- Initialize progress for all videos (all steps start as not_started)
INSERT IGNORE INTO video_progress (video_id, step_id, status)
SELECT v.id, ws.id, 'not_started'
FROM videos v
CROSS JOIN workflow_steps ws;
