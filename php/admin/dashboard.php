<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';

admin_require_login();

$pdo = null;
$databaseError = null;

try {
    $pdo = db();
} catch (Throwable $e) {
    $databaseError = $e->getMessage();
}

if (!defined('LIBERTY_CHURCH_DASHBOARD_VERSION')) {
    define('LIBERTY_CHURCH_DASHBOARD_VERSION', '2025-10-07T05:30:00Z');
    if (function_exists('error_log')) {
        error_log('[Dashboard] Loaded ' . LIBERTY_CHURCH_DASHBOARD_VERSION);
    }
}

if (!$pdo) {
    $drivers = PDO::getAvailableDrivers();
    admin_page_start('Dashboard', 'dashboard');
    ?>
    <section class="card">
        <h3>Local Database Setup Needed</h3>
        <p class="muted">
            The admin login gate is disabled for local conversion work, but the dashboard still needs a MySQL database connection before content tools can load.
        </p>
        <div class="flash flash-error"><?= htmlspecialchars($databaseError ?: 'Database connection is unavailable.') ?></div>
        <table class="table">
            <tbody>
                <tr>
                    <th>Configured host</th>
                    <td><?= htmlspecialchars(DB_HOST) ?></td>
                </tr>
                <tr>
                    <th>Configured database</th>
                    <td><?= htmlspecialchars(DB_NAME) ?></td>
                </tr>
                <tr>
                    <th>Configured user</th>
                    <td><?= htmlspecialchars(DB_USER) ?></td>
                </tr>
                <tr>
                    <th>Available PDO drivers</th>
                    <td><?= htmlspecialchars($drivers ? implode(', ', $drivers) : 'none') ?></td>
                </tr>
            </tbody>
        </table>
        <p>
            For cPanel, run <code>database/glc_cpanel_full_schema.sql</code> in the target MySQL/MariaDB database, then update <code>php/config.php</code> with that database name and user.
        </p>
        <p>
            For this Windows local server, PHP also needs the <code>pdo_mysql</code> extension enabled before it can connect to MySQL.
        </p>
    </section>
    <?php
    admin_page_end();
    return;
}

/**
 * Determine whether a table exposes the given column using SHOW COLUMNS (identifiers can't be parameterized)
 */
function dashboard_table_has_column(PDO $pdo, string $table, string $column): bool
{
    // Sanitize table name to prevent injection
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    if ($table === '') {
        if (function_exists('error_log')) {
            error_log('[Dashboard] Invalid table name detected when checking columns.');
        }
        return false;
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    if ($stmt === false) {
        if (function_exists('error_log')) {
            error_log('[Dashboard] Unable to inspect columns for ' . $table);
        }
        return false;
    }

    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return in_array($column, $columns, true);
}

/**
 * Count records with optional equality filters, skipping any filters tied to missing columns.
 *
 * @param array<string, scalar> $filters
 */
function dashboard_count(PDO $pdo, string $table, array $filters = []): int
{
    $whereParts = [];
    $params = [];

    foreach ($filters as $column => $value) {
        if (!dashboard_table_has_column($pdo, $table, $column)) {
            if (function_exists('error_log')) {
                error_log("[Dashboard] Column {$table}.{$column} missing; skipping filter");
            }
            continue;
        }

        $paramName = ':p' . count($params);
        $whereParts[] = "`{$column}` = {$paramName}";
        $params[$paramName] = $value;
    }

    $sql = "SELECT COUNT(*) FROM `{$table}`";
    if ($whereParts) {
        $sql .= ' WHERE ' . implode(' AND ', $whereParts);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

$metrics = [
    'active_announcements' => dashboard_count($pdo, 'announcements', ['is_published' => 1]),
    'prayer_open'          => dashboard_count($pdo, 'prayer_requests', ['is_prayed' => 0]),
    'visit_new'            => dashboard_count($pdo, 'visit_submissions', ['is_read' => 0]),
    'youth_albums'         => dashboard_count($pdo, 'youth_albums', ['is_published' => 1]),
    'youth_media'          => dashboard_count($pdo, 'youth_media'),
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
