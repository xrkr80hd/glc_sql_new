<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

$pdo = db();

// Handle stream toggle/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');
    
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $streamTitle = trim($_POST['stream_title'] ?? '');
    $embedCode = trim($_POST['embed_code'] ?? '');
    $serviceType = trim($_POST['service_type'] ?? 'sunday');
    
    // Deactivate all existing streams
    $pdo->exec("UPDATE live_streams SET is_active = 0");
    
    // If new stream should be active, create or update
    if ($isActive) {
        // Check if we have a stream for today
        $stmt = $pdo->prepare("
            SELECT * FROM live_streams 
            WHERE DATE(created_at) = CURDATE()
            LIMIT 1
        ");
        $stmt->execute();
        $existingStream = $stmt->fetch();
        
        if ($existingStream) {
            // Update existing
            $stmt = $pdo->prepare("
                UPDATE live_streams 
                SET is_active = 1, stream_title = ?, embed_code = ?, service_type = ?
                WHERE id = ?
            ");
            $stmt->execute([$streamTitle, $embedCode, $serviceType, $existingStream['id']]);
        } else {
            // Create new
            $stmt = $pdo->prepare("
                INSERT INTO live_streams (stream_title, embed_code, service_type, is_active)
                VALUES (?, ?, ?, 1)
            ");
            $stmt->execute([$streamTitle, $embedCode, $serviceType]);
        }
    }
    
    header('Location: index.php?message=' . urlencode('Stream updated successfully'));
    exit;
}

// Fetch current active stream
$stmt = $pdo->query("SELECT * FROM live_streams WHERE is_active = 1 LIMIT 1");
$activeStream = $stmt->fetch();

// If no active stream, fetch most recent
if (!$activeStream) {
    $stmt = $pdo->query("SELECT * FROM live_streams ORDER BY created_at DESC LIMIT 1");
    $activeStream = $stmt->fetch();
}

$message = $_GET['message'] ?? '';

admin_page_start('Live Stream Management', 'stream');
?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<section class="card">
    <div class="card-header">
        <h2>📡 Live Stream Control</h2>
    </div>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        
        <div class="stream-status">
            <div class="status-indicator <?= ($activeStream && $activeStream['is_active']) ? 'live' : 'offline' ?>">
                <?php if ($activeStream && $activeStream['is_active']): ?>
                    🔴 LIVE NOW
                <?php else: ?>
                    ⚪ Offline
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" 
                    <?= ($activeStream && $activeStream['is_active']) ? 'checked' : '' ?>>
                <span>Stream is currently live</span>
            </label>
            <small class="form-help">Check this box to make the stream visible on the website</small>
        </div>
        
        <div class="form-group">
            <label for="stream_title">Stream Title</label>
            <input 
                type="text" 
                id="stream_title" 
                name="stream_title" 
                placeholder="e.g., Sunday Morning Worship Service"
                value="<?= htmlspecialchars($activeStream['stream_title'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="embed_code">L1 Primary Stream Embed Code</label>
            <textarea 
                id="embed_code" 
                name="embed_code" 
                rows="8"
                placeholder="Paste full YouTube/Vimeo embed code here (e.g., <iframe src=...></iframe>)"
                style="font-family: monospace; font-size: 0.9rem;"><?= htmlspecialchars($activeStream['embed_code'] ?? '') ?></textarea>
            <small class="form-help">Paste the complete embed code from YouTube, Vimeo, or your streaming platform. This is your PRIMARY stream (L1).</small>
        </div>
        
        <div class="form-group">
            <label for="service_type">Service Type</label>
            <select id="service_type" name="service_type">
                <option value="sunday" <?= ($activeStream['service_type'] ?? 'sunday') === 'sunday' ? 'selected' : '' ?>>Sunday Service</option>
                <option value="wednesday" <?= ($activeStream['service_type'] ?? '') === 'wednesday' ? 'selected' : '' ?>>Wednesday Night</option>
                <option value="special" <?= ($activeStream['service_type'] ?? '') === 'special' ? 'selected' : '' ?>>Special Event</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Update Stream</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>📺 Stream Preview</h3>
    
    <?php if ($activeStream && $activeStream['is_active'] && $activeStream['embed_code']): ?>
        <div class="stream-preview">
            <?= $activeStream['embed_code'] ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No active stream. Configure stream settings above to preview.</p>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <h3>📜 Recent Streams</h3>
    
    <?php
    $stmt = $pdo->query("
        SELECT * FROM live_streams 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recentStreams = $stmt->fetchAll();
    ?>
    
    <?php if (empty($recentStreams)): ?>
        <div class="empty-state">
            <p>No stream history yet.</p>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Title</th>
                    <th>Service Type</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentStreams as $stream): ?>
                    <tr>
                        <td>
                            <?php if ($stream['is_active']): ?>
                                <span class="badge badge-success">🔴 Live</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Ended</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($stream['stream_title']) ?></td>
                        <td>
                            <?php
                            $typeLabels = [
                                'sunday' => '☀️ Sunday',
                                'wednesday' => '🌙 Wednesday',
                                'special' => '⭐ Special Event'
                            ];
                            echo $typeLabels[$stream['service_type']] ?? htmlspecialchars($stream['service_type']);
                            ?>
                        </td>
                        <td><?= format_datetime($stream['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<style>
.stream-status {
    text-align: center;
    padding: 2rem;
    margin-bottom: 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
}

.status-indicator {
    display: inline-block;
    font-size: 1.5rem;
    font-weight: bold;
    padding: 1rem 2rem;
    border-radius: 50px;
    transition: all 0.3s;
}

.status-indicator.live {
    background: #fff;
    color: #dc3545;
    box-shadow: 0 0 20px rgba(220, 53, 69, 0.5);
    animation: pulse 2s infinite;
}

.status-indicator.offline {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 20px rgba(220, 53, 69, 0.5);
    }
    50% {
        box-shadow: 0 0 30px rgba(220, 53, 69, 0.8);
    }
}

.stream-preview {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    height: 0;
    overflow: hidden;
    background: #000;
    border-radius: 8px;
}

.stream-preview iframe,
.stream-preview video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}
</style>

<?php
admin_page_end();
?>
