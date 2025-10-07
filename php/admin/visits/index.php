<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

if (!admin_can_view_visits()) {
    http_response_code(403);
    exit('You do not have permission to view visit submissions.');
}

$pdo = db();

$status = strtolower(trim((string)($_GET['status'] ?? 'unread')));
$allowedStatuses = ['unread', 'recent', 'all'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'unread';
}

$canManage = admin_can_manage_visits();

$totalUnread = (int)$pdo->query('SELECT COUNT(*) FROM visit_submissions WHERE is_read = 0')->fetchColumn();
$totalAll = (int)$pdo->query('SELECT COUNT(*) FROM visit_submissions')->fetchColumn();

switch ($status) {
    case 'recent':
        $heading = 'Recent submissions';
        $query = 'SELECT * FROM visit_submissions ORDER BY created_at DESC LIMIT 50';
        break;
    case 'all':
        $heading = 'All submissions';
        $query = 'SELECT * FROM visit_submissions ORDER BY created_at DESC';
        break;
    default:
        $heading = 'Unread submissions';
        $query = 'SELECT * FROM visit_submissions WHERE is_read = 0 ORDER BY created_at DESC';
        break;
}

$stmt = $pdo->query($query);
$submissions = $stmt->fetchAll();
$redirectTarget = '/php/admin/visits/index.php' . ($status !== 'unread' ? '?status=' . $status : '');

admin_page_start('Visit Submissions', 'visits');
?>
<div class="card" style="gap:20px;">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
        <div>
            <h3 style="margin:0;"><?= htmlspecialchars($heading) ?></h3>
            <p style="margin:6px 0 0;color:var(--admin-muted);">
                <?= $status === 'unread' ? 'Follow up with every new family planning a visit.' : 'Browse the recent history of visit forms submitted online.' ?>
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a class="btn btn-secondary <?= $status === 'unread' ? 'active-filter' : '' ?>" href="?status=unread">Unread (<?= number_format($totalUnread) ?>)</a>
            <a class="btn btn-secondary <?= $status === 'recent' ? 'active-filter' : '' ?>" href="?status=recent">Last 50</a>
            <a class="btn btn-secondary <?= $status === 'all' ? 'active-filter' : '' ?>" href="?status=all">All (<?= number_format($totalAll) ?>)</a>
        </div>
    </div>

    <?php if ($submissions): ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Guest</th>
                        <th>Visit plans</th>
                        <th>Notes</th>
                        <th>Received</th>
                        <?php if ($canManage): ?>
                            <th></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($submission['name']) ?></strong><br>
                            <a href="mailto:<?= htmlspecialchars($submission['email']) ?>"><?= htmlspecialchars($submission['email']) ?></a>
                            <?php if (!empty($submission['phone'])): ?>
                                <br><small><?= htmlspecialchars($submission['phone']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:220px;">
                            <?php if (!empty($submission['visit_date'])): ?>
                                <div><span class="badge">Visit: <?= htmlspecialchars((string)$submission['visit_date']) ?></span></div>
                            <?php endif; ?>
                            <?php if (!empty($submission['party_size'])): ?>
                                <div><small>Party size: <?= htmlspecialchars((string)$submission['party_size']) ?></small></div>
                            <?php endif; ?>
                            <div><small>Status: <?= (int)$submission['is_read'] ? '<span class="badge muted">Acknowledged</span>' : '<span class="badge">New</span>' ?></small></div>
                        </td>
                        <td style="max-width:320px;">
                            <?php if (!empty($submission['notes'])): ?>
                                <?= nl2br(htmlspecialchars((string)$submission['notes'])) ?>
                            <?php else: ?>
                                <span class="badge muted">No additional notes</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(format_datetime((string)$submission['created_at'])) ?></td>
                        <?php if ($canManage): ?>
                            <td class="actions">
                                <form method="post" action="mark.php">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$submission['id'] ?>">
                                    <input type="hidden" name="value" value="<?= (int)$submission['is_read'] ? '0' : '1' ?>">
                                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget) ?>">
                                    <button class="btn btn-secondary" type="submit">
                                        <?= (int)$submission['is_read'] ? 'Mark unread' : 'Mark as read' ?>
                                    </button>
                                </form>
                                <form method="post" action="delete.php" onsubmit="return confirm('Delete this visit submission from <?= htmlspecialchars($submission['name']) ?>? This cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$submission['id'] ?>">
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
        <div class="empty-state">No visit submissions in this view.</div>
    <?php endif; ?>
</div>
<?php
admin_page_end();
