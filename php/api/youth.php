<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_http_method('GET');

$pdo = db();

// Scripture of the week (single active row)
$scriptureStmt = $pdo->query('SELECT id, scripture_text, scripture_reference, devotional, updated_at FROM youth_scripture WHERE is_active = 1 ORDER BY updated_at DESC, created_at DESC LIMIT 1');
$scripture = $scriptureStmt->fetch(PDO::FETCH_ASSOC) ?: null;
if ($scripture) {
    $scripture['updated_at'] = format_datetime((string)$scripture['updated_at']);
}

// Announcements
$announcementStmt = $pdo->query('SELECT id, title, content, event_date, display_order, created_at FROM youth_announcements WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC');
$announcements = $announcementStmt->fetchAll(PDO::FETCH_ASSOC);

// Albums + media
$albumStmt = $pdo->query('SELECT id, title, summary, event_date, cover_media, display_order, created_at FROM youth_albums WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC');
$albums = $albumStmt->fetchAll(PDO::FETCH_ASSOC);

$albumMedia = [];
if (!empty($albums)) {
    $albumIds = array_column($albums, 'id');
    $placeholders = implode(',', array_fill(0, count($albumIds), '?'));

    $mediaStmt = $pdo->prepare("SELECT id, album_id, media_type, media_filename, media_caption, media_url, display_order, is_featured, created_at
        FROM youth_media
        WHERE album_id IN ($placeholders)
        ORDER BY album_id ASC, display_order ASC, created_at ASC");
    $mediaStmt->execute($albumIds);

    while ($row = $mediaStmt->fetch(PDO::FETCH_ASSOC)) {
        $albumId = (int)$row['album_id'];
        if (!isset($albumMedia[$albumId])) {
            $albumMedia[$albumId] = [];
        }

        $mediaUrl = media_public_url($row['media_filename']) ?? ($row['media_url'] ?: null);
        if (!$mediaUrl) {
            continue; // skip broken media entries
        }

        $albumMedia[$albumId][] = [
            'id' => (int)$row['id'],
            'type' => $row['media_type'] === 'video' ? 'video' : 'image',
            'url' => $mediaUrl,
            'caption' => $row['media_caption'],
            'is_featured' => (bool)$row['is_featured'],
            'created_at' => format_datetime((string)$row['created_at']),
        ];
    }
}

$responseAlbums = array_map(function(array $album) use ($albumMedia) {
    $id = (int)$album['id'];
    $mediaItems = $albumMedia[$id] ?? [];

    $coverPath = $album['cover_media'] !== null && $album['cover_media'] !== ''
        ? media_public_url($album['cover_media'])
        : null;

    if ($coverPath === null && !empty($mediaItems)) {
        $coverPath = $mediaItems[0]['url'];
    }

    return [
        'id' => $id,
        'title' => $album['title'],
        'summary' => $album['summary'],
        'event_date' => $album['event_date'],
        'cover' => $coverPath,
        'media' => $mediaItems,
        'created_at' => format_datetime((string)$album['created_at']),
    ];
}, $albums);

json_response([
    'scripture' => $scripture,
    'announcements' => $announcements,
    'albums' => $responseAlbums,
]);
