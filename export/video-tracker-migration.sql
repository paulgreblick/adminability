-- Video Tracker Migration - Upgrade to 15-step workflow
-- Run this in phpMyAdmin AFTER the original video-tracker-tables.sql
-- This adds the enhanced workflow steps and pre-populates categories

-- =====================================================
-- STEP 1: Add new workflow columns to videos table
-- =====================================================

-- WRITING PHASE (4 steps)
ALTER TABLE videos ADD COLUMN step_research ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER folder_link;
ALTER TABLE videos ADD COLUMN step_first_draft ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_research;
ALTER TABLE videos ADD COLUMN step_review ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_first_draft;
ALTER TABLE videos ADD COLUMN step_final_script ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_review;

-- PRODUCTION PHASE (5 steps)
ALTER TABLE videos ADD COLUMN step_slides ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_final_script;
ALTER TABLE videos ADD COLUMN step_audio_record ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_slides;
ALTER TABLE videos ADD COLUMN step_audio_edit ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_audio_record;
ALTER TABLE videos ADD COLUMN step_video_compile ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_audio_edit;
ALTER TABLE videos ADD COLUMN step_video_edit ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_video_compile;

-- PUBLISHING PHASE (6 steps)
ALTER TABLE videos ADD COLUMN step_thumbnail ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_video_edit;
ALTER TABLE videos ADD COLUMN step_seo_title ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_thumbnail;
ALTER TABLE videos ADD COLUMN step_description ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_seo_title;
ALTER TABLE videos ADD COLUMN step_tags ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_description;
ALTER TABLE videos ADD COLUMN step_upload ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_tags;
ALTER TABLE videos ADD COLUMN step_publish ENUM('not_started', 'in_progress', 'complete') DEFAULT 'not_started' AFTER step_upload;

-- Add YouTube URL field
ALTER TABLE videos ADD COLUMN youtube_url VARCHAR(255) DEFAULT NULL AFTER step_publish;
ALTER TABLE videos ADD COLUMN published_at DATETIME DEFAULT NULL AFTER youtube_url;

-- =====================================================
-- STEP 2: Migrate data from old columns to new columns
-- =====================================================

-- Map old stage_script to new step_final_script
UPDATE videos SET step_final_script = stage_script WHERE stage_script IS NOT NULL;

-- Map old stage_ppt to new step_slides
UPDATE videos SET step_slides = stage_ppt WHERE stage_ppt IS NOT NULL;

-- Map old stage_audio to both audio steps (if complete, both are complete)
UPDATE videos SET step_audio_record = stage_audio, step_audio_edit = stage_audio WHERE stage_audio IS NOT NULL;

-- Map old stage_video to both video steps
UPDATE videos SET step_video_compile = stage_video, step_video_edit = stage_video WHERE stage_video IS NOT NULL;

-- Map old stage_thumbnail to new step_thumbnail
UPDATE videos SET step_thumbnail = stage_thumbnail WHERE stage_thumbnail IS NOT NULL;

-- Map old stage_description to new step_description
UPDATE videos SET step_description = stage_description WHERE stage_description IS NOT NULL;

-- Map old stage_upload to new step_upload
UPDATE videos SET step_upload = stage_upload WHERE stage_upload IS NOT NULL;

-- =====================================================
-- STEP 3: Remove old columns
-- =====================================================

ALTER TABLE videos DROP COLUMN stage_script;
ALTER TABLE videos DROP COLUMN stage_ppt;
ALTER TABLE videos DROP COLUMN stage_audio;
ALTER TABLE videos DROP COLUMN stage_video;
ALTER TABLE videos DROP COLUMN stage_thumbnail;
ALTER TABLE videos DROP COLUMN stage_description;
ALTER TABLE videos DROP COLUMN stage_upload;

-- =====================================================
-- STEP 4: Add sort_order to categories if missing
-- =====================================================

-- This should already exist but just in case
-- ALTER TABLE video_categories ADD COLUMN sort_order INT DEFAULT 0 AFTER description;

-- =====================================================
-- STEP 5: Pre-populate affirmation categories
-- =====================================================

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
