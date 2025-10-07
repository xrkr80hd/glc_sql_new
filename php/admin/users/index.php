<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();
admin_require_role('pastor', 'admin');

$pdo = db();
$users = $pdo->query('SELECT id, username, role, is_active, created_at, last_login FROM admin_users ORDER BY username ASC')->fetchAll();
$roleLabels = admin_role_labels();

admin_page_start('Manage Users', 'users');
?>
<div class="card" style="gap:24px;">
    <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;">
        <div>
            <h3 style="margin:0;">Administrator Accounts</h3>
            <p style="margin:6px 0 0;color:var(--admin-muted);">Create, disable, or update login access for the team.</p>
        </div>
        <a class="btn btn-primary" href="/php/admin/users/new.php">Add user</a>
    </div>

    <?php if ($users): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Last login</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($roleLabels[$user['role']] ?? ucfirst($user['role'])) ?></td>
                    <td>
                        <?= (int)$user['is_active'] ? '<span class="badge">Active</span>' : '<span class="badge danger">Disabled</span>'; ?>
                    </td>
                    <td><?= htmlspecialchars(format_datetime((string)$user['created_at'])) ?></td>
                    <td><?= $user['last_login'] ? htmlspecialchars(format_datetime((string)$user['last_login'])) : '<span class="badge muted">Never</span>' ?></td>
                    <td class="actions">
                        <a class="btn btn-secondary" href="/php/admin/users/edit.php?id=<?= (int)$user['id'] ?>">Edit</a>
                        <?php if ((int)$user['id'] !== (admin_current_user()['id'] ?? 0)): ?>
                            <form method="post" action="toggle.php" onsubmit="return confirm('Toggle active status for <?= htmlspecialchars($user['username']) ?>?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                <button class="btn <?= (int)$user['is_active'] ? 'btn-danger' : 'btn-primary' ?>" type="submit">
                                    <?= (int)$user['is_active'] ? 'Disable' : 'Activate' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">No admin accounts created yet.</div>
    <?php endif; ?>
</div>
<?php
admin_page_end();
