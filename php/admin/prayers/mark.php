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

$redirect = (string)($_POST['redirect'] ?? '/php/admin/prayers/index.php');
$id = (int)($_POST['id'] ?? 0);
$value = isset($_POST['value']) && (int)$_POST['value'] === 1 ? 1 : 0;

if ($id <= 0) {
    admin_flash('error', 'Invalid prayer request.');
    admin_redirect($redirect);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, name, is_prayed FROM prayer_requests WHERE id = :id');
$stmt->execute([':id' => $id]);
$request = $stmt->fetch();

if (!$request) {
    admin_flash('error', 'Prayer request not found.');
    admin_redirect($redirect);
}

if ((int)$request['is_prayed'] === $value) {
    admin_redirect($redirect);
}

$pdo->prepare('UPDATE prayer_requests SET is_prayed = :value WHERE id = :id')
    ->execute([
        ':value' => $value,
        ':id' => $id,
    ]);

$name = $request['name'] ?: 'Anonymous request';
$label = $value === 1 ? 'marked as prayed for' : 'returned to active';
admin_flash('success', sprintf('%s has been %s.', $name, $label));
admin_redirect($redirect);
