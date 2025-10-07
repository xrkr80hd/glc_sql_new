# Liberty Church Website – PHP Deployment Package

**Stack:** Pure PHP 8.1+ / MySQL / AJAX / HTML/CSS/JS  
**No Node.js, npm, or build tools required**

This site runs entirely on PHP and MySQL for maximum shared-hosting compatibility. The `glc_backend/` folder contains the legacy Node.js dashboard (archived for reference only) and is **not used in production**. All admin features have been converted to PHP under `php/admin/`.

## 🚀 Quick Start (Shared Host Friendly)

1. **Upload the repository contents** to your PHP-enabled web root (PHP 8.1+ recommended).
2. **Create the database** using the credentials provided by your host.
3. **Run** `database/setup.sql` against that database to provision tables and seed the admin record.
4. **Update credentials** in `php/config.php` (and `.env` for API keys) so they match the live MySQL instance.
5. **Verify file permissions** for `uploads/` (webserver must be able to write here for media).
6. Visit `https://your-domain.com/index.html` (public site) and `https://your-domain.com/php/admin/login.php` (admin console).

## 🛠️ Environment Setup

## ✅ Testing With Real Data

To guarantee every feature reflects what members see in production, always test with live (or exact mirrored) content:

- Export the current production MySQL data and restore it locally before running any checks.
- Populate `.env` with the same credentials the live site uses—no placeholders or mock values.
- Upload real announcement images, sermon thumbnails, and media assets during validation so layout, file sizes, and performance match production.
- When exercising email or external integrations, point them at the real service (or an officially provided staging endpoint) so the full round trip is verified.

If you must work offline, refresh your dataset from production immediately before testing to avoid stale results.

### Required Environment Variables (.env file)

```env
# YouTube API Configuration
YOUTUBE_CHANNEL_ID=UC3OOjkWTTRf0fKDK5Z2o93A
YOUTUBE_SERMONS_API_KEY=AIzaSyDfpimhe4hZo6DVA_fq9vfoqQykFLfZ3SE
YOUTUBE_LIVE_API_KEY=AIzaSyDipr0uXATERfvHM13Lwd3y1AOMOliKhpk

# Server Configuration  
PORT=4000
ADMIN_PASSWORD=Str0ngPrayer!2025

# MySQL Configuration (set exactly as production uses)
DB_TYPE=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=golibert2_liberty_church
DB_USER=golibert2_liberty_church_user
DB_PASSWORD=Liberty2025!
```

## 📁 File Structure

```text
liberty_church_deploy_final/
├── index.html              # Homepage
├── live.html               # Live streaming page
├── sermons.html            # Sermons archive
├── visit.html              # Visit information
├── give.html               # Giving page
├── prayer.html             # Prayer requests
├── youth.html              # Youth ministry
├── beliefs.html            # Beliefs page
├── assets/                 # CSS, JS, images, videos
├── includes/               # PHP includes rendered by public HTML pages
├── php/                    # Active PHP backend (PDO config, admin UI, APIs)
│   ├── api/                # JSON endpoints consumed by AJAX
│   └── config.php          # Production database bootstrap (PDO)
├── api/                    # Web-accessible entrypoints proxying php/api/* scripts
├── database/               # MySQL DDL used in production
├── uploads/                # Media uploaded through the admin (ensure writable)
├── glc_backend/            # Legacy Node.js implementation (kept for reference only)
└── .env                    # Environment configuration (API keys, DB credentials)
```

## 🎛️ Admin Access

Sign in at `https://your-domain.com/php/admin/login.php` using the administrator seeded in `database/setup.sql` (username `admin`, bcrypt hash provided in the seed). Change the password immediately after deployment by editing the user inside the portal.

Current modules inside the PHP admin console:

- **Dashboard** – Live stream status, sermon totals, unread visit/prayer counts, and recent activity.
- **Visit Submissions** – Review new guests, flip read/unread status, and purge records once followed up.
- **Prayer Requests** – View pastoral requests, mark them prayed/unprayed, or remove duplicates.
- **Manage Users** – Add admins, reset passwords, toggle account status, and assign ministry roles.

### Role Permissions

| Role | Access Summary |
| --- | --- |
| Pastor | Full control of every module (manage users, toggle visit/prayer states). |
| Administrator | Same operational access as Pastor, minus the "last pastor" safeguard. |
| Music Minister | Read-only access to visit & prayer queues. |
| Worship Team | Read-only access to visit submissions. |
| Youth Minister | Read-only access to visit & prayer queues. |
| Media Team | Read-only access to visit submissions. |
| Sound Team | Read-only access to visit submissions. |

> **Heads-up:** if your `admin_users` table still uses the legacy `musician` role value, migrate it once: `UPDATE admin_users SET role = 'worship_team' WHERE role = 'musician';`

## 🚨 Emergency Tools

If you are still migrating away from the historic Node tooling, the old emergency scripts remain in `glc_backend/`. They are no longer required once PHP endpoints are fully adopted.

## 🎯 Features Included

- ✅ **Smart Live Streaming** - Auto-detects when you're broadcasting
- ✅ **Beautiful Fallback Video** - Shows warm loop when offline  
- ✅ **Admin Dashboard & Pastoral Tools** - PHP-based console for visits, prayers, and user management
- ✅ **Auto Sermon Updates** - Pulls from YouTube automatically
- ✅ **Youth Ministry Section** - Photo galleries, announcements
- ✅ **Prayer Requests** - Secure submission system
- ✅ **Mobile Responsive** - Works on all devices
- ✅ **SEO Optimized** - Google-friendly structure

## 🔧 Troubleshooting

### Live Stream Not Showing?

1. Confirm YouTube API keys in `.env`
2. Load `/api/current-stream` in a browser to see the raw JSON status
3. Ensure the `live_streams` table has an active row

### Admin Panel Won't Load?

1. Confirm PHP sessions are enabled and writable
2. Double-check `php/config.php` credentials
3. Verify `php/admin/login.php` is deployed and your `admin_users` table has at least one active account (run the role migration note above if you still see `musician`).

### Database Issues?

- **MySQL**: Ensure database and user exist on host (see `database/setup.sql`)
- **SQLite**: No longer used in production (only referenced in legacy Node code)

## 📞 Support

- All delete functions included for content management
- Rick Roll protection activated 😄
- Emergency scripts ready for quick fixes

Ready to deploy! 🚀
