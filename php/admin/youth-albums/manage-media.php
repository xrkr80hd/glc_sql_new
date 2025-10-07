<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

$pdo = db();

$albumId = (int)($_GET['album_id'] ?? 0);
if ($albumId === 0) {
    header('Location: index.php?error=' . urlencode('Invalid album ID'));
    exit;
}

// Fetch album
$stmt = $pdo->prepare("SELECT * FROM youth_albums WHERE id = ?");
$stmt->execute([$albumId]);
$album = $stmt->fetch();

if (!$album) {
    header('Location: index.php?error=' . urlencode('Album not found'));
    exit;
}

// Handle media upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_files'])) {
    verify_csrf($_POST['csrf_token'] ?? '');
    
    ensure_upload_directory();
    
    $files = $_FILES['media_files'];
    $uploadedCount = 0;
    
    // Handle multiple files
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'mov'];
        
        if (!in_array($extension, $allowedExtensions)) {
            continue;
        }
        
        if ($files['size'][$i] > MAX_UPLOAD_BYTES) {
            continue;
        }
        
        $mediaType = in_array($extension, ['mp4', 'mov']) ? 'video' : 'image';
        $filename = uniqid('youth_media_') . '.' . $extension;
        $destination = UPLOAD_DIR . '/' . $filename;
        
        if (move_uploaded_file($files['tmp_name'][$i], $destination)) {
            $sql = "INSERT INTO youth_media (album_id, media_type, media_filename, display_order)
                    VALUES (:album_id, :media_type, :filename, 0)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':album_id' => $albumId,
                ':media_type' => $mediaType,
                ':filename' => $filename,
            ]);
            
            $uploadedCount++;
        }
    }
    
    header('Location: manage-media.php?album_id=' . $albumId . '&message=' . urlencode($uploadedCount . ' file(s) uploaded successfully'));
    exit;
}

// Handle media deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_media_id'])) {
    verify_csrf($_POST['csrf_token'] ?? '');
    
    $mediaId = (int)$_POST['delete_media_id'];
    
    $stmt = $pdo->prepare("SELECT media_filename FROM youth_media WHERE id = ? AND album_id = ?");
    $stmt->execute([$mediaId, $albumId]);
    $media = $stmt->fetch();
    
    if ($media && $media['media_filename']) {
        $mediaPath = UPLOAD_DIR . '/' . $media['media_filename'];
        if (file_exists($mediaPath)) {
            unlink($mediaPath);
        }
        
        $stmt = $pdo->prepare("DELETE FROM youth_media WHERE id = ?");
        $stmt->execute([$mediaId]);
    }
    
    header('Location: manage-media.php?album_id=' . $albumId . '&message=' . urlencode('Photo deleted successfully'));
    exit;
}

// Fetch all media for this album
$stmt = $pdo->prepare("
    SELECT * FROM youth_media 
    WHERE album_id = ? 
    ORDER BY display_order ASC, created_at DESC
");
$stmt->execute([$albumId]);
$mediaItems = $stmt->fetchAll();

$message = $_GET['message'] ?? '';

admin_page_start('Manage Photos - ' . htmlspecialchars($album['title']), 'youth-albums');
?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<section class="card">
    <div class="card-header">
        <h2>📸 <?= htmlspecialchars($album['title']) ?></h2>
        <a href="index.php" class="btn btn-secondary">← Back to Albums</a>
    </div>
    
    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        
        <div class="form-group">
            <label for="media_files">Upload Photos/Videos</label>
            <input type="file" id="media_files" name="media_files[]" multiple accept="image/*,video/mp4,video/quicktime">
            <small class="form-help">Select multiple files to upload at once (max <?= MAX_UPLOAD_BYTES / 1024 / 1024 ?>MB each)</small>
        </div>
        
        <button type="submit" class="btn btn-primary">📤 Upload Files</button>
    </form>
</section>

<?php if (empty($mediaItems)): ?>
    <section class="card">
        <div class="empty-state">
            <p>No photos in this album yet. Upload some to get started!</p>
        </div>
    </section>
<?php else: ?>
    <section class="card">
        <h3>Album Contents (<?= count($mediaItems) ?> items)</h3>
        
        <div class="media-grid">
            <?php foreach ($mediaItems as $media): ?>
                <div class="media-item">
                    <?php if ($media['media_type'] === 'image'): ?>
                        <img src="/uploads/<?= htmlspecialchars($media['media_filename']) ?>" alt="Youth photo">
                    <?php else: ?>
                        <video src="/uploads/<?= htmlspecialchars($media['media_filename']) ?>" controls></video>
                    <?php endif; ?>
                    
                    <div class="media-actions">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this photo?');">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="delete_media_id" value="<?= (int)$media['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<style>
.upload-form {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.media-item {
    position: relative;
    aspect-ratio: 1;
    background: #f0f0f0;
    border-radius: 8px;
    overflow: hidden;
}

.media-item img,
.media-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-actions {
    position: absolute;
    bottom: 0.5rem;
    right: 0.5rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.media-item:hover .media-actions {
    opacity: 1;
}
</style>

<?php
admin_page_end();
?>
