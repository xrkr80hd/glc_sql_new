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

$redirect = (string)($_POST['redirect'] ?? '/php/admin/visits/index.php');
$id = (int)($_POST['id'] ?? 0);
$value = isset($_POST['value']) && (int)$_POST['value'] === 1 ? 1 : 0;

if ($id <= 0) {
    admin_flash('error', 'Invalid submission.');
    admin_redirect($redirect);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, name, is_read FROM visit_submissions WHERE id = :id');
$stmt->execute([':id' => $id]);
$submission = $stmt->fetch();

if (!$submission) {
    admin_flash('error', 'Visit submission not found.');
    admin_redirect($redirect);
}

if ((int)$submission['is_read'] === $value) {
    admin_redirect($redirect);
}

$pdo->prepare('UPDATE visit_submissions SET is_read = :value WHERE id = :id')
    ->execute([
        ':value' => $value,
        ':id' => $id,
    ]);

$label = $value === 1 ? 'marked as read' : 'reopened';
admin_flash('success', sprintf('Visit submission from %s %s.', $submission['name'], $label));
admin_redirect($redirect);
