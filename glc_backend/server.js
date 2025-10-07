const express = require('express');
const path = require('path');
const fs = require('fs');
const multer = require('multer');
const sqlite3 = require('sqlite3').verbose();
const session = require('express-session');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
require('dotenv').config();

// Import TaskScheduler
const TaskScheduler = require('./services/TaskScheduler');

const app = express();
const PORT = process.env.PORT || 4000;

// Configure multer for file uploads
const storage = multer.diskStorage({
    destination: function (req, file, cb) {
        const uploadDir = path.join(__dirname, '../uploads');
        if (!fs.existsSync(uploadDir)) {
            fs.mkdirSync(uploadDir, { recursive: true });
        }
        cb(null, uploadDir);
    },
    filename: function (req, file, cb) {
        const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
        cb(null, 'announcement-' + uniqueSuffix + path.extname(file.originalname));
    }
});

const upload = multer({ 
    storage: storage,
    limits: { fileSize: 5 * 1024 * 1024 }, // 5MB limit
    fileFilter: function (req, file, cb) {
        const allowedTypes = /jpeg|jpg|png|gif|webp/;
        const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
        const mimetype = allowedTypes.test(file.mimetype);
        
        if (mimetype && extname) {
            return cb(null, true);
        } else {
            cb(new Error('Only image files are allowed!'));
        }
    }
});

const youthMediaUpload = multer({
    storage: storage,
    limits: { fileSize: 75 * 1024 * 1024 }, // 75MB limit for videos
    fileFilter: function (req, file, cb) {
        const allowedExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.mp4', '.mov', '.m4v', '.webm'];
        const ext = path.extname(file.originalname).toLowerCase();
        const isAllowedExt = allowedExtensions.includes(ext);
        const isAllowedMime = file.mimetype.startsWith('image/') || file.mimetype.startsWith('video/');
        if (isAllowedExt && isAllowedMime) {
            return cb(null, true);
        }
        cb(new Error('Only image or video files (mp4, mov, webm) are allowed for youth gallery uploads!'));
    }
});

