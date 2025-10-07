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

if ($id <= 0) {
    admin_flash('error', 'Invalid visit submission.');
    admin_redirect($redirect);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, name FROM visit_submissions WHERE id = :id');
$stmt->execute([':id' => $id]);
$submission = $stmt->fetch();

if (!$submission) {
    admin_flash('error', 'Visit submission not found.');
    admin_redirect($redirect);
}

$pdo->prepare('DELETE FROM visit_submissions WHERE id = :id')->execute([':id' => $id]);

admin_flash('info', sprintf('Deleted the visit submission from %s.', $submission['name']));
admin_redirect($redirect);
