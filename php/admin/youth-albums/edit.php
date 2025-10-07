<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: index.php?error=' . urlencode('Invalid album ID'));
    exit;
}

// Fetch album
$stmt = $pdo->prepare("SELECT * FROM youth_albums WHERE id = ?");
$stmt->execute([$id]);
$album = $stmt->fetch();

if (!$album) {
    header('Location: index.php?error=' . urlencode('Album not found'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') {
        header('Location: edit.php?id=' . $id . '&error=' . urlencode('Album title is required'));
        exit;
    }

    $coverMedia = $album['cover_media'];

    // Handle new cover photo upload
    if (isset($_FILES['cover_media']) && $_FILES['cover_media']['error'] === UPLOAD_ERR_OK) {
        ensure_upload_directory();
        
        $uploadedFile = $_FILES['cover_media'];
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($extension, $allowedExtensions) && $uploadedFile['size'] <= MAX_UPLOAD_BYTES) {
            // Delete old cover
            if ($coverMedia && file_exists(UPLOAD_DIR . '/' . $coverMedia)) {
                unlink(UPLOAD_DIR . '/' . $coverMedia);
            }
            
            $filename = uniqid('album_cover_') . '.' . $extension;
            $destination = UPLOAD_DIR . '/' . $filename;
            
            if (move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
                $coverMedia = $filename;
            }
        }
    }

    $sql = "UPDATE youth_albums 
            SET title = :title, summary = :summary, event_date = :event_date, 
                cover_media = :cover_media, display_order = :display_order, is_active = :is_active
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':summary' => $summary,
        ':event_date' => $eventDate ?: null,
        ':cover_media' => $coverMedia,
        ':display_order' => $displayOrder,
        ':is_active' => $isActive,
        ':id' => $id,
    ]);

    header('Location: index.php?message=' . urlencode('Album updated successfully'));
    exit;
}

admin_page_start('Edit Album', 'youth-albums');
?>

<section class="card">
    <h2>Edit Album</h2>

    <form method="POST" enctype="multipart/form-data" class="form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label for="title">Album Title *</label>
            <input type="text" id="title" name="title" required maxlength="255" value="<?= htmlspecialchars($album['title']) ?>">
        </div>

        <div class="form-group">
            <label for="summary">Summary</label>
            <textarea id="summary" name="summary" rows="3"><?= htmlspecialchars($album['summary'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="event_date">Event Date</label>
            <input type="date" id="event_date" name="event_date" value="<?= htmlspecialchars($album['event_date'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="cover_media">Cover Photo</label>
            <?php if ($album['cover_media']): ?>
                <div style="margin-bottom: 0.5rem;">
                    <img src="/uploads/<?= htmlspecialchars($album['cover_media']) ?>" alt="Current cover" style="max-width: 200px; border-radius: 4px;">
                    <p class="muted" style="margin-top: 0.25rem;">Current cover (upload a new one to replace)</p>
                </div>
            <?php endif; ?>
            <input type="file" id="cover_media" name="cover_media" accept="image/jpeg,image/png,image/webp">
        </div>

        <div class="form-group">
            <label for="display_order">Display Order</label>
            <input type="number" id="display_order" name="display_order" value="<?= (int)$album['display_order'] ?>" min="0">
        </div>

        <div class="form-group">
            <label class="checkbox">
                <input type="checkbox" name="is_active" value="1" <?= $album['is_active'] ? 'checked' : '' ?>>
                <span>Publish on youth page</span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>

<?php
admin_page_end();
?>