console.log('🏛️ Starting Liberty Church Admin Backend...');
console.log(`📊 Environment: ${process.env.APP_ENV || 'development'}`);
console.log(`🗄️  Database: SQLite (liberty_church.db)`);
console.log(`🚀 Server will run on port: ${PORT}`);

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Cache prevention middleware for HTML files
app.use((req, res, next) => {
    if (req.path.endsWith('.html') || req.path === '/' || req.path.includes('/dashboard')) {
        res.setHeader('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
        res.setHeader('Pragma', 'no-cache');
        res.setHeader('Expires', '0');
    }
    next();
});

app.use(express.static('public'));
app.use('/uploads', express.static(path.join(__dirname, '../uploads')));
app.use(session({
    secret: 'liberty_church_2024',
    resave: false,
    saveUninitialized: false
}));

// Set EJS as template engine
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

// SQLite Database connection
const db = new sqlite3.Database('./liberty_church.db', (err) => {
    if (err) {
        console.error('❌ SQLite connection failed:', err.message);
    } else {
        console.log('✅ Connected to SQLite database successfully');
        db.run('PRAGMA foreign_keys = ON');
        
        db.serialize(() => {
            // Admin users table
            db.run(`CREATE TABLE IF NOT EXISTS admin_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                role TEXT DEFAULT 'admin',
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME
            )`);
            
            // Live streams table
            db.run(`CREATE TABLE IF NOT EXISTS live_streams (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                embed_code TEXT,
                stream_title TEXT,
                is_active INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )`);
            
            // Announcements/events table
            db.run(`CREATE TABLE IF NOT EXISTS announcements_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                event_date DATE,
                media_type TEXT DEFAULT 'none',
                media_filename TEXT,
                media_alt_text TEXT,
                is_active INTEGER DEFAULT 1,
                display_order INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )`);
            
            // Plan Your Visit submissions table
            db.run(`CREATE TABLE IF NOT EXISTS visit_submissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT,
                visit_date DATE,
                party_size TEXT,
                notes TEXT,
                is_read INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )`);

            db.run(`CREATE TABLE IF NOT EXISTS prayer_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                email TEXT,
                request TEXT NOT NULL,
                share_permission INTEGER DEFAULT 0,
                is_prayed INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )`);

            db.run(`CREATE INDEX IF NOT EXISTS idx_prayer_requests_status ON prayer_requests (is_prayed, created_at)`);
            
            // Youth content tables
            db.run(`CREATE TABLE IF NOT EXISTS youth_scripture (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                scripture_text TEXT NOT NULL,
                scripture_reference TEXT NOT NULL,
                devotional TEXT NOT NULL,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )`);
            
            db.run(`CREATE TABLE IF NOT EXISTS youth_announcements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                event_date DATE,
                is_active INTEGER DEFAULT 1,
                display_order INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )`);

            db.run(`CREATE TABLE IF NOT EXISTS youth_albums (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                summary TEXT,
                event_date DATE,
                cover_media TEXT,
                is_active INTEGER DEFAULT 1,
                display_order INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )`);

            db.run(`CREATE TABLE IF NOT EXISTS youth_media (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                album_id INTEGER NOT NULL,
                media_type TEXT CHECK(media_type IN ('image','video')) DEFAULT 'image',
                media_filename TEXT,
                media_caption TEXT,
                media_url TEXT,
                display_order INTEGER DEFAULT 0,
                is_featured INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (album_id) REFERENCES youth_albums(id) ON DELETE CASCADE
            )`);
            
            // Insert default admin user
            db.run(`INSERT OR IGNORE INTO admin_users (username, role) VALUES ('admin', 'pastor')`);
            
            console.log('✅ SQLite database tables initialized');
        });
    }
});

function requireAuth(req, res, next) {
    if (!req.session.admin_logged_in) {
        return res.redirect('/login');
    }
    next();
}

function deleteUploadIfExists(filename) {
    if (!filename) {
        return;
    }

    const filePath = path.join(__dirname, '../uploads', filename);

    fs.access(filePath, fs.constants.F_OK, (err) => {
        if (err) {
            return;
        }

        fs.unlink(filePath, (unlinkErr) => {
            if (unlinkErr) {
                console.error('Error deleting upload:', filename, unlinkErr.message);
            } else {
                console.log(`🧹 Removed upload: ${filename}`);
            }
        });
    });
}

function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatInlineText(text) {
    if (!text) {
        return '';
    }

    return escapeHtml(text).replace(/\r?\n/g, '<br>');
}

function formatParagraphs(text) {
    if (!text) {
        return '';
    }

    const paragraphs = text
        .split(/\r?\n\s*\r?\n/)
        .map(paragraph => paragraph.trim())
        .filter(Boolean);

    if (paragraphs.length === 0) {
        return `<p>${formatInlineText(text.trim())}</p>`;
    }

    return paragraphs
        .map(paragraph => `<p>${formatInlineText(paragraph)}</p>`)
        .join('\n');
}

function buildMediaPublicPath(filename) {
    if (!filename) {
        return '';
    }

    if (/^https?:\/\//i.test(filename)) {
        return filename;
    }

    return `/uploads/${filename.replace(/^\/+/, '')}`;
}

function formatAlbumDate(dateValue) {
    if (!dateValue) {
        return '';
    }

    const parsed = new Date(dateValue);
    if (Number.isNaN(parsed.getTime())) {
        return '';
    }

    return parsed.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
}

function extractYoutubeVideoId(embedCode) {
    if (!embedCode) {
        return '';
    }

    const trimmed = embedCode.trim();
    const iframeMatch = trimmed.match(/embed\/([A-Za-z0-9_-]{6,})/i);
    if (iframeMatch && iframeMatch[1]) {
        return iframeMatch[1];
    }

    const queryMatch = trimmed.match(/[?&]v=([A-Za-z0-9_-]{6,})/i);
    if (queryMatch && queryMatch[1]) {
        return queryMatch[1];
    }

    const shortMatch = trimmed.match(/youtu\.be\/([A-Za-z0-9_-]{6,})/i);
    if (shortMatch && shortMatch[1]) {
        return shortMatch[1];
    }

    return '';
}

// Routes
app.get('/', (req, res) => {
    if (req.session.admin_logged_in) {
        res.redirect('/dashboard');
    } else {
        res.redirect('/login');
    }
});

app.get('/login', (req, res) => {
    res.render('login', { error: null });
});

app.post('/login', (req, res) => {
    const { username, password } = req.body;
    
    if (!username || !password) {
        return res.render('login', { error: 'Username and password are required' });
    }
    
    db.get('SELECT * FROM admin_users WHERE username = ? AND is_active = 1', [username], (err, user) => {
        if (err) {
            console.error('Database error during login:', err);
            return res.render('login', { error: 'Database connection error. Please try again.' });
        }
        
        if (!user) {
            console.log(`Login attempt failed for user: ${username}`);
            return res.render('login', { error: 'Invalid username or password' });
        }
        
        const adminPassword = process.env.ADMIN_PASSWORD || 'liberty2024';
        if (password === adminPassword) {
            req.session.admin_logged_in = true;
            req.session.admin_username = username;
            req.session.admin_role = user.role || 'admin';
            
            db.run('UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?', [user.id]);
            
            console.log(`✅ User ${username} logged in successfully`);
            res.redirect('/dashboard');
        } else {
            console.log(`❌ Invalid password attempt for user: ${username}`);
            res.render('login', { error: 'Invalid username or password' });
        }
    });
});

app.get('/logout', (req, res) => {
    const username = req.session.admin_username;
    req.session.destroy((err) => {
        if (err) {
            console.error('Error destroying session:', err);
        } else {
            console.log(`👋 User ${username} logged out`);
        }
        res.redirect('/login');
    });
});

app.get('/dashboard', (req, res) => {
    if (!req.session.admin_logged_in) {
        return res.redirect('/login');
    }

    res.setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    res.setHeader('Pragma', 'no-cache');
    res.setHeader('Expires', '0');

    db.get('SELECT * FROM live_streams WHERE is_active = 1 ORDER BY updated_at DESC LIMIT 1', (err, currentStreamRow) => {
        if (err) {
            console.error('Error fetching streams:', err);
        }

        let currentStream = null;
        if (currentStreamRow) {
            currentStream = { ...currentStreamRow };
            if (!currentStream.youtube_video_id) {
                const derivedId = extractYoutubeVideoId(currentStream.embed_code || '');
                if (derivedId) {
                    currentStream.youtube_video_id = derivedId;
                }
            }
        }

        db.all('SELECT * FROM announcements_events WHERE is_active = 1 ORDER BY display_order ASC', (annErr, contentRows) => {
            if (annErr) {
                console.error('Error fetching content:', annErr);
            }

            const content = contentRows || [];

            db.all('SELECT * FROM visit_submissions ORDER BY created_at DESC', (visitErr, visitRows) => {
                if (visitErr) {
                    console.error('Error fetching visits:', visitErr);
                }

                const visits = visitRows || [];

                db.all('SELECT * FROM prayer_requests ORDER BY is_prayed ASC, created_at DESC', (prayerErr, prayerRows) => {
                    if (prayerErr) {
                        console.error('Error fetching prayer requests:', prayerErr);
                    }

                    const prayerRequests = prayerRows || [];

                    db.get('SELECT * FROM youth_scripture WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1', (scriptureErr, scriptureRow) => {
                        if (scriptureErr) {
                            console.error('Error fetching youth scripture:', scriptureErr);
                        }

                        const youthScripture = scriptureRow || null;

                        db.all('SELECT * FROM youth_announcements ORDER BY is_active DESC, display_order ASC, event_date ASC, created_at DESC', (youthAnnErr, youthAnnouncementRows) => {
                            if (youthAnnErr) {
                                console.error('Error fetching youth announcements:', youthAnnErr);
                            }

                            const youthAnnouncements = youthAnnouncementRows || [];

                            db.all('SELECT * FROM youth_albums WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC', (albumErr, albumRows) => {
                                if (albumErr) {
                                    console.error('Error fetching youth albums:', albumErr);
                                }

                                const youthAlbumsRaw = albumRows || [];

                                db.all('SELECT * FROM youth_media ORDER BY album_id ASC, display_order ASC, created_at ASC', (mediaErr, mediaRows) => {
                                    if (mediaErr) {
                                        console.error('Error fetching youth media:', mediaErr);
                                    }

                                    const mediaByAlbum = new Map();
                                    (mediaRows || []).forEach(item => {
                                        if (!mediaByAlbum.has(item.album_id)) {
                                            mediaByAlbum.set(item.album_id, []);
                                        }
                                        mediaByAlbum.get(item.album_id).push(item);
                                    });

                                    const youthAlbums = youthAlbumsRaw.map(album => ({
                                        ...album,
                                        media: mediaByAlbum.get(album.id) || []
                                    }));

                                    res.render('dashboard', {
                                        currentStream,
                                        content,
                                        visits,
                                        prayerRequests,
                                        youthScripture,
                                        youthAnnouncements,
                                        youthAlbums,
                                        admin_username: req.session.admin_username,
                                        success: req.query.success,
                                        error: req.query.error,
                                        PORT
                                    });
                                });
                            });
                        });
                    });
                });
            });
        });
    });
});

app.post('/toggle-ls1', requireAuth, (req, res) => {
    let embedCode = (req.body.embed_code || '').trim();
    const streamTitleInput = (req.body.stream_title || '').trim();

    const activateStream = (resolvedEmbed, resolvedTitle) => {
        const youtubeVideoId = extractYoutubeVideoId(resolvedEmbed) || null;

        const onSuccess = () => {
            updateLivePage('LS1', resolvedEmbed);
            console.log('✅ LS1 activated by', req.session.admin_username);
            res.redirect('/dashboard?success=' + encodeURIComponent('LS1 Activated - Live Stream'));
        };

        db.serialize(() => {
            db.run('UPDATE live_streams SET is_active = 0', (resetErr) => {
                if (resetErr) {
                    console.error('Error deactivating streams:', resetErr);
                    return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to update stream'));
                }

                const insertWithYoutube = 'INSERT INTO live_streams (embed_code, stream_title, youtube_video_id, is_active, updated_at) VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)';
                const insertWithoutYoutube = 'INSERT INTO live_streams (embed_code, stream_title, is_active, updated_at) VALUES (?, ?, 1, CURRENT_TIMESTAMP)';

                db.run(insertWithYoutube, [resolvedEmbed, resolvedTitle, youtubeVideoId], function(insertErr) {
                    if (insertErr) {
                        if (insertErr.message && insertErr.message.includes('no column named youtube_video_id')) {
                            db.run(insertWithoutYoutube, [resolvedEmbed, resolvedTitle], function(fallbackErr) {
                                if (fallbackErr) {
                                    console.error('Error inserting stream:', fallbackErr);
                                    return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to update stream'));
                                }
                                onSuccess();
                            });
                        } else {
                            console.error('Error inserting stream:', insertErr);
                            return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to update stream'));
                        }
                    } else {
                        onSuccess();
                    }
                });
            });
        });
    };

    if (!embedCode) {
        db.get('SELECT embed_code, stream_title FROM live_streams ORDER BY updated_at DESC LIMIT 1', (lookupErr, lastStream) => {
            if (lookupErr) {
                console.error('Error retrieving existing stream:', lookupErr);
                return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to load previous stream data. Please enter the embed code.'));
            }

            if (!lastStream || !lastStream.embed_code) {
                return res.redirect('/dashboard?error=' + encodeURIComponent('Please enter the YouTube embed code before activating LS1.'));
            }

            const resolvedTitle = streamTitleInput || lastStream.stream_title || 'Live Stream';
            activateStream(lastStream.embed_code, resolvedTitle);
        });
    } else {
        const resolvedTitle = streamTitleInput || 'Live Stream';
        activateStream(embedCode, resolvedTitle);
    }
});

app.post('/toggle-ls2', requireAuth, (req, res) => {
    db.run('UPDATE live_streams SET is_active = 0', (err) => {
        if (err) {
            console.error('Error deactivating streams:', err);
            return res.redirect('/dashboard?error=Failed to update stream');
        }
        
        updateLivePage('LS2');
        console.log('✅ LS2 activated by', req.session.admin_username);
        res.redirect('/dashboard?success=LS2 Activated - Fallback Video');
    });
});

// Content management with image upload
app.post('/add-content', requireAuth, upload.single('media'), (req, res) => {
    const { title, content, event_date } = req.body;
    const eventDate = event_date || null;
    const mediaFilename = req.file ? req.file.filename : null;
    const mediaAltText = title; // Use title as alt text
    
    db.run('INSERT INTO announcements_events (title, content, event_date, media_filename, media_alt_text, is_active) VALUES (?, ?, ?, ?, ?, 1)',
        [title, content, eventDate, mediaFilename, mediaAltText], function(err) {
            if (err) {
                console.error('Error adding content:', err);
                // Delete uploaded file if database insert fails
                if (req.file) {
                    fs.unlinkSync(path.join(__dirname, '../uploads', req.file.filename));
                }
                return res.redirect('/dashboard?error=Failed to add content');
            }
            
            console.log('✅ Content added successfully by', req.session.admin_username);
            if (mediaFilename) {
                console.log('📷 Image uploaded:', mediaFilename);
            }
            
            // Automatically update homepage after adding content
            db.all('SELECT * FROM announcements_events WHERE is_active = 1 ORDER BY display_order ASC', (err, items) => {
                if (err) {
                    console.error('Error fetching content for homepage update:', err);
                    return res.redirect('/dashboard?error=Content added but failed to update homepage');
                }
                
                updateIndexPage(items || []);
                console.log('✅ Homepage updated automatically');
                res.redirect('/dashboard?success=Content added and homepage updated successfully!');
            });
        });
});

app.post('/update-homepage', requireAuth, (req, res) => {
    db.all('SELECT * FROM announcements_events WHERE is_active = 1 ORDER BY display_order ASC', (err, items) => {
        if (err) {
            console.error('Error fetching content for homepage update:', err);
            return res.redirect('/dashboard?error=Failed to update homepage');
        }
        
        updateIndexPage(items || []);
        console.log('✅ Homepage updated successfully by', req.session.admin_username);
        res.redirect('/dashboard?success=Homepage updated successfully');
    });
});

// Delete content
app.post('/delete-content/:id', requireAuth, (req, res) => {
    const contentId = req.params.id;
    
    // First, check if the item has an uploaded media file to delete
    db.get('SELECT media_filename FROM announcements_events WHERE id = ?', [contentId], (err, row) => {
        if (err) {
            console.error('Error fetching content for deletion:', err);
            return res.redirect('/dashboard?error=Failed to delete content');
        }
        
        // Delete the uploaded file if it exists
        if (row && row.media_filename) {
            deleteUploadIfExists(row.media_filename);
        }
        
        // Delete from database
        db.run('DELETE FROM announcements_events WHERE id = ?', [contentId], function(err) {
            if (err) {
                console.error('Error deleting content:', err);
                return res.redirect('/dashboard?error=Failed to delete content');
            }
            
            console.log('✅ Content deleted by', req.session.admin_username);
            
            // Regenerate index.html with remaining active content
            db.all('SELECT * FROM announcements_events WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC', [], (err, items) => {
                if (err) {
                    console.error('Error fetching content after deletion:', err);
                    return res.redirect('/dashboard?error=Content deleted but failed to update homepage');
                }
                
                updateIndexPage(items || []);
                res.redirect('/dashboard?success=Content deleted and homepage updated!');
            });
        });
    });
});

// Visit submissions endpoints
app.post('/api/submit-visit', (req, res) => {
    const { name, email, phone, date, party, notes } = req.body;
    
    if (!name || !email) {
        return res.status(400).json({ success: false, message: 'Name and email are required' });
    }
    
    db.run(`INSERT INTO visit_submissions (name, email, phone, visit_date, party_size, notes) 
            VALUES (?, ?, ?, ?, ?, ?)`,
        [name, email, phone || null, date || null, party || '1', notes || null],
        function(err) {
            if (err) {
                console.error('Error saving visit submission:', err);
                return res.status(500).json({ success: false, message: 'Failed to save submission' });
            }
            
            console.log(`✅ New visit submission from ${name} (${email})`);
            res.json({ success: true, message: 'Visit request received!' });
        });
});

app.post('/mark-visit-read/:id', requireAuth, (req, res) => {
    const visitId = req.params.id;
    
    db.run('UPDATE visit_submissions SET is_read = 1 WHERE id = ?', [visitId], function(err) {
        if (err) {
            console.error('Error marking visit as read:', err);
            return res.redirect('/dashboard?error=Failed to update');
        }
        res.redirect('/dashboard?success=Marked as read');
    });
});

app.post('/delete-visit/:id', requireAuth, (req, res) => {
    const visitId = req.params.id;
    
    db.run('DELETE FROM visit_submissions WHERE id = ?', [visitId], function(err) {
        if (err) {
            console.error('Error deleting visit:', err);
            return res.redirect('/dashboard?error=Failed to delete');
        }
        
        console.log('✅ Visit submission deleted by', req.session.admin_username);
        res.redirect('/dashboard?success=Visit submission deleted');
    });
});

// Prayer request endpoints
app.post('/api/prayer-request', (req, res) => {
    const { name, email, request: requestBody, sharePermission } = req.body || {};

    const trimmedRequest = (requestBody || '').toString().trim();
    if (!trimmedRequest) {
        return res.status(400).json({ success: false, message: 'Prayer request is required' });
    }

    const sanitizedName = (name || '').toString().trim() || null;
    const sanitizedEmail = (email || '').toString().trim() || null;
    const allowShare = sharePermission === true || sharePermission === 'true' || sharePermission === 'yes' || sharePermission === 'on';

    db.run(
        `INSERT INTO prayer_requests (name, email, request, share_permission) VALUES (?, ?, ?, ?)`,
        [sanitizedName, sanitizedEmail, trimmedRequest, allowShare ? 1 : 0],
        function(err) {
            if (err) {
                console.error('Error saving prayer request:', err);
                return res.status(500).json({ success: false, message: 'Failed to save prayer request' });
            }

            console.log(`🙏 New prayer request received${sanitizedName ? ` from ${sanitizedName}` : ''}`);
            res.json({ success: true, message: 'Prayer request received! Our team will be praying.' });
        }
    );
});

app.post('/mark-prayer-prayed/:id', requireAuth, (req, res) => {
    const prayerId = req.params.id;

    db.run('UPDATE prayer_requests SET is_prayed = 1 WHERE id = ?', [prayerId], function(err) {
        if (err) {
            console.error('Error marking prayer as prayed:', err);
            return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to update prayer request'));
        }

        res.redirect('/dashboard?success=' + encodeURIComponent('Prayer request marked as prayed'));
    });
});

app.post('/delete-prayer/:id', requireAuth, (req, res) => {
    const prayerId = req.params.id;

    db.run('DELETE FROM prayer_requests WHERE id = ?', [prayerId], function(err) {
        if (err) {
            console.error('Error deleting prayer request:', err);
            return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to delete prayer request'));
        }

        console.log('🗂️ Prayer request archived by', req.session.admin_username);
        res.redirect('/dashboard?success=' + encodeURIComponent('Prayer request archived'));
    });
});

// ============================================
// YOUTH PAGE MANAGEMENT
// ============================================

// Update Scripture of the Week
app.post('/update-youth-scripture', requireAuth, (req, res) => {
    const { scripture_text, scripture_reference, devotional } = req.body;
    
    if (!scripture_text || !scripture_reference || !devotional) {
        return res.redirect('/dashboard?error=' + encodeURIComponent('All scripture fields are required'));
    }
    
    // Deactivate old scripture
    db.run('UPDATE youth_scripture SET is_active = 0', (err) => {
        if (err) {
            console.error('Error deactivating old scripture:', err);
            return res.redirect('/dashboard?error=' + encodeURIComponent('Database error'));
        }
        
        // Insert new scripture
        db.run('INSERT INTO youth_scripture (scripture_text, scripture_reference, devotional) VALUES (?, ?, ?)',
            [scripture_text, scripture_reference, devotional],
            function(err) {
                if (err) {
                    console.error('Error adding scripture:', err);
                    return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to add scripture'));
                }
                
                console.log(`✅ Scripture of the Week updated: ${scripture_reference}`);
                res.redirect('/dashboard?success=' + encodeURIComponent('Scripture of the Week updated! Click "Update Youth Page" to publish.'));
            });
    });
});

// Add Youth Announcement
app.post('/add-youth-announcement', requireAuth, (req, res) => {
    const { title = '', content = '', event_date = '' } = req.body;
    const trimmedTitle = title.trim();
    const trimmedContent = content.trim();
    const normalizedDate = event_date.trim() || null;

    if (!trimmedTitle || !trimmedContent) {
        return res.redirect('/dashboard?error=' + encodeURIComponent('Title and content are required'));
    }
    
    db.run(
        'INSERT INTO youth_announcements (title, content, event_date) VALUES (?, ?, ?)',
        [trimmedTitle, trimmedContent, normalizedDate],
        function(err) {
            if (err) {
                console.error('Error adding youth announcement:', err);
                return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to add youth announcement'));
            }
            
            console.log(`✅ Youth announcement added: ${trimmedTitle} (ID: ${this.lastID})`);
            
            updateYouthPage(() => {
                res.redirect('/dashboard?success=' + encodeURIComponent('Youth announcement added and page updated!'));
            });
        }
    );
});

// Delete Youth Announcement
app.post('/delete-youth-announcement/:id', requireAuth, (req, res) => {
    const { id } = req.params;
    
    db.run('DELETE FROM youth_announcements WHERE id = ?', [id], function(err) {
        if (err) {
            console.error('Error deleting youth announcement:', err);
            return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to delete youth announcement'));
        }
        
        console.log(`✅ Youth announcement deleted (ID: ${id})`);
        
        // Auto-update youth page after deletion
        updateYouthPage(() => {
            res.redirect('/dashboard?success=' + encodeURIComponent('Youth announcement deleted and page updated!'));
        });
    });
});

// Update Youth Page Button
app.post('/update-youth-page', requireAuth, (req, res) => {
    updateYouthPage(() => {
        res.redirect('/dashboard?success=' + encodeURIComponent('Youth page updated successfully!'));
    });
});

// Create Youth Album
app.post('/create-youth-album', requireAuth, upload.single('cover_media'), (req, res) => {
    const { title, summary, event_date } = req.body;

    if (!title) {
        if (req.file) {
            deleteUploadIfExists(req.file.filename);
        }
        return res.redirect('/dashboard?error=' + encodeURIComponent('Album title is required'));
    }

    db.get('SELECT COALESCE(MAX(display_order), 0) AS maxOrder FROM youth_albums', (err, row) => {
        if (err) {
            console.error('Error fetching album order:', err);
            if (req.file) {
                deleteUploadIfExists(req.file.filename);
            }
            return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to create album'));
        }

        const nextOrder = (row && typeof row.maxOrder === 'number') ? row.maxOrder + 1 : 0;

        db.run('INSERT INTO youth_albums (title, summary, event_date, cover_media, display_order) VALUES (?, ?, ?, ?, ?)',
            [title.trim(), summary ? summary.trim() : null, event_date || null, req.file ? req.file.filename : null, nextOrder],
            function(err) {
                if (err) {
                    console.error('Error creating youth album:', err);
                    if (req.file) {
                        deleteUploadIfExists(req.file.filename);
                    }
                    return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to create album'));
                }

                console.log(`✅ Youth album created (ID: ${this.lastID}) by ${req.session.admin_username}`);
                res.redirect('/dashboard?success=' + encodeURIComponent('Youth album added! Click "Update Youth Page" to publish.'));
            });
    });
});

// Delete Youth Album
app.post('/delete-youth-album/:id', requireAuth, (req, res) => {
    const albumId = req.params.id;

    db.get('SELECT cover_media FROM youth_albums WHERE id = ?', [albumId], (err, album) => {
        if (err) {
            console.error('Error fetching youth album:', err);
            return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to delete album'));
        }

        if (!album) {
            return res.redirect('/dashboard?success=' + encodeURIComponent('Album already removed.'));
        }

        db.all('SELECT media_filename FROM youth_media WHERE album_id = ?', [albumId], (err, mediaRows) => {
            if (err) {
                console.error('Error fetching youth album media:', err);
            }

            db.run('DELETE FROM youth_media WHERE album_id = ?', [albumId], function(err) {
                if (err) {
                    console.error('Error deleting youth media:', err);
                    return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to delete album media'));
                }

                db.run('DELETE FROM youth_albums WHERE id = ?', [albumId], function(err) {
                    if (err) {
                        console.error('Error deleting youth album:', err);
                        return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to delete album'));
                    }

                    deleteUploadIfExists(album.cover_media);
                    (mediaRows || []).forEach(row => deleteUploadIfExists(row.media_filename));

                    console.log(`🗑️ Youth album deleted (ID: ${albumId}) by ${req.session.admin_username}`);
                    res.redirect('/dashboard?success=' + encodeURIComponent('Youth album deleted. Remember to update the youth page.'));
                });
            });
        });
    });
});

// Add media to Youth Album
app.post('/add-youth-media', requireAuth, youthMediaUpload.single('media'), (req, res) => {
    const { album_id, caption } = req.body;

    if (!album_id) {
        if (req.file) {
            deleteUploadIfExists(req.file.filename);
        }
        return res.redirect('/dashboard?error=' + encodeURIComponent('Select an album before uploading media'));
    }

    if (!req.file) {
        return res.redirect('/dashboard?error=' + encodeURIComponent('Upload an image or video file for the album'));
    }

    db.get('SELECT id FROM youth_albums WHERE id = ?', [album_id], (err, album) => {
        if (err) {
            console.error('Error verifying album:', err);
            deleteUploadIfExists(req.file.filename);
            return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to attach media to album'));
        }

        if (!album) {
            deleteUploadIfExists(req.file.filename);
            return res.redirect('/dashboard?error=' + encodeURIComponent('Album not found'));
        }

        const mediaType = req.file.mimetype.startsWith('video/') ? 'video' : 'image';

        db.get('SELECT COALESCE(MAX(display_order), 0) AS maxOrder FROM youth_media WHERE album_id = ?', [album_id], (err, row) => {
            if (err) {
                console.error('Error getting media order:', err);
                deleteUploadIfExists(req.file.filename);
                return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to store media'));
            }

            const nextOrder = (row && typeof row.maxOrder === 'number') ? row.maxOrder + 1 : 0;

            db.run('INSERT INTO youth_media (album_id, media_type, media_filename, media_caption, display_order) VALUES (?, ?, ?, ?, ?)',
                [album_id, mediaType, req.file.filename, caption ? caption.trim() : null, nextOrder],
                function(err) {
                    if (err) {
                        console.error('Error adding youth media:', err);
                        deleteUploadIfExists(req.file.filename);
                        return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to store media'));
                    }

                    console.log(`✅ Youth media added (ID: ${this.lastID}) to album ${album_id}`);
                    res.redirect('/dashboard?success=' + encodeURIComponent('Media uploaded! Click "Update Youth Page" to publish.'));
                });
        });
    });
});

// Delete youth media item
app.post('/delete-youth-media/:id', requireAuth, (req, res) => {
    const mediaId = req.params.id;

    db.get('SELECT media_filename, album_id FROM youth_media WHERE id = ?', [mediaId], (err, media) => {
        if (err) {
            console.error('Error fetching youth media:', err);
            return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to delete media item'));
        }

        if (!media) {
            return res.redirect('/dashboard?success=' + encodeURIComponent('Media already removed.'));
        }

        db.run('DELETE FROM youth_media WHERE id = ?', [mediaId], function(err) {
            if (err) {
                console.error('Error deleting youth media:', err);
                return res.redirect('/dashboard?error=' + encodeURIComponent('Failed to delete media item'));
            }

            deleteUploadIfExists(media.media_filename);
            console.log(`🗑️ Youth media deleted (ID: ${mediaId}) by ${req.session.admin_username}`);
            res.redirect('/dashboard?success=' + encodeURIComponent('Media item deleted. Update the youth page to remove it from the site.'));
        });
    });
});

// Helper functions
function updateLivePage(mode, embedCode = '') {
    try {
        const liveHtmlPath = path.join(__dirname, '../live.html');
        console.log(`📝 Attempting to update: ${liveHtmlPath}`);
        
        let liveHtml = fs.readFileSync(liveHtmlPath, 'utf8');
        
        if (mode === 'LS1') {
            const wrappedEmbed = `<div class="embed aspect-16x9">
              ${embedCode}
            </div>`;
            
            liveHtml = liveHtml.replace(
                /(<div id="LS1"[^>]*>)([\s\S]*?)(<\/div>\s*<!--[\s\S]*?-->\s*<div id="LS2")/,
                `<div id="LS1" style="display: block;">\n            ${wrappedEmbed}\n            <!-- Backend will inject live stream content here -->\n          </div>\n          \n          <!-- LS2 Integration Point - Fallback Video -->\n          <div id="LS2"`
            );
            
            liveHtml = liveHtml.replace(
                /<div id="LS2"[^>]*>/,
                '<div id="LS2" style="display: none;">'
            );
            
            console.log('✅ Updated live.html to LS1 mode (Live Stream Active)');
            
        } else {
            const fallbackContent = `<div class="embed aspect-16x9">
              <video class="fallback-video" autoplay muted loop>
                <source src="assets/stream_fallback_loop/stream_fall_back_loop.mp4" type="video/mp4">
                Your browser does not support the video tag.
              </video>
            </div>
            <p class="note">We are not currently streaming live. Join us Sundays at 10:00 AM.</p>`;
            
            liveHtml = liveHtml.replace(
                /(<div id="LS2"[^>]*>)([\s\S]*?)(<\/div>\s*<\/div>\s*<\/section>)/,
                `<div id="LS2" style="display: block;">\n            ${fallbackContent}\n          </div>\n        </div>\n      </section>`
            );
            
            liveHtml = liveHtml.replace(
                /(<div id="LS1"[^>]*>)([\s\S]*?)(<\/div>\s*<!--[\s\S]*?-->\s*<div id="LS2")/,
                `<div id="LS1" style="display: none;">\n            <!-- Backend will inject live stream content here -->\n          </div>\n          \n          <!-- LS2 Integration Point - Fallback Video -->\n          <div id="LS2"`
            );
            
            console.log('✅ Updated live.html to LS2 mode (Fallback Video Active)');
        }
        
        fs.writeFileSync(liveHtmlPath, liveHtml);
        console.log(`✅ File written successfully`);
        
    } catch (error) {
        console.error('❌ Error updating live page:', error.message);
    }
}

function updateIndexPage(items) {
    try {
        const indexPagePath = process.env.INDEX_HTML_PATH || '../index.html';
        const fullPath = path.resolve(__dirname, indexPagePath);
        
        if (!fs.existsSync(fullPath)) {
            throw new Error(`Index page not found at: ${fullPath}`);
        }
        
        let content;
        
        if (items.length === 0) {
            content = `<div class="content-card">
          <div class="card-body">
            <div class="empty-announcements">
              No announcements or events at this time. Check back soon!
            </div>
          </div>
        </div>`;
        } else {
            content = `<div class="content-card expandable">
          <div class="card-header">
            <h3>Latest News & Events</h3>
          </div>
          <div class="card-body">`;
            
            items.forEach(item => {
                content += `\n            <div class="announcement-item">`;
                
                // Add image if exists
                if (item.media_filename) {
                    content += `\n              <div class="announcement-media">`;
                    content += `\n                <img src="uploads/${item.media_filename}" alt="${item.media_alt_text || item.title}" style="max-width: 300px; border-radius: 8px; margin-bottom: 1rem;">`;
                    content += `\n              </div>`;
                }
                
                content += `\n              <h4>${item.title}</h4>`;
                if (item.event_date) {
                    const date = new Date(item.event_date).toLocaleDateString('en-US', { 
                        year: 'numeric', month: 'long', day: 'numeric' 
                    });
                    content += `\n              <div class="event-date">📅 ${date}</div>`;
                }
                content += `\n              <div class="content">${item.content.replace(/\n/g, '<br>')}</div>`;
                content += `\n            </div>`;
            });
            
            content += `\n          </div>
        </div>`;
        }
        
        let indexHtml = fs.readFileSync(fullPath, 'utf8');
        
        indexHtml = indexHtml.replace(
            /(<!-- IndexUpdate 1 -->)([\s\S]*?)(<!-- End IndexUpdate 1 -->)/,
            `$1\n        ${content}\n        $3`
        );
        
        fs.writeFileSync(fullPath, indexHtml);
        console.log(`✅ Successfully updated index.html with ${items.length} content items`);
        
    } catch (error) {
        console.error('❌ Error updating index page:', error.message);
    }
}

function updateYouthPage(callback) {
    const youthHtmlPath = path.join(__dirname, process.env.YOUTH_HTML_PATH || '../youth.html');

    fs.readFile(youthHtmlPath, 'utf8', (err, template) => {
        if (err) {
            console.error('❌ Error reading youth.html:', err);
            return callback();
        }

        db.get('SELECT * FROM youth_scripture WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1', (scriptureErr, scripture) => {
            if (scriptureErr) {
                console.error('Error fetching scripture:', scriptureErr);
            }

            db.all('SELECT * FROM youth_announcements WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC', (announcementsErr, announcementRows) => {
                if (announcementsErr) {
                    console.error('Error fetching youth announcements:', announcementsErr);
                }

                db.all('SELECT * FROM youth_albums WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC', (albumsErr, albumRows) => {
                    if (albumsErr) {
                        console.error('Error fetching youth albums:', albumsErr);
                    }

                    db.all('SELECT * FROM youth_media ORDER BY album_id ASC, display_order ASC, created_at ASC', (mediaErr, mediaRows) => {
                        if (mediaErr) {
                            console.error('Error fetching youth media:', mediaErr);
                        }

                        const scriptureBody = scripture ? formatInlineText(scripture.scripture_text || '') : '';
                        const scriptureReference = scripture ? escapeHtml(scripture.scripture_reference || '') : '';
                        const devotionalBody = scripture ? formatParagraphs(scripture.devotional || '') : '';

                        const scriptureFallback = 'Hang tight—our next scripture is coming soon.';
                        const devotionalFallback = '<p>Our team is crafting the next devotional. Check back soon.</p>';

                        const scriptureHTML = `
    <section class="section alt youth-scripture" id="week-in-word">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Here&#8217;s what&#8217;s speaking to us</span>
                <h2>Scripture of the Week + Devotional</h2>
                <p class="sub">Each week we rally around a verse and a short devo crafted by our youth team.</p>
            </div>
            <div class="youth-scripture-grid">
                <article class="glass-card scripture-card">
                    <span class="badge badge-light">Scripture of the Week</span>
                    <blockquote>
                        <p>${scriptureBody || scriptureFallback}</p>
                    </blockquote>
                    ${scriptureReference ? `<cite>${scriptureReference}</cite>` : ''}
                </article>
                <article class="glass-card devotional-card">
                    <div class="devotional-header">
                        <span class="badge badge-outline">Weekly Devo</span>
                        <h3>Lean in &amp; reflect</h3>
                    </div>
                    <div class="devotional-text">
                        ${devotionalBody || devotionalFallback}
                    </div>
                    <div class="devotional-footer">
                        <p>Want to talk it out? Show up Sundays at <strong>9:20 AM</strong> for student-led conversation and prayer.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>`;

                        const announcementCards = (announcementRows || []).map(ann => {
                            const dateLabel = ann.event_date ? `<div class="announcement-meta">📅 ${formatAlbumDate(ann.event_date)}</div>` : '';
                            const bodyCopy = ann.content ? `<p class="announcement-copy">${formatInlineText(ann.content)}</p>` : '';
                            return `        <article class="announcement-card">
                    ${dateLabel}
                    <h3>${escapeHtml(ann.title || '')}</h3>
                    ${bodyCopy}
                </article>`;
                        }).join('\n');

                        const announcementsPlaceholder = `        <article class="announcement-card empty" id="announcements-empty">
                    <p class="muted">Announcements are loading...</p>
                </article>`;

                        const announcementsHTML = `
    <section class="section alt youth-announcements" id="announcements">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Don&#8217;t miss out</span>
                <h2>Announcements &amp; Events</h2>
                <p class="sub">Fresh updates, pop-up hangs, and everything happening next.</p>
            </div>
            <div class="announcements-grid" id="announcements-grid">
${announcementCards || announcementsPlaceholder}
            </div>
        </div>
    </section>`;

                        const mediaByAlbum = new Map();
                        (mediaRows || []).forEach(item => {
                            if (!mediaByAlbum.has(item.album_id)) {
                                mediaByAlbum.set(item.album_id, []);
                            }
                            mediaByAlbum.get(item.album_id).push(item);
                        });

                        const albumsWithMedia = (albumRows || []).map(album => {
                            const rawMedia = mediaByAlbum.get(album.id) || [];
                            const mediaItems = rawMedia
                                .map(row => {
                                    const url = buildMediaPublicPath(row.media_filename) || row.media_url || '';
                                    if (!url) {
                                        return null;
                                    }
                                    return {
                                        id: row.id,
                                        type: row.media_type === 'video' ? 'video' : 'image',
                                        url,
                                        caption: row.media_caption || ''
                                    };
                                })
                                .filter(Boolean);

                            const coverCandidate = album.cover_media ? buildMediaPublicPath(album.cover_media) : '';
                            const coverPath = coverCandidate || (mediaItems[0] ? mediaItems[0].url : 'assets/youth-backdrop.png');

                            return {
                                album,
                                coverPath,
                                mediaItems
                            };
                        });

                        let galleryHTML = '';
                        let galleryJSON = '[]';

                        if (albumsWithMedia.length > 0) {
                            const galleryData = albumsWithMedia.map(({ album, coverPath, mediaItems }) => ({
                                id: album.id,
                                title: album.title,
                                summary: album.summary,
                                event_date: album.event_date,
                                cover: coverPath,
                                media: mediaItems
                            }));

                            galleryJSON = JSON.stringify(galleryData).replace(/</g, '\\u003c');

                            const selectOptions = albumsWithMedia.map(({ album }, index) =>
                                `                    <option value="${album.id}"${index === 0 ? ' selected' : ''}>${escapeHtml(album.title || '')}</option>`
                            ).join('\n');

                            const defaultAlbum = albumsWithMedia[0];
                            const defaultMedia = defaultAlbum.mediaItems[0] || null;

                            const stageMediaMarkup = defaultMedia
                                ? (defaultMedia.type === 'video'
                                    ? `                    <video src="${escapeHtml(defaultMedia.url)}" controls preload="metadata" aria-label="${escapeHtml(defaultMedia.caption || defaultAlbum.album.title || 'Youth gallery video')}"></video>`
                                    : `                    <img src="${escapeHtml(defaultMedia.url)}" alt="${escapeHtml(defaultMedia.caption || defaultAlbum.album.title || 'Youth gallery photo')}">`)
                                : `                    <img src="${escapeHtml(defaultAlbum.coverPath)}" alt="${escapeHtml(defaultAlbum.album.title || 'Youth gallery cover')}">`;

                            const summaryHtml = defaultAlbum.album.summary ? formatParagraphs(defaultAlbum.album.summary) : '';
                            const stageDetailsLines = [
                                `<h3>${escapeHtml(defaultAlbum.album.title || 'Youth Hangout')}</h3>`,
                                summaryHtml,
                                defaultAlbum.album.event_date ? `<div class="stage-meta">📅 ${formatAlbumDate(defaultAlbum.album.event_date)}</div>` : ''
                            ].filter(Boolean);

                            const stageDetailsInner = stageDetailsLines.length
                                ? stageDetailsLines.join('\n')
                                : '<p class="muted">Highlights from this hangout are coming soon.</p>';

                            const stageDetailsMarkup = stageDetailsInner
                                .split('\n')
                                .map(line => `                    ${line}`)
                                .join('\n');

                            const mediaGridMarkup = defaultAlbum.mediaItems.length
                                ? defaultAlbum.mediaItems.map((media, index) => {
                                    const thumb = media.type === 'video'
                                        ? `<div class="media-thumb media-thumb-video"><video src="${escapeHtml(media.url)}" muted preload="metadata" aria-hidden="true"></video><span class="media-flag">▶</span></div>`
                                        : `<div class="media-thumb"><img src="${escapeHtml(media.url)}" alt="${escapeHtml(media.caption || defaultAlbum.album.title || 'Youth gallery image')}"></div>`;
                                    return `                <button class="media-item${index === 0 ? ' is-active' : ''}" type="button" data-album-id="${defaultAlbum.album.id}" data-media-index="${index}">
                    ${thumb}
                    <span class="sr-only">${escapeHtml(media.caption || defaultAlbum.album.title || 'Gallery media')}</span>
                </button>`;
                                }).join('\n')
                                : '                <div class="media-empty"><p class="muted">Media will be added soon.</p></div>';

                            galleryHTML = `
    <section class="section youth-gallery" id="gallery">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Memories in motion</span>
                <h2>Check out our past hangouts</h2>
                <p class="sub">Choose an album to explore highlight photos and videos.</p>
            </div>
            <div class="gallery-controls">
                <label class="sr-only" for="youth-album-select">Choose an album</label>
                <select id="youth-album-select" class="album-select">
${selectOptions}
                </select>
            </div>
            <div class="gallery-stage${defaultAlbum.mediaItems.length === 0 ? ' empty' : ''}" id="gallery-stage" data-selected-album="${defaultAlbum.album.id}">
                <div class="stage-media">
${stageMediaMarkup}
                </div>
                <div class="stage-details">
${stageDetailsMarkup}
                </div>
            </div>
            <div class="media-grid" id="gallery-media-grid" aria-live="polite">
${mediaGridMarkup}
            </div>
        </div>
    </section>
    <script type="application/json" id="youthGalleryData">${galleryJSON}</script>`;
                        } else {
                            galleryHTML = `
    <section class="section youth-gallery" id="gallery">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Memories in motion</span>
                <h2>Check out our past hangouts</h2>
                <p class="sub">Choose an album to explore highlight photos and videos.</p>
            </div>
            <div class="gallery-controls">
                <label class="sr-only" for="youth-album-select">Choose an album</label>
                <select id="youth-album-select" class="album-select" disabled>
                    <option value="">Albums coming soon</option>
                </select>
            </div>
            <div class="gallery-stage empty" id="gallery-stage">
                <div class="stage-placeholder">
                    <p class="muted">Gallery coming soon. Check back after our next youth hangout!</p>
                </div>
            </div>
            <div class="media-grid" id="gallery-media-grid" aria-live="polite"></div>
        </div>
    </section>
    <script type="application/json" id="youthGalleryData">[]</script>`;
                        }

                        let updatedHtml = template;

                        const scriptureRegex = /(<!-- YouthUpdate 1: Scripture of the Week \+ Weekly Devotional to be updated from backend -->)[\s\S]*?(?=<!-- YouthUpdate 2:|$)/;
                        updatedHtml = updatedHtml.replace(scriptureRegex, `$1\n${scriptureHTML}\n\n  `);

                        const announcementsRegex = /(<!-- YouthUpdate 2: Youth Announcements to be updated from backend -->)[\s\S]*?(?=<!-- YouthUpdate 3:|<section|$)/;
                        updatedHtml = updatedHtml.replace(announcementsRegex, `$1\n${announcementsHTML}\n\n  `);

                        const galleryRegex = /(<!-- YouthUpdate 3: Photo Gallery to be updated from backend -->)[\s\S]*?(?=<!-- YouthUpdate 4:|$)/;
                        updatedHtml = updatedHtml.replace(galleryRegex, `$1\n${galleryHTML}\n\n  `);

                        fs.writeFile(youthHtmlPath, updatedHtml, 'utf8', (writeErr) => {
                            if (writeErr) {
                                console.error('❌ Error writing youth.html:', writeErr);
                            } else {
                                console.log('✅ youth.html updated successfully!');
                            }
                            callback();
                        });
                    });
                });
            });
        });
    });
}

// Public API
app.get('/api/current-stream', (req, res) => {
    db.get('SELECT * FROM live_streams WHERE is_active = 1 ORDER BY updated_at DESC LIMIT 1', 
        (err, stream) => {
            if (err) {
                console.error('❌ API Error fetching stream:', err);
                return res.status(500).json({ 
                    error: 'Database error', 
                    stream: null,
                    fallback: true 
                });
            }
            console.log('✅ API /api/current-stream called - Stream:', stream ? stream.stream_title : 'None (fallback)');
            res.json({ 
                stream: stream || null,
                fallback: stream === null,
                timestamp: new Date().toISOString()
            });
        });
});

app.get('/api/youth-gallery', (req, res) => {
    db.all('SELECT * FROM youth_albums WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC', (err, albums) => {
        if (err) {
            console.error('❌ API Error fetching youth albums:', err);
            return res.status(500).json({ error: 'Database error', albums: [] });
        }

        if (!albums || albums.length === 0) {
            return res.json({ albums: [] });
        }

        const albumIds = albums.map(album => album.id);
        const placeholders = albumIds.map(() => '?').join(',');

        db.all(`SELECT * FROM youth_media WHERE album_id IN (${placeholders}) ORDER BY album_id ASC, display_order ASC, created_at ASC`, albumIds, (err, mediaRows) => {
            if (err) {
                console.error('❌ API Error fetching youth media:', err);
                return res.status(500).json({ error: 'Database error', albums: [] });
            }

            const mediaByAlbum = new Map();
            (mediaRows || []).forEach(item => {
                if (!mediaByAlbum.has(item.album_id)) {
                    mediaByAlbum.set(item.album_id, []);
                }
                mediaByAlbum.get(item.album_id).push({
                    id: item.id,
                    type: item.media_type,
                    url: buildMediaPublicPath(item.media_filename) || item.media_url || '',
                    caption: item.media_caption,
                    created_at: item.created_at
                });
            });

            const response = albums.map(album => ({
                id: album.id,
                title: album.title,
                summary: album.summary,
                event_date: album.event_date,
                cover: album.cover_media ? buildMediaPublicPath(album.cover_media) : null,
                created_at: album.created_at,
                media: mediaByAlbum.get(album.id) || []
            }));

            res.json({ albums: response });
        });
    });
});

app.post('/refresh-sermons', requireAuth, (req, res) => {
    console.log('✅ Sermons cache refresh triggered by', req.session.admin_username);
    res.redirect('/dashboard?success=Sermons cache cleared - Visit sermons page to see fresh data');
});

// =============================================================================
// TASK SCHEDULER API ENDPOINTS (Replaces Cron Jobs)
// =============================================================================

// Initialize TaskScheduler (replaces cron jobs)
const taskScheduler = new TaskScheduler();

// Health check endpoint
app.get('/health', (req, res) => {
    res.status(200).json({ 
        status: 'healthy',
        timestamp: new Date().toISOString(),
        uptime: process.uptime()
    });
});

// Get task scheduler status
app.get('/api/tasks/status', requireAuth, (req, res) => {
    const status = taskScheduler.getTaskStatus();
    res.json({
        success: true,
        tasks: status,
        timestamp: new Date().toISOString()
    });
});

// Manually trigger a task
app.post('/api/tasks/:taskName/trigger', requireAuth, (req, res) => {
    const { taskName } = req.params;
    
    try {
        taskScheduler.triggerTask(taskName);
        console.log(`🚀 Task ${taskName} manually triggered by ${req.session.admin_username}`);
        
        res.json({
            success: true,
            message: `Task '${taskName}' has been triggered`,
            timestamp: new Date().toISOString()
        });
    } catch (error) {
        console.error(`❌ Failed to trigger task ${taskName}:`, error.message);
        res.status(400).json({
            success: false,
            error: error.message
        });
    }
});

// API endpoints for external services (GitHub Actions, webhooks, etc.)
app.post('/api/refresh-sermons', (req, res) => {
    // Add basic API key authentication if needed
    const apiKey = req.headers.authorization?.replace('Bearer ', '');
    if (process.env.API_KEY && apiKey !== process.env.API_KEY) {
        return res.status(401).json({ error: 'Unauthorized' });
    }
    
    try {
        taskScheduler.triggerTask('sermon-refresh');
        res.json({
            success: true,
            message: 'Sermon refresh triggered',
            timestamp: new Date().toISOString()
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

app.post('/api/backup-database', (req, res) => {
    const apiKey = req.headers.authorization?.replace('Bearer ', '');
    if (process.env.API_KEY && apiKey !== process.env.API_KEY) {
        return res.status(401).json({ error: 'Unauthorized' });
    }
    
    try {
        taskScheduler.triggerTask('database-backup');
        res.json({
            success: true,
            message: 'Database backup triggered',
            timestamp: new Date().toISOString()
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Start server
app.listen(PORT, () => {
    console.log('\n' + '='.repeat(60));
    console.log('🏛️  LIBERTY CHURCH ADMIN BACKEND STARTED');
    console.log('='.repeat(60));
    console.log(`🚀 Server running on: http://localhost:${PORT}`);
    console.log(`📁 Static files: ${path.join(__dirname, 'public')}`);
    console.log(`🎨 Templates: ${path.join(__dirname, 'views')}`);
    console.log('⏰ Task scheduler initialized (no cron jobs needed!)');
    console.log('='.repeat(60));
    console.log('🔐 Ready for admin access!');
});

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('\n🔄 Shutting down gracefully...');
    taskScheduler.stopAllTasks();
    process.exit(0);
});