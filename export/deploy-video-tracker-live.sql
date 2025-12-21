-- Deploy Video Tracker to Live Server
-- Run this in phpMyAdmin on BigScoots
-- This adds the dynamic workflow system (workflow_steps + video_progress tables)
-- Safe to run - uses IF NOT EXISTS and INSERT IGNORE

-- =====================================================
-- STEP 1: Create workflow_steps table (dynamic work types)
-- =====================================================
CREATE TABLE IF NOT EXISTS workflow_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phase ENUM('writing', 'production', 'publishing') NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- STEP 2: Create video_categories table
-- =====================================================
CREATE TABLE IF NOT EXISTS video_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- STEP 3: Create videos table (simplified - no step columns)
-- =====================================================
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
-- STEP 4: Create video_progress table (tracks status per step)
-- =====================================================
CREATE TABLE IF NOT EXISTS video_progress (
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
-- STEP 5: Add video permissions (safe - uses INSERT IGNORE)
-- =====================================================
INSERT IGNORE INTO permissions (name, description) VALUES
('videos.view', 'View video tracker'),
('videos.create', 'Create videos and categories'),
('videos.edit', 'Edit video progress'),
('videos.delete', 'Delete videos and categories');

-- =====================================================
-- STEP 6: Give all roles access to video permissions
-- =====================================================
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE p.name LIKE 'videos.%';

-- =====================================================
-- STEP 7: Pre-populate default workflow steps (15 steps)
-- =====================================================
-- Only insert if table is empty (fresh install)
INSERT INTO workflow_steps (name, phase, sort_order)
SELECT * FROM (
    SELECT 'Research' as name, 'writing' as phase, 1 as sort_order UNION ALL
    SELECT 'First Draft', 'writing', 2 UNION ALL
    SELECT 'Review/Edit', 'writing', 3 UNION ALL
    SELECT 'Final Script', 'writing', 4 UNION ALL
    SELECT 'Slides', 'production', 5 UNION ALL
    SELECT 'Audio Record', 'production', 6 UNION ALL
    SELECT 'Audio Edit', 'production', 7 UNION ALL
    SELECT 'Video Compile', 'production', 8 UNION ALL
    SELECT 'Video Edit', 'production', 9 UNION ALL
    SELECT 'Thumbnail', 'publishing', 10 UNION ALL
    SELECT 'SEO Title', 'publishing', 11 UNION ALL
    SELECT 'Description', 'publishing', 12 UNION ALL
    SELECT 'Tags', 'publishing', 13 UNION ALL
    SELECT 'Upload', 'publishing', 14 UNION ALL
    SELECT 'Publish', 'publishing', 15
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM workflow_steps LIMIT 1);

-- =====================================================
-- STEP 8: Pre-populate affirmation categories (16 topics)
-- =====================================================
-- Only insert if table is empty (fresh install)
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
-- Done! The video tracker is now ready to use.
-- Your existing users table is untouched.
-- =====================================================
