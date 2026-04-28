<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_http_method('GET');

$pdo = db();

$hasTitle = db_column_exists($pdo, 'live_streams', 'title');
$hasEmbedUrl = db_column_exists($pdo, 'live_streams', 'embed_url');
$hasFallback = db_column_exists($pdo, 'live_streams', 'fallback_video_url');
$hasWatchCta = db_column_exists($pdo, 'live_streams', 'watch_cta_label');
$hasStartsAt = db_column_exists($pdo, 'live_streams', 'starts_at');
$hasEndsAt = db_column_exists($pdo, 'live_streams', 'ends_at');

$titleSelect = $hasTitle ? 'COALESCE(title, stream_title) AS stream_title' : 'stream_title';
$embedUrlSelect = $hasEmbedUrl ? 'embed_url' : 'NULL AS embed_url';
$fallbackSelect = $hasFallback ? 'fallback_video_url' : 'NULL AS fallback_video_url';
$watchCtaSelect = $hasWatchCta ? 'watch_cta_label' : "'Watch Live Now' AS watch_cta_label";
$windowSql = $hasStartsAt && $hasEndsAt
    ? 'AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW())'
    : '';

$stmt = $pdo->query("
    SELECT id, {$titleSelect}, embed_code, {$embedUrlSelect}, youtube_video_id, {$fallbackSelect}, {$watchCtaSelect}, is_active, updated_at, created_at
    FROM live_streams
    WHERE is_active = 1
      {$windowSql}
    ORDER BY updated_at DESC
    LIMIT 1
");
$stream = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

if ($stream) {
    $stream['is_active'] = (bool)$stream['is_active'];
    $stream['title'] = $stream['stream_title'];
    if (!$stream['embed_url'] && !empty($stream['youtube_video_id'])) {
        $stream['embed_url'] = 'https://www.youtube.com/embed/' . $stream['youtube_video_id'];
    }
}

json_response([
    'stream' => $stream,
    'fallback' => $stream === null,
    'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('America/Chicago')))->format(DateTimeInterface::ATOM),
]);
