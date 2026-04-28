<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';
require_once __DIR__ . '/ordering.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

verify_csrf($_POST['csrf_token'] ?? '');

$pdo = db();
$id = (int) ($_POST['id'] ?? 0);
$direction = trim((string) ($_POST['direction'] ?? ''));

if ($id === 0 || !in_array($direction, ['up', 'down'], true)) {
    header('Location: index.php?error=' . urlencode('Invalid reorder request.'));
    exit;
}

$currentStmt = $pdo->prepare("SELECT id, is_published FROM announcements WHERE id = ?");
$currentStmt->execute([$id]);
$current = $currentStmt->fetch();

if (!$current) {
    header('Location: index.php?error=' . urlencode('Announcement not found.'));
    exit;
}

$idsStmt = $pdo->prepare("
    SELECT id
    FROM announcements
    WHERE is_published = ?
    ORDER BY sort_order ASC, created_at DESC, id DESC
");
$idsStmt->execute([(int) $current['is_published']]);
$orderedIds = array_map('intval', array_column($idsStmt->fetchAll(), 'id'));

$reorderedIds = announcement_reorder_ids($orderedIds, $id, $direction);

if ($reorderedIds === $orderedIds) {
    header('Location: index.php');
    exit;
}

$pdo->beginTransaction();

try {
    $updateStmt = $pdo->prepare("UPDATE announcements SET sort_order = :sort_order WHERE id = :id");

    foreach ($reorderedIds as $position => $announcementId) {
        $updateStmt->execute([
            ':sort_order' => $position,
            ':id' => $announcementId,
        ]);
    }

    $pdo->commit();
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: index.php?error=' . urlencode('Could not reorder the announcement right now.'));
    exit;
}

header('Location: index.php?message=' . urlencode('Announcement order updated.'));
exit;
