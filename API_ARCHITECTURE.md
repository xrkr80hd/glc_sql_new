# Liberty Church API & Frontend Integration Summary

## ✅ Current Architecture (Pure AJAX - No Node.js Required)

All frontend pages (`.html` files) use **JavaScript/AJAX** to fetch data from PHP API endpoints. No server-side PHP rendering in HTML files.

---

## API Endpoints Overview

### 1. **Visit Submissions** (`/api/visit/`)
- **Frontend:** `visit.html` + `index.html` (visit form)
- **Method:** POST
- **Payload:** `{ name, email, phone, date, party, notes }`
- **Backend:** `/php/api/visit.php`
- **Database:** Inserts into `visit_submissions` table
- **Admin View:** `/php/admin/visits/index.php`

### 2. **Prayer Requests** (`/api/prayer-request/`)
- **Frontend:** `prayer.html`
- **Method:** POST
- **Payload:** `{ name, email, request, sharePermission }`
- **Backend:** `/php/api/prayer_request.php`
- **Database:** Inserts into `prayer_requests` table
- **Admin View:** `/php/admin/prayers/index.php`

### 3. **Youth Content** (`/api/youth/`)
- **Frontend:** `youth.html` (via `assets/js/youth.js`)
- **Method:** GET
- **Response:**
  ```json
  {
    "scripture": {
      "id": 1,
      "scripture_text": "...",
      "scripture_reference": "Psalm 56:3",
      "devotional": "...",
      "updated_at": "..."
    },
    "announcements": [
      {
        "id": 2,
        "title": "...",
        "body": "...",
        "photos": [
          { "file_path": "...", "alt": "..." }
        ]
      }
    ],
    "albums": [
      {
        "id": 1,
        "title": "...",
        "media": [
          { "url": "...", "type": "image", "caption": "..." }
        ]
      }
    ]
  }
  ```
- **Backend:** `/php/api/youth.php`
- **Database:** Queries `youth_scripture`, `announcements` (category='youth'), `announcement_photos`, `youth_albums`, `youth_media`
- **Admin Views:** 
  - Scripture: `/php/admin/youth-scripture/index.php`
  - Albums: `/php/admin/youth-albums/index.php`
  - Announcements: `/php/admin/announcements/index.php` (category='youth')

### 4. **Main Announcements** (`/api/announcements-main/`)
- **Frontend:** `index.html` (via `assets/js/announcements-main.js`)
- **Method:** GET
- **Response:**
  ```json
  {
    "announcements": [
      {
        "id": 3,
        "title": "...",
        "body": "...",
        "photos": [
          { "file_path": "...", "alt": "..." }
        ]
      }
    ],
    "count": 1
  }
  ```
- **Backend:** `/php/api/announcements_main.php`
- **Database:** Queries `announcements` (category='main'), `announcement_photos`
- **Admin View:** `/php/admin/announcements/index.php` (category='main')

### 5. **Live Stream Status** (`/api/stream-status/`)
- **Frontend:** `live.html`, `live2.html`
- **Method:** GET
- **Response:**
  ```json
  {
    "is_live": true,
    "embed_code": "<iframe...>",
    "stream_title": "Sunday Service",
    "redirect_url": "/live2.html"
  }
  ```
- **Backend:** `/api/stream-status/index.php` (uses `/php/config.php` directly)
- **Database:** Queries `live_streams` table
- **Admin View:** `/php/admin/stream/index.php`

### 6. **Current Stream** (`/api/current-stream/`)
- **Frontend:** Used by live stream indicator
- **Method:** GET
- **Response:**
  ```json
  {
    "stream": {
      "id": 1,
      "stream_title": "...",
      "embed_code": "...",
      "youtube_video_id": "...",
      "is_active": true
    },
    "fallback": false,
    "timestamp": "2025-10-08T..."
  }
  ```
- **Backend:** `/php/api/current_stream.php`
- **Database:** Queries `live_streams` table

