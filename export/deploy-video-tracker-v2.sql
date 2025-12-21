-- Video Tracker V2 - Redesigned Workflow
-- Based on partner's sketches: 5 phases, 12 steps
-- Run this in phpMyAdmin on BigScoots
--
-- This REPLACES the previous workflow steps with the new structure.
-- Safe to run on fresh install or existing install.

-- =====================================================
-- STEP 1: Create tables if they don't exist
-- =====================================================

CREATE TABLE IF NOT EXISTS video_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS videos (
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

-- =====================================================
-- STEP 2: Drop and recreate workflow_steps with new phases
-- =====================================================

-- First, drop video_progress (it references workflow_steps)
DROP TABLE IF EXISTS video_progress;

-- Drop old workflow_steps
DROP TABLE IF EXISTS workflow_steps;

-- Create new workflow_steps with 5 phases
CREATE TABLE workflow_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phase ENUM('writing', 'audio', 'video', 'publish', 'final') NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create video_progress table
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

-- =====================================================
-- STEP 3: Insert the 12 workflow steps (5 phases)
-- =====================================================

INSERT INTO workflow_steps (name, phase, sort_order) VALUES
-- Writing Phase (2 steps)
('Script Draft', 'writing', 1),
('Script Final', 'writing', 2),

-- Audio Phase (2 steps)
('Base Recording', 'audio', 3),
('Editing', 'audio', 4),

-- Video Phase (2 steps)
('PowerPoint Created', 'video', 5),
('PowerPoint Assembled', 'video', 6),

-- Publish Phase (4 steps)
('Title Confirmed', 'publish', 7),
('Thumbnail Created', 'publish', 8),
('Description Created', 'publish', 9),
('IG Comments Created', 'publish', 10),

-- Final Phase (2 steps)
('Uploaded to YouTube', 'final', 11),
('Comments Pinned', 'final', 12);

-- =====================================================
-- STEP 4: Initialize progress for existing videos
-- =====================================================

-- Add progress records for any existing videos
INSERT INTO video_progress (video_id, step_id, status)
SELECT v.id, ws.id, 'not_started'
FROM videos v
CROSS JOIN workflow_steps ws
WHERE NOT EXISTS (
    SELECT 1 FROM video_progress vp
    WHERE vp.video_id = v.id AND vp.step_id = ws.id
);

-- =====================================================
-- STEP 5: Add video permissions (safe - uses INSERT IGNORE)
-- =====================================================

INSERT IGNORE INTO permissions (name, description) VALUES
('videos.view', 'View video tracker'),
('videos.create', 'Create videos and categories'),
('videos.edit', 'Edit video progress'),
('videos.delete', 'Delete videos and categories');

-- Give all roles access to video permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE p.name LIKE 'videos.%';

-- =====================================================
-- STEP 6: Pre-populate affirmation categories (if empty)
-- =====================================================

INSERT INTO video_categories (name, description, sort_order)
SELECT * FROM (
    SELECT 'Morning Positive Energy' as name, 'Start your day with powerful positive affirmations' as description, 1 as sort_order UNION ALL
    SELECT 'Self Love', 'Build self-worth and embrace who you are', 2 UNION ALL
    SELECT 'Abundance', 'Attract abundance in all areas of life', 3 UNION ALL
    SELECT 'Manifestation', 'Manifest your dreams and desires into reality', 4 UNION ALL
    SELECT 'Money', 'Financial abundance and wealth consciousness', 5 UNION ALL
    SELECT 'Wealth', 'Build lasting wealth and prosperity', 6 UNION ALL
    SELECT 'Success', 'Achieve your goals and succeed in life', 7 UNION ALL
    SELECT 'Happiness', 'Cultivate joy and lasting happiness', 8 UNION ALL
    SELECT 'Health', 'Radiant health and physical wellbeing', 9 UNION ALL
    SELECT 'Peace and Calm', 'Inner peace and tranquility', 10 UNION ALL
    SELECT 'Stress', 'Release stress and find relief', 11 UNION ALL
    SELECT 'Anxiety', 'Overcome anxiety and find calm', 12 UNION ALL
    SELECT 'Worry', 'Let go of worry and embrace peace', 13 UNION ALL
    SELECT 'Overwhelm', 'Manage overwhelm and regain control', 14 UNION ALL
    SELECT 'Healing from the Past', 'Release the past and embrace healing', 15 UNION ALL
    SELECT 'Positive Life Changes', 'Embrace change and transformation', 16
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM video_categories LIMIT 1);

-- =====================================================
-- Done! New workflow structure:
--
-- WRITING:          Script Draft, Script Final
-- AUDIO:            Base Recording, Editing
-- VIDEO:            PowerPoint Created, PowerPoint Assembled
-- READY TO PUBLISH: Title Confirmed, Thumbnail Created, Description Created, IG Comments Created
-- PUBLISHED:        Uploaded to YouTube, Comments Pinned
-- =====================================================
