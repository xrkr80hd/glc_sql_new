<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_http_method('GET');

$pdo = db();

// Scripture of the week (single active row)
$hasScriptureAudience = db_column_exists($pdo, 'youth_scripture', 'audience');
$hasScriptureTitle = db_column_exists($pdo, 'youth_scripture', 'title');
$hasScriptureWeeks = db_column_exists($pdo, 'youth_scripture', 'week_start') && db_column_exists($pdo, 'youth_scripture', 'week_end');
$hasDevotionalText = db_column_exists($pdo, 'youth_scripture', 'devotional_text');
$scriptureTitleSelect = $hasScriptureTitle ? 'title' : 'NULL AS title';
$devotionalSelect = $hasDevotionalText ? 'COALESCE(devotional_text, devotional) AS devotional' : 'devotional';
$scriptureWhere = $hasScriptureAudience
    ? "audience = 'youth' AND is_published = 1"
    : 'is_active = 1';
$scriptureWhere .= $hasScriptureWeeks
    ? ' AND (week_start IS NULL OR week_start <= CURDATE()) AND (week_end IS NULL OR week_end >= CURDATE())'
    : '';
$scriptureStmt = $pdo->query("
    SELECT id, {$scriptureTitleSelect}, scripture_text, scripture_reference, {$devotionalSelect}, updated_at
    FROM youth_scripture
    WHERE {$scriptureWhere}
    ORDER BY updated_at DESC, created_at DESC
    LIMIT 1
");
$scripture = $scriptureStmt->fetch(PDO::FETCH_ASSOC) ?: null;
if ($scripture) {
    $scripture['updated_at'] = format_datetime((string)$scripture['updated_at']);
}

// Announcements
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');
$hasStartsAt = db_column_exists($pdo, 'announcements', 'starts_at');
$hasEndsAt = db_column_exists($pdo, 'announcements', 'ends_at');
$hasImageUrl = db_column_exists($pdo, 'announcements', 'image_url');
$hasImageAlt = db_column_exists($pdo, 'announcements', 'image_alt');
$startsSelect = $hasStartsAt ? 'starts_at' : 'start_date AS starts_at';
$endsSelect = $hasEndsAt ? 'ends_at' : 'end_date AS ends_at';
$imageSelect = $hasImageUrl ? 'image_url' : 'NULL AS image_url';
$imageAltSelect = $hasImageAlt ? 'image_alt' : 'NULL AS image_alt';
$windowSql = $hasStartsAt && $hasEndsAt
    ? 'AND (starts_at IS NULL OR starts_at <= :now1) AND (ends_at IS NULL OR ends_at >= :now2)'
    : 'AND (start_date IS NULL OR start_date <= :today1) AND (end_date IS NULL OR end_date >= :today2)';
$orderSql = $hasStartsAt
    ? 'sort_order ASC, starts_at DESC, updated_at DESC'
    : 'sort_order ASC, start_date DESC, updated_at DESC';
$announcementStmt = $pdo->prepare("
    SELECT id, category, title, body, start_date, end_date, {$startsSelect}, {$endsSelect}, {$imageSelect}, {$imageAltSelect}, sort_order, updated_at
    FROM announcements
    WHERE category IN ('youth', 'global')
      AND is_published = 1
      {$windowSql}
    ORDER BY {$orderSql}
");
$announcementStmt->execute($hasStartsAt && $hasEndsAt
    ? [':now1' => $now, ':now2' => $now]
    : [':today1' => $today, ':today2' => $today]
);
$announcements = [];
while ($row = $announcementStmt->fetch(PDO::FETCH_ASSOC)) {
    $photos = $pdo->prepare('SELECT file_path, alt FROM announcement_photos WHERE announcement_id=? ORDER BY sort_order ASC, id ASC');
    $photos->execute([$row['id']]);
    $imgs = $photos->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($row['image_url'])) {
        array_unshift($imgs, [
            'file_path' => $row['image_url'],
            'alt' => $row['image_alt'] ?: $row['title'],
        ]);
    }
    $row['photos'] = $imgs;
    $announcements[] = $row;
}

// Albums + media
$hasAlbumDate = db_column_exists($pdo, 'youth_albums', 'album_date');
$hasCoverImage = db_column_exists($pdo, 'youth_albums', 'cover_image_url');
$albumDateSelect = $hasAlbumDate ? 'COALESCE(album_date, event_date) AS event_date' : 'event_date';
$coverSelect = $hasCoverImage ? 'COALESCE(cover_image_url, cover_media) AS cover_media' : 'cover_media';
$albumStmt = $pdo->query("SELECT id, title, summary, {$albumDateSelect}, {$coverSelect}, display_order, created_at FROM youth_albums WHERE is_active = 1 AND is_published = 1 ORDER BY display_order ASC, created_at DESC");
$albums = $albumStmt->fetchAll(PDO::FETCH_ASSOC);

$albumMedia = [];
if (!empty($albums)) {
    $albumIds = array_column($albums, 'id');
    $placeholders = implode(',', array_fill(0, count($albumIds), '?'));

    $hasMediaTitle = db_column_exists($pdo, 'youth_media', 'title');
    $hasMediaDescription = db_column_exists($pdo, 'youth_media', 'description');
    $hasMediaThumbnail = db_column_exists($pdo, 'youth_media', 'thumbnail_url');
    $hasMediaTakenOn = db_column_exists($pdo, 'youth_media', 'taken_on');
    $hasMediaPublished = db_column_exists($pdo, 'youth_media', 'is_published');
    $titleSelect = $hasMediaTitle ? 'title' : 'NULL AS title';
    $descriptionSelect = $hasMediaDescription ? 'description' : 'NULL AS description';
    $thumbnailSelect = $hasMediaThumbnail ? 'thumbnail_url' : 'NULL AS thumbnail_url';
    $takenOnSelect = $hasMediaTakenOn ? 'taken_on' : 'NULL AS taken_on';
    $publishedSql = $hasMediaPublished ? 'AND is_published = 1' : '';

    $mediaStmt = $pdo->prepare("SELECT id, album_id, media_type, media_filename, media_caption, media_url, {$titleSelect}, {$descriptionSelect}, {$thumbnailSelect}, {$takenOnSelect}, display_order, is_featured, created_at
        FROM youth_media
        WHERE album_id IN ($placeholders)
          {$publishedSql}
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
            'title' => $row['title'] ?: $row['media_caption'],
            'caption' => $row['media_caption'],
            'description' => $row['description'],
            'thumbnail_url' => media_public_url($row['thumbnail_url']) ?? $row['thumbnail_url'],
            'taken_on' => $row['taken_on'],
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
