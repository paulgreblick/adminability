-- Video Tracker Tables - Enhanced 15-step Workflow
-- Run this in phpMyAdmin on BigScoots after uploading files
-- For new installations only. If upgrading, use video-tracker-migration.sql instead.

-- Video Categories Table
CREATE TABLE IF NOT EXISTS video_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Videos Table with 15-step workflow
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    notes TEXT,
    folder_link VARCHAR(500),

    -- WRITING PHASE (4 steps)
    step_research ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_first_draft ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_review ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_final_script ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',

    -- PRODUCTION PHASE (5 steps)
    step_slides ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_audio_record ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_audio_edit ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_video_compile ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_video_edit ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',

    -- PUBLISHING PHASE (6 steps)
    step_thumbnail ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_seo_title ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_description ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_tags ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_upload ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',
    step_publish ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started',

    -- YouTube tracking
    youtube_url VARCHAR(255) DEFAULT NULL,
    published_at DATETIME DEFAULT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES video_categories(id) ON DELETE CASCADE
);

-- Add video permissions
INSERT IGNORE INTO permissions (name, description) VALUES
('videos.view', 'View video tracker'),
('videos.create', 'Create videos and categories'),
('videos.edit', 'Edit video progress'),
('videos.delete', 'Delete videos and categories');

-- Give all roles access to videos
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE p.name LIKE 'videos.%';

-- Pre-populate affirmation categories (16 topics)
INSERT INTO video_categories (name, description, sort_order) VALUES
('Morning Positive Energy', 'Start your day with powerful positive affirmations', 1),
('Self Love', 'Build self-worth and embrace who you are', 2),
('Abundance', 'Attract abundance in all areas of life', 3),
('Manifestation', 'Manifest your dreams and desires into reality', 4),
('Money', 'Financial abundance and wealth consciousness', 5),
('Wealth', 'Build lasting wealth and prosperity', 6),
('Success', 'Achieve your goals and succeed in life', 7),
('Happiness', 'Cultivate joy and lasting happiness', 8),
('Health', 'Radiant health and physical wellbeing', 9),
('Peace and Calm', 'Inner peace and tranquility', 10),
('Stress', 'Release stress and find relief', 11),
('Anxiety', 'Overcome anxiety and find calm', 12),
('Worry', 'Let go of worry and embrace peace', 13),
('Overwhelm', 'Manage overwhelm and regain control', 14),
('Healing from the Past', 'Release the past and embrace healing', 15),
('Positive Life Changes', 'Embrace change and transformation', 16);
