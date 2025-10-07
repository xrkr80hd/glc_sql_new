<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_http_method('GET');

$pdo = db();

$stmt = $pdo->query('SELECT id, stream_title, embed_code, youtube_video_id, is_active, updated_at, created_at FROM live_streams WHERE is_active = 1 ORDER BY updated_at DESC LIMIT 1');
$stream = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

if ($stream) {
    $stream['is_active'] = (bool)$stream['is_active'];
}

json_response([
    'stream' => $stream,
    'fallback' => $stream === null,
    'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('America/Chicago')))->format(DateTimeInterface::ATOM),
]);
