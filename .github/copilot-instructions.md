# Liberty Church Website - AI Coding Guide

## Architecture Overview

This is a **PHP-first church website** designed for maximum shared hosting compatibility. The codebase avoids Node.js/build tools in production, using pure PHP 8.1+ with MySQL for all dynamic functionality.

### Key Components

- **Frontend**: Static HTML files (`index.html`, `live.html`, etc.) with CSS/JS in `assets/`
- **Backend**: PHP-based admin system in `php/admin/` with role-based access control
- **API Layer**: JSON endpoints in `php/api/` proxied through web-accessible `api/` directory
- **Database**: MySQL with schema in `database/setup.sql`
- **Content**: Dynamic content via PHP includes (`includes/`) embedded in static HTML

## Critical Architecture Patterns

### 1. Dual API Structure

- Public APIs: `api/current-stream/index.php` → `php/api/current_stream.php`
- All API files in `api/` are simple proxies to actual logic in `php/api/`
- Use `php/api/_bootstrap.php` for shared API functionality

### 2. Role-Based Admin System

```php
// Always check permissions before admin operations
admin_require_login();
admin_require_role('pastor', 'administrator'); // Multiple roles allowed
```

Roles: `pastor` (full access), `administrator`, `music_minister`, `worship_team`, `youth_minister`, `media_team`, `sound_team`

### 3. Database Connection Pattern

```php
// Use the singleton PDO connection
$pdo = db(); // From php/config.php
// Always use prepared statements
$stmt = $pdo->prepare('SELECT * FROM table WHERE id = ?');
```

### 4. Live Streaming Integration

- Live status stored in `live_streams` table with `is_active` flag
- Frontend polls `/api/current-stream/` every 60 seconds
- YouTube API integration for sermon fetching (keys in `.env`)

## Development Workflows

### Local Setup

1. Ensure PHP 8.1+ with PDO MySQL extension
2. Import `database/setup.sql` to create schema + admin user
3. Update `php/config.php` with local database credentials
4. Set `uploads/` directory writable for media uploads
5. Copy `.env.example` to `.env` with YouTube API keys

### Testing with Production Data

**Critical**: Always test with live production data, not mock data. Export production MySQL and restore locally before testing any features.

### Admin Login

- URL: `/php/admin/login.php`
- Default: username `admin`, password from bcrypt hash in `database/setup.sql`
- Session management in `php/admin/bootstrap.php`

## Project-Specific Conventions

### File Organization

- Static pages: Root directory (`.html` files)
- PHP logic: `php/` directory (never web-accessible except `php/admin/`)
- Web-accessible APIs: `api/` directory (proxy to `php/api/`)
- Shared includes: `includes/` (for embedding in static HTML)

### Database Naming

- Tables use snake_case: `admin_users`, `live_streams`, `prayer_requests`
- Foreign keys follow pattern: `{table}_id`
- Boolean fields: `is_active`, `is_read`, `is_prayed`

### Security Patterns

```php
// CSRF protection for forms
$csrf_public = $_SESSION['csrf_public']; // Generated in includes/header.php

// File uploads (youth media)
const MAX_UPLOAD_BYTES = 75 * 1024 * 1024; // 75MB limit
```

### CSS/JS Integration

- Main styles: `assets/style.css`
- Live streaming: `assets/js/live-stream.js` + `assets/live-indicator.css`
- Youth section: `assets/js/youth.js`

## Integration Points

### YouTube API

- Sermon fetching: `YOUTUBE_SERMONS_API_KEY` in `.env`
- Live stream detection: `YOUTUBE_LIVE_API_KEY` in `.env`
- Channel ID: `YOUTUBE_CHANNEL_ID` in `.env`

### External Dependencies

- Google Fonts (Inter, Dancing Script)
- YouTube iframe embeds for sermons/live streaming
- No CDN dependencies - all assets self-hosted

### Email/Communications

- Contact forms submit to `php/api/` endpoints
- Prayer requests stored in database, not emailed directly
- Visit submissions tracked in admin for follow-up

## Debugging & Maintenance

### Common Troubleshooting

- Live stream not showing: Check `live_streams` table `is_active` flag and API response at `/api/current-stream/`
- Admin panel 403: Verify role migration (`musician` → `worship_team`)
- File uploads failing: Check `uploads/` directory permissions

### Legacy Code

- `glc_backend/`: Node.js implementation (reference only, not used in production)
- Any SQLite references are legacy - production uses MySQL only

### Emergency Access

- Database: Direct MySQL access with credentials in `php/config.php`
- Admin: Password reset via direct database update of `admin_users.password_hash`
