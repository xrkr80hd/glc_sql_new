-- Liberty Church Next-to-PHP MySQL Parity Migration
-- Target: MySQL 5.7+/MariaDB 10.3+ on cPanel-style hosting.
-- Run after database/setup.sql.
-- This translates the Supabase/Postgres app schema into regular MySQL tables.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

DELIMITER $$

DROP PROCEDURE IF EXISTS add_column_if_missing $$
CREATE PROCEDURE add_column_if_missing(
    IN table_name_in VARCHAR(64),
    IN column_name_in VARCHAR(64),
    IN column_definition_in TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = table_name_in
          AND COLUMN_NAME = column_name_in
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', table_name_in, '` ADD COLUMN `', column_name_in, '` ', column_definition_in);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS add_index_if_missing $$
CREATE PROCEDURE add_index_if_missing(
    IN table_name_in VARCHAR(64),
    IN index_name_in VARCHAR(64),
    IN index_columns_in TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = table_name_in
          AND INDEX_NAME = index_name_in
    ) THEN
        SET @ddl = CONCAT('CREATE INDEX `', index_name_in, '` ON `', table_name_in, '` (', index_columns_in, ')');
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

-- Existing table extensions.

CALL add_column_if_missing('announcements', 'starts_at', 'DATETIME NULL');
CALL add_column_if_missing('announcements', 'ends_at', 'DATETIME NULL');
CALL add_column_if_missing('announcements', 'image_url', 'VARCHAR(512) NULL');
CALL add_column_if_missing('announcements', 'image_alt', 'VARCHAR(255) NULL');
CALL add_index_if_missing('announcements', 'idx_announcements_public_window', 'category, is_published, sort_order, starts_at, ends_at');

UPDATE announcements
SET starts_at = COALESCE(starts_at, CAST(start_date AS DATETIME)),
    ends_at = COALESCE(ends_at, TIMESTAMP(end_date, '23:59:59'))
WHERE start_date IS NOT NULL OR end_date IS NOT NULL;

CALL add_column_if_missing('live_streams', 'title', 'VARCHAR(255) NULL');
CALL add_column_if_missing('live_streams', 'embed_url', 'VARCHAR(1024) NULL');
CALL add_column_if_missing('live_streams', 'fallback_video_url', 'VARCHAR(512) NULL');
CALL add_column_if_missing('live_streams', 'watch_cta_label', 'VARCHAR(120) NOT NULL DEFAULT ''Watch Live Now''');
CALL add_column_if_missing('live_streams', 'starts_at', 'DATETIME NULL');
CALL add_column_if_missing('live_streams', 'ends_at', 'DATETIME NULL');
CALL add_index_if_missing('live_streams', 'idx_livestreams_public_window', 'is_active, starts_at, ends_at, created_at');

UPDATE live_streams
SET title = COALESCE(title, stream_title)
WHERE stream_title IS NOT NULL;

CALL add_column_if_missing('visit_submissions', 'preferred_service', 'VARCHAR(120) NULL');
CALL add_column_if_missing('visit_submissions', 'message', 'TEXT NULL');
CALL add_column_if_missing('visit_submissions', 'submitted_at', 'DATETIME NULL');
CALL add_index_if_missing('visit_submissions', 'idx_visit_submissions_submitted', 'submitted_at');

UPDATE visit_submissions
SET message = COALESCE(message, notes),
    submitted_at = COALESCE(submitted_at, created_at)
WHERE notes IS NOT NULL OR created_at IS NOT NULL;

CALL add_column_if_missing('prayer_requests', 'request_text', 'TEXT NULL');
CALL add_column_if_missing('prayer_requests', 'is_private', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL add_column_if_missing('prayer_requests', 'status', 'ENUM(''new'',''in_progress'',''prayed'',''closed'') NOT NULL DEFAULT ''new''');
CALL add_column_if_missing('prayer_requests', 'submitted_at', 'DATETIME NULL');
CALL add_index_if_missing('prayer_requests', 'idx_prayer_requests_status_submitted', 'status, submitted_at');

UPDATE prayer_requests
SET request_text = COALESCE(request_text, request),
    status = CASE WHEN is_prayed = 1 THEN 'prayed' ELSE status END,
    submitted_at = COALESCE(submitted_at, created_at)
WHERE request IS NOT NULL OR created_at IS NOT NULL;

CALL add_column_if_missing('sermons', 'video_url', 'VARCHAR(1024) NULL');
CALL add_column_if_missing('sermons', 'preached_on', 'DATE NULL');
CALL add_column_if_missing('sermons', 'is_published', 'TINYINT(1) NOT NULL DEFAULT 1');
CALL add_column_if_missing('sermons', 'sort_order', 'INT NOT NULL DEFAULT 0');
CALL add_index_if_missing('sermons', 'idx_sermons_public_preached', 'is_published, preached_on, sort_order');

UPDATE sermons
SET preached_on = COALESCE(preached_on, sermon_date),
    video_url = COALESCE(video_url, CONCAT('https://www.youtube.com/watch?v=', youtube_id))
WHERE sermon_date IS NOT NULL OR youtube_id IS NOT NULL;

CALL add_column_if_missing('youth_scripture', 'audience', 'ENUM(''main'',''youth'') NOT NULL DEFAULT ''youth''');
CALL add_column_if_missing('youth_scripture', 'title', 'VARCHAR(255) NULL');
CALL add_column_if_missing('youth_scripture', 'week_start', 'DATE NULL');
CALL add_column_if_missing('youth_scripture', 'week_end', 'DATE NULL');
CALL add_column_if_missing('youth_scripture', 'is_published', 'TINYINT(1) NOT NULL DEFAULT 1');
CALL add_column_if_missing('youth_scripture', 'devotional_text', 'TEXT NULL');
CALL add_index_if_missing('youth_scripture', 'idx_youth_scripture_public_week', 'audience, is_published, week_start, week_end');

UPDATE youth_scripture
SET devotional_text = COALESCE(devotional_text, devotional),
    week_start = COALESCE(week_start, DATE(created_at)),
    week_end = COALESCE(week_end, DATE_ADD(DATE(created_at), INTERVAL 7 DAY))
WHERE devotional IS NOT NULL OR created_at IS NOT NULL;

CALL add_column_if_missing('youth_albums', 'album_date', 'DATE NULL');
CALL add_column_if_missing('youth_albums', 'cover_image_url', 'VARCHAR(512) NULL');
CALL add_index_if_missing('youth_albums', 'idx_youth_albums_public', 'is_published, display_order, event_date, created_at');

UPDATE youth_albums
SET album_date = COALESCE(album_date, event_date),
    cover_image_url = COALESCE(cover_image_url, cover_media)
WHERE event_date IS NOT NULL OR cover_media IS NOT NULL;

CALL add_column_if_missing('youth_media', 'title', 'VARCHAR(255) NULL');
CALL add_column_if_missing('youth_media', 'description', 'TEXT NULL');
CALL add_column_if_missing('youth_media', 'thumbnail_url', 'VARCHAR(512) NULL');
CALL add_column_if_missing('youth_media', 'taken_on', 'DATE NULL');
CALL add_column_if_missing('youth_media', 'is_published', 'TINYINT(1) NOT NULL DEFAULT 1');
CALL add_index_if_missing('youth_media', 'idx_youth_media_album_public', 'album_id, is_published, display_order, taken_on, created_at');

-- New public/content tables from the Supabase app.

CREATE TABLE IF NOT EXISTS ministries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    image_url VARCHAR(512) NULL,
    image_alt VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ministries_public (is_published, sort_order, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seasonal_features (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NULL,
    scripture_reference VARCHAR(255) NULL,
    scripture_text TEXT NULL,
    media_url VARCHAR(512) NULL,
    media_type ENUM('video','image') NOT NULL DEFAULT 'image',
    cta_label VARCHAR(120) NULL,
    cta_url VARCHAR(512) NULL,
    season_tag VARCHAR(120) NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    display_seconds INT NOT NULL DEFAULT 15,
    enable_audio TINYINT(1) NOT NULL DEFAULT 0,
    volume_percent INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_seasonal_features_public (is_active, starts_at, ends_at, sort_order, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS youth_banners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT NULL,
    image_url VARCHAR(512) NULL,
    cta_label VARCHAR(120) NULL,
    cta_url VARCHAR(512) NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_youth_banners_public (is_active, starts_at, ends_at, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS archived_sermons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    media_url VARCHAR(1024) NOT NULL,
    thumbnail_url VARCHAR(512) NULL,
    preached_on DATE NULL,
    speaker VARCHAR(255) NULL,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_archived_sermons_public (is_published, preached_on, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery_videos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    album_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    video_url VARCHAR(1024) NOT NULL,
    thumbnail_url VARCHAR(512) NULL,
    description TEXT NULL,
    recorded_on DATE NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gallery_videos_public (is_published, sort_order, recorded_on, created_at),
    INDEX idx_gallery_videos_album (album_id, is_published, sort_order),
    CONSTRAINT fk_gallery_videos_album
        FOREIGN KEY (album_id) REFERENCES youth_albums(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    platform_key VARCHAR(80) NOT NULL UNIQUE,
    label VARCHAR(120) NOT NULL,
    url VARCHAR(512) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_social_links_public (is_active, sort_order, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Team/member/admin tables translated from the Supabase member and role model.

CREATE TABLE IF NOT EXISTS team_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_team_roles_active (is_active, sort_order, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(120) NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL UNIQUE,
    phone VARCHAR(50) NULL,
    password_hash VARCHAR(255) NULL,
    profile_photo_url VARCHAR(512) NULL,
    notes TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_superuser TINYINT(1) NOT NULL DEFAULT 0,
    failed_sign_in_attempts INT NOT NULL DEFAULT 0,
    sign_in_lock_until DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_team_members_active (is_active, full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_member_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    is_role_admin TINYINT(1) NOT NULL DEFAULT 0,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_team_member_roles_member_role (member_id, role_id),
    INDEX idx_team_member_roles_member (member_id, role_id, assigned_at),
    INDEX idx_team_member_roles_role_admin (role_id, is_role_admin, assigned_at),
    CONSTRAINT fk_team_member_roles_member
        FOREIGN KEY (member_id) REFERENCES team_members(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_team_member_roles_role
        FOREIGN KEY (role_id) REFERENCES team_roles(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NULL,
    name VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    route VARCHAR(512) NULL,
    category ENUM('bug','ui','idea','other') NOT NULL DEFAULT 'other',
    severity ENUM('low','medium','high') NOT NULL DEFAULT 'low',
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_member_feedback_lookup (created_at, category, severity),
    INDEX idx_member_feedback_member (member_id, created_at),
    CONSTRAINT fk_member_feedback_member
        FOREIGN KEY (member_id) REFERENCES team_members(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_member_password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    request_ip VARCHAR(64) NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    INDEX idx_team_member_password_resets_member (member_id, requested_at),
    CONSTRAINT fk_team_member_password_resets_member
        FOREIGN KEY (member_id) REFERENCES team_members(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curriculum_library (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_url VARCHAR(512) NOT NULL,
    topic VARCHAR(160) NULL,
    starts_on DATE NULL,
    ends_on DATE NULL,
    notes TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_curriculum_library_role_dates (role_id, starts_on, ends_on, created_at),
    CONSTRAINT fk_curriculum_library_role
        FOREIGN KEY (role_id) REFERENCES team_roles(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_song_lists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    service_date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    songs_json JSON NULL,
    notes TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_service_song_lists_role_date (role_id, service_date, created_at),
    CONSTRAINT fk_service_song_lists_role
        FOREIGN KEY (role_id) REFERENCES team_roles(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ministry_order_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NULL,
    requested_by_member_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    request_details TEXT NOT NULL,
    needed_by_date DATE NULL,
    estimated_cost DECIMAL(12,2) NULL,
    status ENUM('new','reviewing','ordered','fulfilled','declined') NOT NULL DEFAULT 'new',
    pastor_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ministry_order_requests_status (status, role_id, needed_by_date, created_at),
    INDEX idx_ministry_order_requests_member (requested_by_member_id, created_at),
    CONSTRAINT fk_ministry_order_requests_role
        FOREIGN KEY (role_id) REFERENCES team_roles(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ministry_order_requests_member
        FOREIGN KEY (requested_by_member_id) REFERENCES team_members(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookkeeping_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    entry_type ENUM('offering','tithe','expense','adjustment','other') NOT NULL DEFAULT 'other',
    title VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    submitted_by_member_id INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bookkeeping_reports_lookup (report_date, entry_type, created_at),
    CONSTRAINT fk_bookkeeping_reports_member
        FOREIGN KEY (submitted_by_member_id) REFERENCES team_members(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Canonical role seeds.
INSERT INTO team_roles (role_key, name, description, sort_order, is_system, is_active)
VALUES
    ('superuser', 'Superuser', 'Full system access.', 0, 1, 1),
    ('pastor', 'Pastor', 'Pastoral administration and review access.', 10, 1, 1),
    ('media_team', 'Media Team', 'Media and communications access.', 20, 1, 1),
    ('worship_leader', 'Worship Leader', 'Worship planning leadership.', 30, 1, 1),
    ('worship_team', 'Worship Team', 'Worship team member access.', 40, 1, 1),
    ('foh_sound', 'FOH Sound', 'Front-of-house sound access.', 50, 1, 1),
    ('kids_church', 'Kids Church', 'Kids ministry access.', 60, 1, 1),
    ('childrens_church', 'Children''s Church', 'Children''s ministry access.', 70, 1, 1),
    ('bookkeeper', 'Bookkeeper', 'Bookkeeping report access.', 80, 1, 1),
    ('youth_minister', 'Youth Minister', 'Youth ministry leadership access.', 90, 1, 1),
    ('youth_minister_assistant', 'Youth Minister Assistant', 'Youth ministry assistant access.', 100, 1, 1),
    ('youth_ministry', 'Youth Ministry', 'Youth ministry team access.', 110, 1, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    sort_order = VALUES(sort_order),
    is_system = VALUES(is_system),
    is_active = VALUES(is_active);

DROP PROCEDURE IF EXISTS add_column_if_missing;
DROP PROCEDURE IF EXISTS add_index_if_missing;
