<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

verify_csrf($_POST['csrf_token'] ?? '');

$id = (int)($_POST['id'] ?? 0);

if ($id === 0) {
    header('Location: index.php?error=' . urlencode('Invalid album ID'));
    exit;
}

$pdo = db();

// Get album info to delete cover and media files
$stmt = $pdo->prepare("SELECT cover_media FROM youth_albums WHERE id = ?");
$stmt->execute([$id]);
$album = $stmt->fetch();

if ($album && $album['cover_media']) {
    $coverPath = UPLOAD_DIR . '/' . $album['cover_media'];
    if (file_exists($coverPath)) {
        unlink($coverPath);
    }
}

// Get all media files
$stmt = $pdo->prepare("SELECT media_filename FROM youth_media WHERE album_id = ? AND media_filename IS NOT NULL");
$stmt->execute([$id]);
$mediaFiles = $stmt->fetchAll();

foreach ($mediaFiles as $media) {
    $mediaPath = UPLOAD_DIR . '/' . $media['media_filename'];
    if (file_exists($mediaPath)) {
        unlink($mediaPath);
    }
}

// Delete album (cascade will delete media records)
$stmt = $pdo->prepare("DELETE FROM youth_albums WHERE id = ?");
$stmt->execute([$id]);

header('Location: index.php?message=' . urlencode('Album and all photos deleted successfully'));
exit;