---

## How It Works (AJAX Flow)

### Example: Youth Page Scripture Display

1. **User visits** `youth.html`
2. **DOM loads** → JavaScript in `assets/js/youth.js` runs
3. **Fetch API call** → `GET /api/youth/`
4. **PHP processes** → `/php/api/youth.php` queries database
5. **JSON returned** → `{ scripture: {...}, announcements: [...], albums: [...] }`
6. **JavaScript renders** → Updates DOM with scripture text, devotional, announcements, photo galleries

### Example: Visit Form Submission

1. **User fills form** on `visit.html`
2. **Submit button clicked** → JavaScript intercepts
3. **AJAX POST** → `/api/visit/` with form data
4. **PHP processes** → `/php/api/visit.php` inserts into `visit_submissions`
5. **JSON response** → `{ success: true, message: "..." }`
6. **JavaScript updates UI** → Shows success message, resets form
7. **Admin sees it** → Appears in `/php/admin/visits/index.php` dashboard

---

## Live Page Behavior

### `live.html` (Fallback/Waiting Page)
- Shows fallback video loop when no stream is active
- Polls `/api/stream-status/` every 30 seconds
- **If stream goes live:** Automatically redirects to `live2.html`

### `live2.html` (Active Stream Page)
- Shows live stream embed
- Polls `/api/stream-status/` every 30 seconds
- **If stream goes offline:** Automatically redirects back to `live.html`
- Fetches `embed_code` from API and injects into page

---

## Database Tables Summary

| Table | Purpose | Used By |
|-------|---------|---------|
| `admin_users` | Admin login credentials | Admin panel |
| `announcements` | Main + Youth announcements | Homepage, Youth page |
| `announcement_photos` | Media for announcements | Homepage, Youth page |
| `visit_submissions` | Visit form submissions | Admin dashboard |
| `prayer_requests` | Prayer request submissions | Admin dashboard |
| `youth_scripture` | Weekly scripture + devotional | Youth page |
| `youth_albums` | Photo/video album metadata | Youth page |
| `youth_media` | Individual photos/videos | Youth page |
| `live_streams` | Live stream configuration | Live pages |
| `sermons` | Sermon archive | Sermons page |

---

## Why Youth Scripture Works

The youth scripture system works because:

1. ✅ **API endpoint exists:** `/api/youth/` returns scripture data
2. ✅ **JavaScript fetches it:** `assets/js/youth.js` calls the API
3. ✅ **Database has data:** `youth_scripture` table has active records
4. ✅ **Admin can manage it:** `/php/admin/youth-scripture/index.php` allows CRUD operations
5. ✅ **Frontend renders it:** JavaScript populates `#scripture-text` and `#scripture-reference` elements

---

## Admin Panel Management

All content is managed through the PHP admin panel at `/php/admin/`:

- **Dashboard:** Overview of all content
- **Announcements:** Create/edit main and youth announcements with photos
- **Youth Scripture:** Set weekly scripture + devotional
- **Youth Albums:** Create photo/video galleries
- **Visit Submissions:** Review and manage visitor inquiries
- **Prayer Requests:** Review and mark as prayed
- **Live Stream:** Configure stream title, embed code, toggle live status
- **Users:** Manage admin accounts and permissions

---

## Testing URLs

- **Test APIs:** `/test-apis.php` (comprehensive test suite)
- **Youth API:** `/api/youth/` (view raw JSON)
- **Main Announcements API:** `/api/announcements-main/` (view raw JSON)
- **Stream Status API:** `/api/stream-status/` (view raw JSON)
- **Admin Dashboard:** `/php/admin/dashboard.php`

---

## No Node.js Required ✅

This entire system runs on:
- **PHP 8.1+** (server-side logic)
- **MySQL** (database)
- **Vanilla JavaScript** (frontend AJAX)
- **Static HTML files** (no server-side rendering)

Perfect for shared hosting environments that don't support Node.js!
