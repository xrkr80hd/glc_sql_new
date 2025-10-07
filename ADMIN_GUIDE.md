# Liberty Church Admin System - Complete Guide

## Overview
The admin system is now **100% PHP-based** with all features from the Node.js dashboard fully converted and enhanced. No npm, no build tools, no Node.js required.

## 🔐 Login Credentials
- **URL**: `https://yourdomain.com/php/admin/login.php`
- **Username**: `admin`
- **Password**: `@LibertyChurch1065!`

## 📋 Features

### 1. Dashboard (`/php/admin/dashboard.php`)
Central hub showing:
- **Metrics**: Active announcements, open prayers, unread visits, youth albums, total media
- **Live Stream Status**: Current stream state with quick manage/view links
- **Youth Scripture**: Current week's scripture with update button
- **Recent Visits**: Last 5 submissions
- **Latest Prayers**: Last 5 prayer requests

### 2. Announcements (`/php/admin/announcements/`)
**Files**: `index.php`, `new.php`, `edit.php`, `delete.php`

**Features**:
- Four categories: Main, Youth, Event, Global
- Title, body (long text), optional date range (start/end dates)
- Display order control (lower numbers appear first)
- Publish toggle (show/hide without deleting)
- Color-coded category badges
- Edit/delete with CSRF protection

**Usage**:
1. Click "📢 Announcements" in sidebar
2. Click "Add Announcement" button
3. Select category, enter title/body, set dates (optional)
4. Set display order (0 = top, higher = lower)
5. Check "Published" to make visible
6. Click "Create Announcement"

### 3. Youth Scripture (`/php/admin/youth-scripture/`)
**Files**: `index.php`

**Features**:
- Single-page editor for weekly scripture + devotional
- Three fields: Scripture text, reference (e.g., "John 3:16"), devotional message
- Preview of current active scripture
- Automatic deactivation of old scripture when new one is added
- Only one scripture active at a time

**Usage**:
1. Click "📖 Youth Scripture" in sidebar
2. See current active scripture (if any)
3. Enter new scripture text, reference, and devotional
4. Click "Update Scripture"
5. Old scripture is automatically archived

### 4. Youth Albums (`/php/admin/youth-albums/`)
**Files**: `index.php`, `new.php`, `edit.php`, `delete.php`, `manage-media.php`

**Features**:
- Create photo albums with cover images
- Album details: Title, summary, event date, display order
- Upload multiple photos/videos per album (manage-media.php)
- Support for: JPG, PNG, WebP, GIF, MP4, MOV
- Max 75MB per file
- Grid view with cover previews
- Media count per album
- Publish toggle
- Delete albums (cascades to all media files)

**Usage - Create Album**:
1. Click "📸 Youth Albums" in sidebar
2. Click "Create Album"
3. Enter title, summary, event date
4. Upload cover photo (required)
5. Set display order, check "Published"
6. Click "Create Album"

**Usage - Add Photos**:
1. On album list, click "Manage Photos" on any album
2. Click "Choose Files" and select multiple images/videos
3. Click "Upload Files"
4. Photos appear in grid view
5. Click "Delete" under any photo to remove it

### 5. Live Stream (`/php/admin/stream/`)
**Files**: `index.php`

**Features**:
- Toggle stream on/off (visible on website or not)
- Set stream title (e.g., "Sunday Morning Worship")
- YouTube embed URL or HLS stream URL
- Service type dropdown: Sunday, Wednesday, Special Event
- Live preview iframe (for YouTube embeds)
- Recent stream history (last 10 streams)
- Automatic deactivation of old streams when new one goes live

**Usage**:
1. Click "📡 Live Stream" in sidebar
2. Check "Stream is currently live" to activate
3. Enter stream title and YouTube embed URL
   - Example: `https://www.youtube.com/embed/VIDEO_ID`
4. Select service type
5. Click "Update Stream"
6. Preview appears below

**Going Live**:
- When checked, stream badge on website changes to "🔴 LIVE NOW"
- Stream appears on `/live.html` page
- Uncheck when service ends

### 6. Prayer Requests (`/php/admin/prayers/`)
*Already working - no changes*
- View, filter, mark as prayed
- See private vs shared permission

### 7. Visit Submissions (`/php/admin/visits/`)
*Already working - no changes*
- View submissions, mark as read
- See email, visit date, message

### 8. Manage Users (`/php/admin/users/`)
*Already working - no changes*
- Create/edit admin accounts
- Role-based permissions

## 🗂️ File Upload System

### Upload Directory
- **Location**: `/uploads/` (created automatically)
- **Path Constant**: `UPLOAD_DIR` in `php/config.php`
- **Max Size**: 75MB per file (`MAX_UPLOAD_BYTES`)

### Supported Formats
- **Images**: JPG, JPEG, PNG, WebP, GIF
- **Videos**: MP4, MOV

### Storage
- Album covers: `uploads/youth_cover_XXXXX.jpg`
- Media files: `uploads/youth_media_XXXXX.jpg`
- Files are named with `uniqid()` to prevent conflicts

