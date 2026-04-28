<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

$projectRoot = dirname(__DIR__, 3);
$liveStatusPath = $projectRoot . '/assets/data/live.json';

/**
 * Load the current live.json status file.
 *
 * @return array<string, mixed>
 */
function glc_load_live_status(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Persist manual livestream overrides to live.json.
 * Accepts LS1/LS2/manual message context and stores raw embed HTML when provided.
 *
 * @param array<string, mixed> $context
 */
function glc_update_live_status_manual(string $path, string $mode, array $context = []): void
{
    $status = glc_load_live_status($path);
    $now = (new DateTimeImmutable('now', new DateTimeZone('America/Chicago')))->format(DateTimeInterface::ATOM);

    $mode = strtolower($mode);
    if (!in_array($mode, ['auto', 'ls1', 'ls2'], true)) {
        $mode = 'auto';
    }

    if ($mode === 'auto') {
        unset($status['manualMode'], $status['manualMessage'], $status['manualEmbedHtml'], $status['manualIsLive']);
        $status['manualUpdatedAt'] = $now;
    } elseif ($mode === 'ls1') {
        $status['manualMode'] = 'LS1';
        $status['manualUpdatedAt'] = $now;
        $status['manualIsLive'] = true;

        if (!empty($context['title'])) {
            $status['title'] = (string) $context['title'];
        }

        if (array_key_exists('videoId', $context)) {
            $status['videoId'] = $context['videoId'] !== '' ? $context['videoId'] : null;
        }

        if (!empty($context['embedHtml'])) {
            $status['manualEmbedHtml'] = (string) $context['embedHtml'];
        } else {
            unset($status['manualEmbedHtml']);
        }

        $status['isLive'] = isset($context['isActive']) ? (bool) $context['isActive'] : true;
        unset($status['manualMessage']);
    } else { // ls2
        $status['manualMode'] = 'LS2';
        $status['manualUpdatedAt'] = $now;
        $status['manualIsLive'] = false;
        $status['isLive'] = false;
        $status['videoId'] = null;

        if (!empty($context['message'])) {
            $status['manualMessage'] = (string) $context['message'];
        } else {
            unset($status['manualMessage']);
        }

        unset($status['manualEmbedHtml']);
    }

    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    file_put_contents($path, json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

$pdo = db();
$currentLiveStatus = glc_load_live_status($liveStatusPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    $streamTitle = trim((string) ($_POST['stream_title'] ?? ''));
    $youtubeVideoId = trim((string) ($_POST['youtube_video_id'] ?? ''));
    $embedCode = trim((string) ($_POST['embed_code'] ?? ''));
    $fallbackMessage = trim((string) ($_POST['fallback_message'] ?? ''));
    $streamAction = strtolower(trim((string) ($_POST['stream_action'] ?? '')));
    $postedCurrentState = (int) ($_POST['current_live_state'] ?? 0);
    $postedCurrentMode = strtolower(trim((string) ($_POST['current_manual_mode'] ?? (string) ($currentLiveStatus['manualMode'] ?? 'auto'))));
    $isActive = $postedCurrentState === 1 ? 1 : 0;

    if ($streamAction === 'go_live') {
        $isActive = 1;
        $streamMode = 'ls1';
    } elseif ($streamAction === 'stop_live') {
        $isActive = 0;
        $streamMode = 'ls2';
    } else {
        $streamMode = in_array($postedCurrentMode, ['auto', 'ls1', 'ls2'], true) ? $postedCurrentMode : 'auto';
    }

    if ($streamAction === 'go_live' && $embedCode === '') {
        admin_flash('error', 'Add the stream embed code before going live.');
        admin_redirect('/php/admin/stream/index.php');
    }

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->query('SELECT * FROM live_streams ORDER BY updated_at DESC LIMIT 1');
        $currentStream = $stmt ? $stmt->fetch() : false;

        if ($currentStream) {
            $update = $pdo->prepare('UPDATE live_streams SET embed_code = :embed, stream_title = :title, youtube_video_id = :youtube, is_active = :active WHERE id = :id');
            $update->execute([
                ':embed'   => $embedCode !== '' ? $embedCode : null,
                ':title'   => $streamTitle !== '' ? $streamTitle : null,
                ':youtube' => $youtubeVideoId !== '' ? $youtubeVideoId : null,
                ':active'  => $isActive,
                ':id'      => $currentStream['id'],
            ]);

            if ($isActive === 1) {
                $deactivate = $pdo->prepare('UPDATE live_streams SET is_active = 0 WHERE id <> :id');
                $deactivate->execute([':id' => $currentStream['id']]);
            } else {
                $pdo->exec('UPDATE live_streams SET is_active = 0');
            }
        } else {
            $insert = $pdo->prepare('INSERT INTO live_streams (embed_code, stream_title, youtube_video_id, is_active) VALUES (:embed, :title, :youtube, :active)');
            $insert->execute([
                ':embed'   => $embedCode !== '' ? $embedCode : null,
                ':title'   => $streamTitle !== '' ? $streamTitle : null,
                ':youtube' => $youtubeVideoId !== '' ? $youtubeVideoId : null,
                ':active'  => $isActive,
            ]);

            if ($isActive === 1) {
                $newId = (int) $pdo->lastInsertId();
                $deactivate = $pdo->prepare('UPDATE live_streams SET is_active = 0 WHERE id <> :id');
                $deactivate->execute([':id' => $newId]);
            } else {
                $pdo->exec('UPDATE live_streams SET is_active = 0');
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        admin_flash('error', 'Failed to update live stream: ' . $e->getMessage());
        admin_redirect('/php/admin/stream/index.php');
    }

    glc_update_live_status_manual($liveStatusPath, $streamMode, [
        'message'   => $fallbackMessage,
        'title'     => $streamTitle,
        'videoId'   => $youtubeVideoId,
        'embedHtml' => $embedCode,
        'isActive'  => $isActive === 1,
    ]);

    admin_flash('success', 'Live stream settings updated successfully.');
    admin_redirect('/php/admin/stream/index.php');
}

$stmt = $pdo->query('SELECT * FROM live_streams ORDER BY updated_at DESC LIMIT 1');
$activeStream = $stmt ? $stmt->fetch() : null;

$stmt = $pdo->query('SELECT * FROM live_streams ORDER BY created_at DESC LIMIT 10');
$recentStreams = $stmt ? $stmt->fetchAll() : [];

$liveStatus = glc_load_live_status($liveStatusPath);
$manualMode = strtoupper($liveStatus['manualMode'] ?? 'AUTO');
$manualUpdatedAt = $liveStatus['manualUpdatedAt'] ?? null;
$manualMessage = $liveStatus['manualMessage'] ?? '';
$manualEmbedHtml = $liveStatus['manualEmbedHtml'] ?? '';
$isCurrentlyLive = $manualMode === 'LS1' ? true : ($manualMode === 'LS2' ? false : (bool) ($activeStream['is_active'] ?? false));
$statusText = $isCurrentlyLive ? 'Status: Live now' : 'Status: Offline';

admin_page_start('Live Stream Control', 'stream');
?>

<?php if ($manualMode !== 'AUTO'): ?>
    <section class="card">
        <div class="alert alert-info" style="margin-bottom: 1.5rem;">
            Stream control override active:
            <strong><?= $manualMode === 'LS1' ? 'Live mode' : 'Fallback mode' ?></strong>
            <?php if ($manualUpdatedAt): ?>
                <small>(since <?= htmlspecialchars($manualUpdatedAt) ?>)</small>
            <?php endif; ?>
            <?php if ($manualMode === 'LS2' && $manualMessage): ?>
                <div style="margin-top: 0.5rem;">Message: <?= nl2br(htmlspecialchars($manualMessage)) ?></div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<section class="card">
    <div class="card-header">
        <h2>📡 Live Stream Control</h2>
    </div>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="current_live_state" value="<?= $isCurrentlyLive ? '1' : '0' ?>">
        <input type="hidden" name="current_manual_mode" value="<?= strtolower($manualMode) ?>">

        <div class="form-group">
            <label for="stream_title">Stream Title</label>
            <input type="text" id="stream_title" name="stream_title" placeholder="e.g., Sunday Morning Worship Service" value="<?= htmlspecialchars($activeStream['stream_title'] ?? '') ?>">
        </div>

        <details class="admin-accordion" open>
            <summary>Embed Code</summary>
            <div class="accordion-panel">
                <div class="form-group admin-accordion-field">
                    <label for="embed_code">Primary Stream Embed Code</label>
                    <textarea id="embed_code" name="embed_code" rows="8" placeholder="Paste full YouTube/Vimeo embed code here (e.g., &lt;iframe src=...&gt;&lt;/iframe&gt;)" class="stream-code-input"><?= htmlspecialchars($activeStream['embed_code'] ?? '') ?></textarea>
                    <small class="form-help">Raw embed HTML is saved and rendered exactly as provided. No conversion is performed.</small>
                </div>
            </div>
        </details>

        <div class="form-group">
            <label for="fallback_message">Fallback Message</label>
            <textarea id="fallback_message" name="fallback_message" rows="3" placeholder="Displayed under the fallback video when LS2 is forced."><?= htmlspecialchars($manualMessage) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Save Stream Settings</button>
        </div>

        <div class="live-control-box" id="liveControlBox">
            <p class="live-control-status"><?= htmlspecialchars($statusText) ?></p>
            <small class="form-help">Save the stream settings first, then switch the site live or send it back to fallback.</small>
            <div class="live-control-actions">
                <button type="submit" name="stream_action" value="go_live" class="stream-action-btn stream-action-btn-go <?= $isCurrentlyLive ? 'is-muted' : 'is-primary' ?>">
                    Go Live
                </button>
                <button type="submit" name="stream_action" value="stop_live" class="stream-action-btn stream-action-btn-stop <?= $isCurrentlyLive ? 'is-primary' : 'is-muted' ?>" data-stop-live>
                    Stop Live
                </button>
            </div>
        </div>
    </form>
</section>

<section class="card">
    <details class="admin-accordion">
        <summary>Stream Monitor</summary>
        <div class="accordion-panel">
            <?php if ($manualMode === 'LS1' && $manualEmbedHtml): ?>
                <div class="stream-preview">
                    <?= $manualEmbedHtml ?>
                </div>
            <?php elseif ($activeStream && $activeStream['is_active'] && !empty($activeStream['embed_code'])): ?>
                <div class="stream-preview">
                    <?= $activeStream['embed_code'] ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No active stream. Configure stream settings above to preview.</p>
                </div>
            <?php endif; ?>
        </div>
    </details>
</section>

<section class="card">
    <h3>📜 Recent Streams</h3>

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
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentStreams as $stream): ?>
                    <tr>
                        <td>
                            <?php if (!empty($stream['is_active'])): ?>
                                <span class="badge badge-success">🔴 Live</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Ended</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($stream['stream_title'] ?? 'Untitled Stream') ?></td>
                        <td><?= htmlspecialchars(format_datetime($stream['created_at'] ?? null) ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<style>
.live-control-box {
    display: grid;
    gap: 1rem;
    padding: 1.25rem;
    margin-top: 1rem;
    border: 1px solid rgba(103, 123, 111, 0.18);
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(248, 251, 249, 0.96), rgba(239, 245, 241, 0.96));
    box-shadow: 0 12px 28px rgba(16, 24, 20, 0.06);
}

.live-control-status {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: #284536;
    letter-spacing: 0.02em;
}

.live-control-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
}

.stream-action-btn {
    appearance: none;
    border: 1px solid transparent;
    border-radius: 14px;
    min-height: 52px;
    padding: 0.9rem 1.1rem;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.stream-action-btn:hover {
    filter: brightness(0.98);
    transform: translateY(-1px);
}

.stream-action-btn:focus-visible {
    outline: 3px solid rgba(31, 138, 76, 0.22);
    outline-offset: 2px;
}

.stream-action-btn-go.is-primary {
    background: #1f8a4c;
    color: #fff;
    box-shadow: 0 12px 28px rgba(31, 138, 76, 0.22);
}

.stream-action-btn-stop.is-primary {
    background: #c53b3b;
    color: #fff;
    box-shadow: 0 12px 28px rgba(197, 59, 59, 0.22);
}

.stream-action-btn.is-muted {
    background: #eef2ef;
    color: #607166;
    border-color: #d7dfda;
    box-shadow: none;
}

.stream-preview {
    position: relative;
    padding-bottom: 56.25%;
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

.admin-accordion {
    margin-bottom: 1rem;
    border: 1px solid rgba(103, 123, 111, 0.18);
    border-radius: 16px;
    background: #f8fbf9;
    overflow: hidden;
}

.admin-accordion summary {
    list-style: none;
    cursor: pointer;
    padding: 1rem 1.25rem;
    font-size: 1rem;
    font-weight: 700;
    color: #284536;
    background: linear-gradient(180deg, rgba(248, 251, 249, 0.98), rgba(239, 245, 241, 0.98));
}

.admin-accordion summary::-webkit-details-marker {
    display: none;
}

.admin-accordion summary::after {
    content: '+';
    float: right;
    font-size: 1.2rem;
    line-height: 1;
    color: #607166;
}

.admin-accordion[open] summary::after {
    content: '−';
}

.accordion-panel {
    padding: 0 1.25rem 1.25rem;
    border-top: 1px solid rgba(103, 123, 111, 0.12);
    background: #fff;
}

.admin-accordion-field {
    margin-bottom: 0;
    padding-top: 1rem;
}

.stream-code-input {
    width: 100%;
    min-height: 220px;
    font-family: monospace;
    font-size: 0.92rem;
    line-height: 1.5;
    resize: vertical;
}

@media (max-width: 640px) {
    .live-control-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const savedScrollY = window.sessionStorage.getItem('glcAdminStreamScrollY');
    if (savedScrollY !== null) {
        window.scrollTo({ top: Number(savedScrollY), behavior: 'auto' });
        window.sessionStorage.removeItem('glcAdminStreamScrollY');
    }

    const streamForm = document.querySelector('form[method="POST"]');
    if (streamForm) {
        streamForm.addEventListener('submit', function () {
            window.sessionStorage.setItem('glcAdminStreamScrollY', String(window.scrollY));
        });
    }

    const stopButton = document.querySelector('[data-stop-live]');
    if (!stopButton) return;

    stopButton.addEventListener('click', function (event) {
        const ok = window.confirm('Stop live stream? This will turn off the live signal and return viewers to the fallback page.');
        if (!ok) {
            event.preventDefault();
        }
    });
});
</script>

<?php
admin_page_end();
?>
