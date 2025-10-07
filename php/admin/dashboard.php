<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';

admin_require_login();

$pdo = db();

$metrics = [
    'active_announcements' => (int) $pdo->query("SELECT COUNT(*) FROM announcements WHERE is_published = 1")->fetchColumn(),
    'prayer_open'          => (int) $pdo->query("SELECT COUNT(*) FROM prayer_requests WHERE is_prayed = 0")->fetchColumn(),
    'visit_new'            => (int) $pdo->query("SELECT COUNT(*) FROM visit_submissions WHERE is_read = 0")->fetchColumn(),
    'youth_albums'         => (int) $pdo->query("SELECT COUNT(*) FROM youth_albums WHERE is_published = 1")->fetchColumn(),
    'youth_media'          => (int) $pdo->query("SELECT COUNT(*) FROM youth_media")->fetchColumn(),
];

$recentVisits = $pdo->query("SELECT name, email, visit_date, created_at FROM visit_submissions ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentPrayers = $pdo->query("SELECT name, request, created_at, share_permission FROM prayer_requests ORDER BY created_at DESC LIMIT 5")->fetchAll();
$liveStream = $pdo->query("SELECT stream_title, is_active, updated_at FROM live_streams ORDER BY updated_at DESC LIMIT 1")->fetch();
$youthScripture = $pdo->query("SELECT scripture_reference, created_at FROM youth_scripture WHERE is_active = 1 LIMIT 1")->fetch();

admin_page_start('Dashboard', 'dashboard');
?>
<section class="metrics-grid">
    <div class="metric-card">
        <h3>📢 Announcements</h3>
        <strong><?= number_format($metrics['active_announcements']) ?></strong>
        <small>Published across all categories</small>
    </div>
    <div class="metric-card">
        <h3>🙏 Prayer Requests</h3>
        <strong><?= number_format($metrics['prayer_open']) ?></strong>
        <small>Open requests needing prayer</small>
    </div>
    <div class="metric-card">
        <h3>👋 Visit Forms</h3>
        <strong><?= number_format($metrics['visit_new']) ?></strong>
        <small>Unread submissions</small>
    </div>
    <div class="metric-card">
        <h3>📸 Youth Albums</h3>
        <strong><?= number_format($metrics['youth_albums']) ?></strong>
        <small>Published photo albums</small>
    </div>
    <div class="metric-card">
        <h3>🖼️ Youth Photos</h3>
        <strong><?= number_format($metrics['youth_media']) ?></strong>
        <small>Total photos & videos</small>
    </div>
</section>

<section class="card">
    <h3>📡 Live Stream Status</h3>
    <?php if ($liveStream): ?>
        <p>
            <span class="badge <?= ((int)$liveStream['is_active']) ? '' : 'muted' ?>">
                <?= ((int)$liveStream['is_active']) ? '🔴 Live' : 'Offline' ?>
            </span>
            <?= htmlspecialchars($liveStream['stream_title'] ?? 'No title set') ?>
        </p>
        <?php if (!empty($liveStream['updated_at'])): ?>
            <small>Updated <?= htmlspecialchars(format_datetime((string)$liveStream['updated_at'])) ?></small>
        <?php endif; ?>
        <div style="margin-top: 1rem;">
            <a class="btn btn-secondary" href="/php/admin/stream/index.php">Manage Stream</a>
            <a class="btn btn-secondary" href="/live.html" target="_blank" rel="noopener">View Live Page</a>
        </div>
    <?php else: ?>
        <div class="empty-state">No live stream has been configured yet.</div>
        <a class="btn btn-primary" href="/php/admin/stream/index.php">Configure Stream</a>
    <?php endif; ?>
</section>

<section class="card">
    <h3>📖 Youth Scripture of the Week</h3>
    <?php if ($youthScripture): ?>
        <p>
            <strong><?= htmlspecialchars($youthScripture['scripture_reference']) ?></strong>
        </p>
        <small>Set <?= htmlspecialchars(format_datetime((string)$youthScripture['created_at'])) ?></small>
        <div style="margin-top: 1rem;">
            <a class="btn btn-secondary" href="/php/admin/youth-scripture/index.php">Update Scripture</a>
        </div>
    <?php else: ?>
        <div class="empty-state">No youth scripture has been set yet.</div>
        <a class="btn btn-primary" href="/php/admin/youth-scripture/index.php">Add Scripture</a>
    <?php endif; ?>
</section>

<section class="card">
    <h3>Recent Visit Submissions</h3>
    <?php if ($recentVisits): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email / Visit Date</th>
                    <th>Received</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentVisits as $visit): ?>
                <tr>
                    <td><?= htmlspecialchars($visit['name']) ?></td>
                    <td>
                        <?= htmlspecialchars($visit['email']) ?><br>
                        <?php if (!empty($visit['visit_date'])): ?>
                            <small>Visit: <?= htmlspecialchars($visit['visit_date']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars(format_datetime((string)$visit['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">No recent visit submissions.</div>
    <?php endif; ?>
</section>

<section class="card">
    <h3>Latest Prayer Requests</h3>
    <?php if ($recentPrayers): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Request</th>
                    <th>Shared?</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentPrayers as $prayer): ?>
                <tr>
                    <td><?= htmlspecialchars($prayer['name'] ?: 'Anonymous') ?><br><small><?= htmlspecialchars(format_datetime((string)$prayer['created_at'])) ?></small></td>
                    <td><?= nl2br(htmlspecialchars($prayer['request'])) ?></td>
                    <td>
                        <?= (int)$prayer['share_permission'] ? '<span class="badge">Yes</span>' : '<span class="badge muted">Private</span>'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">No prayer requests yet.</div>
    <?php endif; ?>
</section>
<?php
admin_page_end();
