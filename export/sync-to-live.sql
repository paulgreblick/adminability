-- Sync Local Data to Live Server
-- Run this in phpMyAdmin on BigScoots
-- This will set up the correct table structure and populate data

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in correct order
DROP TABLE IF EXISTS video_progress;
DROP TABLE IF EXISTS videos;
DROP TABLE IF EXISTS workflow_steps;
DROP TABLE IF EXISTS video_categories;

-- Create video_categories
CREATE TABLE video_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create workflow_steps with 5 phases
CREATE TABLE workflow_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phase ENUM('writing', 'audio', 'video', 'publish', 'final') NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create videos (simplified - no step columns)
CREATE TABLE videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    notes TEXT,
    folder_link VARCHAR(500),
    youtube_url VARCHAR(255) DEFAULT NULL,
    published_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES video_categories(id) ON DELETE CASCADE
);

-- Create video_progress
CREATE TABLE video_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    step_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (step_id) REFERENCES workflow_steps(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_video_step (video_id, step_id)
);

SET FOREIGN_KEY_CHECKS = 1;

-- Insert category
INSERT INTO video_categories (id, name, description, sort_order) VALUES
(1, 'Affirmations', 'Faceless YouTube affirmation videos', 0);

-- Insert the 12 workflow steps
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

-- Insert the 16 videos
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

-- Initialize progress for all videos
INSERT INTO video_progress (video_id, step_id, status)
SELECT v.id, ws.id, 'not_started'
FROM videos v
CROSS JOIN workflow_steps ws;

-- Add video permissions
INSERT IGNORE INTO permissions (name, description) VALUES
('videos.view', 'View video tracker'),
('videos.create', 'Create videos and categories'),
('videos.edit', 'Edit video progress'),
('videos.delete', 'Delete videos and categories');

-- Give all roles access to video permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE p.name LIKE 'videos.%';