### Deletion
- Deleting an album removes:
  1. Album database record
  2. Cover image file from disk
  3. All associated media records (FK cascade)
  4. All media files from disk

## 🔒 Security Features

### CSRF Protection
- All POST forms require CSRF token
- Token generated per session
- Verified on every form submission
- Prevents cross-site request forgery attacks

### Authentication
- Password hashed with bcrypt (cost 12)
- Session-based login
- Auto-redirect to login if not authenticated
- Role-based access control (admin/editor/viewer)

### File Upload Validation
- Extension whitelist (no executable files)
- File size limits enforced
- Unique filenames prevent overwriting
- Upload directory outside web root (recommended for production)

## 📊 Database Tables

### Announcements
```sql
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('main', 'youth', 'event', 'global'),
    title VARCHAR(255),
    body TEXT,
    start_date DATE,
    end_date DATE,
    display_order INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Youth Scripture
```sql
CREATE TABLE youth_scripture (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scripture_text TEXT,
    scripture_reference VARCHAR(255),
    devotional TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Youth Albums
```sql
CREATE TABLE youth_albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    summary TEXT,
    cover_media VARCHAR(255),
    event_date DATE,
    display_order INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE youth_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT,
    media_type ENUM('image', 'video'),
    media_filename VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (album_id) REFERENCES youth_albums(id) ON DELETE CASCADE
);
```

### Live Streams
```sql
CREATE TABLE live_streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stream_title VARCHAR(255),
    stream_url VARCHAR(512),
    service_type ENUM('sunday', 'wednesday', 'special') DEFAULT 'sunday',
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 🎨 Admin UI

### Design System
- **Font**: Inter (Google Fonts)
- **Colors**: 
  - Background: `#f3f6f4`
  - Surface: `#ffffff`
  - Accent: `#1f8a4c` (Liberty green)
  - Text: `#16321f`
- **Sidebar**: Dark green `#0f2417`
- **Radius**: 14px for cards, 10px for buttons
- **Shadows**: Soft elevation shadows

### Navigation Sections
- **Content**: Announcements, Youth Scripture, Youth Albums
- **Live**: Live Stream control
- **Communications**: Visits, Prayers
- **System**: User Management

### Responsive
- Mobile-friendly grid layouts
- Sidebar collapses on narrow screens
- Touch-friendly button sizes

## 🚀 Deployment Checklist

### Pre-Deployment
- [ ] Upload all files to server via FTP/SFTP
- [ ] Import `database/setup.sql` via phpMyAdmin
- [ ] Update `php/config.php` with correct DB credentials
- [ ] Create `/uploads/` directory with write permissions (755 or 775)
- [ ] Test database connection with `db-check.php` (then delete it)

### Post-Deployment
- [ ] Visit `/php/admin/login.php`
- [ ] Login with admin/@LibertyChurch1065!
- [ ] Test creating an announcement
- [ ] Test uploading a youth album with photos
- [ ] Test toggling live stream
- [ ] Delete `db-check.php` if not already removed

### Production Hardening
- [ ] Move `uploads/` outside web root (update `UPLOAD_DIR`)
- [ ] Set `display_errors = Off` in `php.ini`
- [ ] Enable HTTPS (SSL certificate)
- [ ] Set strong `session.cookie_httponly = On`
- [ ] Consider adding IP whitelist for admin pages

## 🛠️ Common Tasks

### Adding a New Announcement
1. Admin → Announcements → Add Announcement
2. Category = Main (or Youth/Event/Global)
3. Title = "Fall Harvest Festival"
4. Body = Full description with dates, times, details
5. Start Date = Day announcement should appear
6. End Date = Day announcement should disappear (optional)
7. Display Order = 0 (top) to 999 (bottom)
8. Published = checked
9. Click "Create Announcement"

### Updating Weekly Youth Scripture
1. Admin → Youth Scripture
2. Scripture Text = Full verse text
3. Reference = "Philippians 4:13"
4. Devotional = Pastor's message/reflection
5. Click "Update Scripture"
6. Old scripture automatically archived

### Creating a Youth Album
1. Admin → Youth Albums → Create Album
2. Title = "Summer Camp 2024"
3. Summary = "Our amazing week at Camp Tejas!"
4. Event Date = 2024-06-15
5. Cover Photo = Upload best photo
6. Display Order = 0
7. Published = checked
8. Click "Create Album"
9. On album list, click "Manage Photos"
10. Upload multiple photos/videos
11. Delete any unwanted media

### Going Live with Stream
1. Admin → Live Stream
2. Check "Stream is currently live"
3. Stream Title = "Sunday Morning Service"
4. Stream URL = YouTube embed link
5. Service Type = Sunday Service
6. Click "Update Stream"
7. Visit `/live.html` to verify

### Taking Stream Offline
1. Admin → Live Stream
2. Uncheck "Stream is currently live"
3. Click "Update Stream"

## 📝 API Endpoints (for frontend)

### Get Active Announcements
```php
// /api/announcements.php (you'll need to create this)
SELECT * FROM announcements 
WHERE is_published = 1 
AND (start_date IS NULL OR start_date <= CURDATE())
AND (end_date IS NULL OR end_date >= CURDATE())
ORDER BY display_order ASC, created_at DESC
```

### Get Youth Scripture
```php
// /api/youth-scripture.php (you'll need to create this)
SELECT * FROM youth_scripture 
WHERE is_active = 1 
LIMIT 1
```

### Get Youth Albums
```php
// /api/youth-albums.php (you'll need to create this)
SELECT a.*, COUNT(m.id) as media_count
FROM youth_albums a
LEFT JOIN youth_media m ON a.id = m.album_id
WHERE a.is_published = 1
GROUP BY a.id
ORDER BY a.display_order ASC, a.created_at DESC
```

### Get Album Media
```php
// /api/album-media.php?album_id=X
SELECT * FROM youth_media 
WHERE album_id = ? 
ORDER BY display_order ASC, created_at DESC
```

### Get Live Stream
```php
// /api/live-stream.php (you'll need to create this)
SELECT * FROM live_streams 
WHERE is_active = 1 
LIMIT 1
```

## 🐛 Troubleshooting

### "Database connection failed"
- Check `DB_HOST` in `php/config.php` (should be 'localhost' not '127.0.0.1')
- Verify `DB_USER` and `DB_PASS` match cPanel MySQL credentials
- Ensure MySQL service is running

### "Upload failed"
- Check `/uploads/` directory exists
- Verify write permissions (755 or 775)
- Confirm file size under 75MB
- Check allowed extensions (JPG/PNG/WebP/MP4/MOV)

### "CSRF token mismatch"
- Clear browser cookies
- Logout and login again
- Check session cookie settings in `php/config.php`

### "Permission denied" on file upload
```bash
# SSH into server, run:
cd /path/to/site
chmod 755 uploads
chown www-data:www-data uploads  # or your web user
```

### Images not displaying
- Check file paths in database (should be relative: `youth_cover_12345.jpg`)
- Verify `/uploads/` is accessible via web browser
- Test: `https://yourdomain.com/uploads/filename.jpg`

## 🎓 Training Tips

### For Content Editors
1. **Announcements**: Add weekly, use date ranges to auto-hide old ones
2. **Youth Scripture**: Update every Sunday night for Monday morning
3. **Albums**: Create album first, then batch upload photos
4. **Stream**: Toggle on 15min before service, off after service ends

### For Administrators
1. **Regular Backups**: Export database weekly via phpMyAdmin
2. **Media Cleanup**: Periodically delete old albums to save space
3. **User Management**: Create editor accounts for trusted volunteers
4. **Security**: Change admin password regularly
5. **Monitoring**: Check dashboard metrics daily

## 📞 Support

### File Structure
```
php/
  config.php           # Database and constants
  admin/
    bootstrap.php      # Core admin functions
    layout.php         # Page wrapper functions
    dashboard.php      # Main dashboard
    login.php          # Login page
    logout.php         # Logout handler
    announcements/     # 4 files
    youth-scripture/   # 1 file
    youth-albums/      # 5 files
    stream/            # 1 file
    prayers/           # Existing
    visits/            # Existing
    users/             # Existing
```

### Key Files to Know
- **php/config.php**: All settings, DB connection, utility functions
- **php/admin/layout.php**: Navigation sidebar, page wrapper
- **php/admin/dashboard.php**: Metrics and quick links
- **assets/admin.css**: All admin styling

### Making Changes
1. **Add navigation link**: Edit `php/admin/layout.php` (line ~30)
2. **Add metric**: Edit `php/admin/dashboard.php` (line ~10)
3. **Change colors**: Edit `assets/admin.css` (line 1-15)
4. **Add upload directory**: Modify `UPLOAD_DIR` in `php/config.php`

## ✅ Feature Checklist

- [x] Dashboard with live metrics
- [x] Announcements CRUD (4 categories)
- [x] Youth Scripture editor
- [x] Youth Albums with cover images
- [x] Youth Albums media manager (multi-upload)
- [x] Live Stream control
- [x] Prayer Requests (existing)
- [x] Visit Submissions (existing)
- [x] User Management (existing)
- [x] Responsive design
- [x] CSRF protection
- [x] File upload validation
- [x] Organized navigation with sections
- [x] Role-based permissions
- [x] Session management
- [x] Flash messages
- [x] Date formatting helpers

---

**All features are now complete and ready for deployment!** 🎉

The admin system is fully functional with:
- 📢 Content management (announcements, scripture, albums)
- 📡 Live stream control
- 🙏 Communications (prayers, visits)
- 👥 User management
- 🔒 Security (CSRF, auth, validation)
- 🎨 Professional UI with organized navigation
- 📱 Mobile-responsive design

No Node.js, no npm, no build steps. Just PHP, MySQL, and standard web technologies.
