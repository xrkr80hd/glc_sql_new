-- Liberty Church Full cPanel MySQL Schema
-- Target: fresh MySQL/MariaDB database for the PHP/static site.
-- No Postgres, no Supabase, no stored procedures.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS live_streams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    embed_code TEXT,
    stream_title VARCHAR(255),
    title VARCHAR(255) NULL,
    embed_url VARCHAR(1024) NULL,
    youtube_video_id VARCHAR(32),
    service_type ENUM('youtube','facebook','twitch','custom') DEFAULT 'youtube',
    fallback_video_url VARCHAR(512) NULL,
    watch_cta_label VARCHAR(120) NOT NULL DEFAULT 'Watch Live Now',
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_livestreams_public_window (is_active, starts_at, ends_at, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category ENUM('main','youth','event','global') NOT NULL DEFAULT 'main',
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    image_url VARCHAR(512) NULL,
    image_alt VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_announcements_public_window (category, is_published, sort_order, starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcement_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    alt VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_announcement_photos_announcement
        FOREIGN KEY (announcement_id) REFERENCES announcements(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visit_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    visit_date DATE NULL,
    preferred_service VARCHAR(120) NULL,
    party_size VARCHAR(20) NULL,
    notes TEXT NULL,
    message TEXT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    INDEX idx_visit_submissions_submitted (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prayer_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    request TEXT NOT NULL,
    request_text TEXT NULL,
    share_permission TINYINT(1) NOT NULL DEFAULT 0,
    is_private TINYINT(1) NOT NULL DEFAULT 0,
    is_prayed TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('new','in_progress','prayed','closed') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    INDEX idx_prayer_status (is_prayed, created_at),
    INDEX idx_prayer_requests_status_submitted (status, submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sermons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    youtube_id VARCHAR(32) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    sermon_date DATE NULL,
    preached_on DATE NULL,
    pastor VARCHAR(255) NULL,
    series_name VARCHAR(255) NULL,
    scripture_reference VARCHAR(255) NULL,
    thumbnail_url VARCHAR(512) NULL,
    video_url VARCHAR(1024) NULL,
    duration VARCHAR(50) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sermons_public_preached (is_published, preached_on, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS youth_scripture (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    audience ENUM('main','youth') NOT NULL DEFAULT 'youth',
    title VARCHAR(255) NULL,
    scripture_text TEXT NOT NULL,
    scripture_reference VARCHAR(255) NOT NULL,
    devotional TEXT NOT NULL,
    devotional_text TEXT NULL,
    week_start DATE NULL,
    week_end DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_youth_scripture_public_week (audience, is_published, week_start, week_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS youth_albums (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    summary TEXT NULL,
    event_date DATE NULL,
    album_date DATE NULL,
    cover_media VARCHAR(512) NULL,
    cover_image_url VARCHAR(512) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_youth_albums_public (is_published, display_order, event_date, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS youth_media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    album_id INT UNSIGNED NOT NULL,
    media_type ENUM('image','video') NOT NULL DEFAULT 'image',
    media_filename VARCHAR(512) NULL,
    media_caption VARCHAR(255) NULL,
    media_url VARCHAR(512) NULL,
    title VARCHAR(255) NULL,
    description TEXT NULL,
    thumbnail_url VARCHAR(512) NULL,
    taken_on DATE NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_youth_media_album_public (album_id, is_published, display_order, taken_on, created_at),
    CONSTRAINT fk_youth_media_album
        FOREIGN KEY (album_id) REFERENCES youth_albums(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT INTO admin_users (username, password_hash, role)
VALUES ('admin', '$2y$10$bpBc0Ysp8wMZyGxsNoVCZORFuxKk8N7KpvyVzrfkKvXoYGgqrcjPa', 'pastor')
ON DUPLICATE KEY UPDATE username = username;

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
