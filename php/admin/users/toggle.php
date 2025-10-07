<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

admin_require_login();
admin_require_role('pastor', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

verify_csrf($_POST['csrf_token'] ?? null);

$targetId = (int)($_POST['id'] ?? 0);
$currentUser = admin_current_user();

if ($targetId <= 0) {
    admin_flash('error', 'Invalid user ID.');
    admin_redirect('/php/admin/users/index.php');
}

if ($currentUser && (int)$currentUser['id'] === $targetId) {
    admin_flash('error', 'You cannot disable your own account.');
    admin_redirect('/php/admin/users/index.php');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, username, role, is_active FROM admin_users WHERE id = :id');
$stmt->execute([':id' => $targetId]);
$target = $stmt->fetch();

if (!$target) {
    admin_flash('error', 'User not found.');
    admin_redirect('/php/admin/users/index.php');
}

$newState = (int)$target['is_active'] ? 0 : 1;

if ($newState === 0 && admin_is_last_active_role((string)$target['role'], $targetId)) {
    admin_flash('error', 'At least one ' . (admin_role_labels()[$target['role']] ?? $target['role']) . ' must remain active.');
    admin_redirect('/php/admin/users/index.php');
}

$pdo->prepare('UPDATE admin_users SET is_active = :state WHERE id = :id')
    ->execute([':state' => $newState, ':id' => $targetId]);

admin_flash($newState ? 'success' : 'info', ($newState ? 'Activated ' : 'Disabled ') . $target['username']);
admin_redirect('/php/admin/users/index.php');
