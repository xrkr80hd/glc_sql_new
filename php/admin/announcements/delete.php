<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

verify_csrf($_POST['csrf_token'] ?? '');

$id = (int)($_POST['id'] ?? 0);

if ($id === 0) {
    header('Location: index.php?error=' . urlencode('Invalid announcement ID'));
    exit;
}

$pdo = db();

// Delete announcement
$stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
$stmt->execute([$id]);

header('Location: index.php?message=' . urlencode('Announcement deleted successfully'));
exit;
