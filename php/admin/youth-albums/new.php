<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

$pdo = db();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') {
        header('Location: new.php?error=' . urlencode('Album title is required'));
        exit;
    }

    // Handle cover photo upload
    $coverMedia = null;
    if (isset($_FILES['cover_media']) && $_FILES['cover_media']['error'] === UPLOAD_ERR_OK) {
        ensure_upload_directory();
        
        $uploadedFile = $_FILES['cover_media'];
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($extension, $allowedExtensions)) {
            header('Location: new.php?error=' . urlencode('Cover photo must be JPG, PNG, or WebP'));
            exit;
        }
        
        if ($uploadedFile['size'] > MAX_UPLOAD_BYTES) {
            header('Location: new.php?error=' . urlencode('Cover photo is too large'));
            exit;
        }
        
        $filename = uniqid('album_cover_') . '.' . $extension;
        $destination = UPLOAD_DIR . '/' . $filename;
        
        if (move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
            $coverMedia = $filename;
        }
    }

    $sql = "INSERT INTO youth_albums (title, summary, event_date, cover_media, display_order, is_active)
            VALUES (:title, :summary, :event_date, :cover_media, :display_order, :is_active)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':summary' => $summary,
        ':event_date' => $eventDate ?: null,
        ':cover_media' => $coverMedia,
        ':display_order' => $displayOrder,
        ':is_active' => $isActive,
    ]);

    $albumId = $pdo->lastInsertId();

    header('Location: manage-media.php?album_id=' . $albumId . '&message=' . urlencode('Album created! Now add photos.'));
    exit;
}

admin_page_start('New Album', 'youth-albums');
?>

<section class="card">
    <h2>Create Youth Album</h2>
    <p class="muted">Start a new photo album for a youth event or gathering.</p>

    <form method="POST" enctype="multipart/form-data" class="form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label for="title">Album Title *</label>
            <input type="text" id="title" name="title" required maxlength="255" placeholder="e.g., Summer Camp 2025">
        </div>

        <div class="form-group">
            <label for="summary">Summary</label>
            <textarea id="summary" name="summary" rows="3" placeholder="Describe what made this event special..."></textarea>
        </div>

        <div class="form-group">
            <label for="event_date">Event Date</label>
            <input type="date" id="event_date" name="event_date">
            <small class="form-help">When did this event happen?</small>
        </div>

        <div class="form-group">
            <label for="cover_media">Cover Photo</label>
            <input type="file" id="cover_media" name="cover_media" accept="image/jpeg,image/png,image/webp">
            <small class="form-help">Upload a landscape photo to represent this album (optional)</small>
        </div>

        <div class="form-group">
            <label for="display_order">Display Order</label>
            <input type="number" id="display_order" name="display_order" value="0" min="0">
            <small class="form-help">Lower numbers appear first</small>
        </div>

        <div class="form-group">
            <label class="checkbox">
                <input type="checkbox" name="is_active" value="1" checked>
                <span>Publish on youth page</span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Album</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>

<?php
admin_page_end();
?>
