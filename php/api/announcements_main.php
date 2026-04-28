<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_http_method('GET');

$pdo = db();

// Get published sitewide announcements within date range
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');
$hasStartsAt = db_column_exists($pdo, 'announcements', 'starts_at');
$hasEndsAt = db_column_exists($pdo, 'announcements', 'ends_at');
$hasImageUrl = db_column_exists($pdo, 'announcements', 'image_url');
$hasImageAlt = db_column_exists($pdo, 'announcements', 'image_alt');

$startsSelect = $hasStartsAt ? 'a.starts_at' : 'a.start_date AS starts_at';
$endsSelect = $hasEndsAt ? 'a.ends_at' : 'a.end_date AS ends_at';
$imageSelect = $hasImageUrl ? 'a.image_url' : 'NULL AS image_url';
$imageAltSelect = $hasImageAlt ? 'a.image_alt' : 'NULL AS image_alt';
$windowSql = $hasStartsAt && $hasEndsAt
    ? 'AND (a.starts_at IS NULL OR a.starts_at <= :now1) AND (a.ends_at IS NULL OR a.ends_at >= :now2)'
    : 'AND (a.start_date IS NULL OR a.start_date <= :today1) AND (a.end_date IS NULL OR a.end_date >= :today2)';
$orderSql = $hasStartsAt
    ? 'a.sort_order ASC, a.starts_at DESC, a.updated_at DESC'
    : 'a.sort_order ASC, a.start_date DESC, a.updated_at DESC';

$stmt = $pdo->prepare("
    SELECT 
        a.id, 
        a.title, 
        a.body, 
        a.start_date, 
        a.end_date,
        {$startsSelect},
        {$endsSelect},
        {$imageSelect},
        {$imageAltSelect},
        a.sort_order,
        a.created_at
    FROM announcements a
    WHERE a.category IN ('main', 'global')
        AND a.is_published = 1
        {$windowSql}
    ORDER BY {$orderSql}
");

$stmt->execute($hasStartsAt && $hasEndsAt
    ? [':now1' => $now, ':now2' => $now]
    : [':today1' => $today, ':today2' => $today]
);
$announcements = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Get photos for this announcement
    $photoStmt = $pdo->prepare('
        SELECT file_path, alt 
        FROM announcement_photos 
        WHERE announcement_id = ? 
        ORDER BY sort_order ASC, id ASC
    ');
    $photoStmt->execute([$row['id']]);
    $photos = $photoStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($row['image_url'])) {
        array_unshift($photos, [
            'file_path' => $row['image_url'],
            'alt' => $row['image_alt'] ?: $row['title'],
        ]);
    }

    $row['photos'] = $photos;
    $announcements[] = $row;
}

json_response([
    'announcements' => $announcements,
    'count' => count($announcements),
]);
