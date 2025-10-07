<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

if (!admin_can_view_prayers()) {
    http_response_code(403);
    exit('You do not have permission to view prayer requests.');
}

$pdo = db();

$status = strtolower(trim((string)($_GET['status'] ?? 'open')));
$allowedStatuses = ['open', 'recent', 'all'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'open';
}

$canManage = admin_can_manage_prayers();

$totalOpen = (int)$pdo->query('SELECT COUNT(*) FROM prayer_requests WHERE is_prayed = 0')->fetchColumn();
$totalAll = (int)$pdo->query('SELECT COUNT(*) FROM prayer_requests')->fetchColumn();

switch ($status) {
    case 'recent':
        $heading = 'Recent prayer requests';
        $query = 'SELECT * FROM prayer_requests ORDER BY created_at DESC LIMIT 50';
        break;
    case 'all':
        $heading = 'All prayer requests';
        $query = 'SELECT * FROM prayer_requests ORDER BY created_at DESC';
        break;
    default:
        $heading = 'Open prayer requests';
        $query = 'SELECT * FROM prayer_requests WHERE is_prayed = 0 ORDER BY created_at ASC';
        break;
}

$stmt = $pdo->query($query);
$requests = $stmt->fetchAll();
$redirectTarget = '/php/admin/prayers/index.php' . ($status !== 'open' ? '?status=' . $status : '');

admin_page_start('Prayer Requests', 'prayers');
?>
<div class="card" style="gap:20px;">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
        <div>
            <h3 style="margin:0;"><?= htmlspecialchars($heading) ?></h3>
            <p style="margin:6px 0 0;color:var(--admin-muted);">
                <?= $status === 'open' ? 'Walk through every open request during prayer time.' : 'Review the complete archive of requests that have come in.' ?>
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a class="btn btn-secondary <?= $status === 'open' ? 'active-filter' : '' ?>" href="?status=open">Open (<?= number_format($totalOpen) ?>)</a>
            <a class="btn btn-secondary <?= $status === 'recent' ? 'active-filter' : '' ?>" href="?status=recent">Last 50</a>
            <a class="btn btn-secondary <?= $status === 'all' ? 'active-filter' : '' ?>" href="?status=all">All (<?= number_format($totalAll) ?>)</a>
        </div>
    </div>

    <?php if ($requests): ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Submitted by</th>
                        <th>Request</th>
                        <th>Permissions</th>
                        <th>Received</th>
                        <?php if ($canManage): ?>
                            <th></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($request['name'] ?: 'Anonymous') ?></strong>
                            <?php if (!empty($request['email'])): ?>
                                <br><a href="mailto:<?= htmlspecialchars($request['email']) ?>">Email</a>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:360px;">
                            <?= nl2br(htmlspecialchars((string)$request['request'])) ?>
                        </td>
                        <td>
                            <div><?= (int)$request['share_permission'] ? '<span class="badge">Can share publicly</span>' : '<span class="badge muted">Private only</span>' ?></div>
                            <div><?= (int)$request['is_prayed'] ? '<span class="badge muted">Already prayed for</span>' : '<span class="badge">Needs prayer</span>' ?></div>
                        </td>
                        <td><?= htmlspecialchars(format_datetime((string)$request['created_at'])) ?></td>
                        <?php if ($canManage): ?>
                            <td class="actions">
                                <form method="post" action="mark.php">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                                    <input type="hidden" name="value" value="<?= (int)$request['is_prayed'] ? '0' : '1' ?>">
                                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget) ?>">
                                    <button class="btn btn-secondary" type="submit">
                                        <?= (int)$request['is_prayed'] ? 'Mark as open' : 'Mark prayed' ?>
                                    </button>
                                </form>
                                <form method="post" action="delete.php" onsubmit="return confirm('Remove this prayer request? You cannot undo this action.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget) ?>">
                                    <button class="btn btn-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">No prayer requests in this view.</div>
    <?php endif; ?>
</div>
<?php
admin_page_end();
