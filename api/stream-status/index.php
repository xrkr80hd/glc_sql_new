<?php
declare(strict_types=1);

require_once __DIR__ . '/../../php/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

try {
    $pdo = db();
    
    // Get active stream
    $stmt = $pdo->query("
        SELECT is_active, embed_code, stream_title 
        FROM live_streams 
        WHERE is_active = 1 
        LIMIT 1
    ");
    
    $stream = $stmt->fetch();
    
    if ($stream && $stream['is_active']) {
        // Stream is live
        json_response([
            'is_live' => true,
            'embed_code' => $stream['embed_code'] ?? '',
            'stream_title' => $stream['stream_title'] ?? 'Live Stream',
            'redirect_url' => '/live2.html'
        ]);
    } else {
        // No active stream
        json_response([
            'is_live' => false,
            'embed_code' => '',
            'stream_title' => '',
            'redirect_url' => '/live.html'
        ]);
    }
    
} catch (Throwable $e) {
    error_log('Stream status error: ' . $e->getMessage());
    json_response([
        'is_live' => false,
        'error' => 'Failed to check stream status'
    ], 500);
}
