<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

$pdo = db();

// Handle status messages
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all announcements
$stmt = $pdo->query("
    SELECT id, category, title, body, start_date, end_date, is_published, sort_order, created_at, updated_at
    FROM announcements
    ORDER BY is_published DESC, sort_order ASC, created_at DESC
");
$announcements = $stmt->fetchAll();

admin_page_start('Announcements', 'announcements');
?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<section class="card">
    <div class="card-header">
        <h2>Manage Announcements</h2>
        <a href="new.php" class="btn btn-primary">+ New Announcement</a>
    </div>
    
    <p class="muted">Announcements can be published to the main homepage or youth page. Set date ranges to automatically show/hide them.</p>

    <?php if (empty($announcements)): ?>
        <div class="empty-state">
            <p>No announcements yet. Create your first one to get started.</p>
            <a href="new.php" class="btn btn-primary">+ Create Announcement</a>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Dates</th>
                    <th>Status</th>
                    <th>Sort</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($announcements as $ann): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($ann['title']) ?></strong>
                            <br>
                            <small class="muted"><?= htmlspecialchars(substr($ann['body'], 0, 80)) ?><?= strlen($ann['body']) > 80 ? '...' : '' ?></small>
                        </td>
                        <td>
                            <span class="badge <?= $ann['category'] === 'youth' ? 'badge-info' : '' ?>">
                                <?= htmlspecialchars(ucfirst($ann['category'])) ?>
                            </span>
                        </td>
                        <td>
                            <small>
                                <?php if ($ann['start_date']): ?>
                                    From: <?= htmlspecialchars($ann['start_date']) ?><br>
                                <?php endif; ?>
                                <?php if ($ann['end_date']): ?>
                                    Until: <?= htmlspecialchars($ann['end_date']) ?>
                                <?php endif; ?>
                                <?php if (!$ann['start_date'] && !$ann['end_date']): ?>
                                    Always visible
                                <?php endif; ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($ann['is_published']): ?>
                                <span class="badge badge-success">Published</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$ann['sort_order'] ?></td>
                        <td class="actions">
                            <a href="edit.php?id=<?= (int)$ann['id'] ?>" class="btn btn-sm">Edit</a>
                            <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Delete this announcement?');">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id" value="<?= (int)$ann['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php
admin_page_end();
?>
