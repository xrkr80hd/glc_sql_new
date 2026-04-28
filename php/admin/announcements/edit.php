<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$error = trim((string) ($_GET['error'] ?? ''));

if ($id === 0) {
    header('Location: index.php?error=' . urlencode('Invalid announcement ID.'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->execute([$id]);
$announcement = $stmt->fetch();

if (!$announcement) {
    header('Location: index.php?error=' . urlencode('Announcement not found.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    $category = trim((string) ($_POST['category'] ?? 'main'));
    $title = trim((string) ($_POST['title'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    $startDate = trim((string) ($_POST['start_date'] ?? ''));
    $endDate = trim((string) ($_POST['end_date'] ?? ''));

    if ($title === '' || $body === '') {
        header('Location: edit.php?id=' . $id . '&error=' . urlencode('Title and body are required.'));
        exit;
    }

    $sql = "UPDATE announcements
            SET category = :category,
                title = :title,
                body = :body,
                start_date = :start_date,
                end_date = :end_date,
                sort_order = :sort_order,
                is_published = :is_published
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':category' => $category,
        ':title' => $title,
        ':body' => $body,
        ':start_date' => $startDate ?: null,
        ':end_date' => $endDate ?: null,
        ':sort_order' => (int) $announcement['sort_order'],
        ':is_published' => 1,
        ':id' => $id,
    ]);

    header('Location: index.php?message=' . urlencode('Announcement saved.'));
    exit;
}

admin_page_start('Edit Announcement', 'announcements');
?>

<?php if ($error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<section class="card announcement-card-shell">
    <div class="announcement-page-head">
        <h3>Edit Announcement</h3>
        <p class="announcement-page-copy">Update the announcement and save your changes. Order still lives on the list page so this form stays simple.</p>
    </div>

    <form method="POST" class="announcement-form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <section class="announcement-section">
            <div class="announcement-section-head">
                <h4>Announcement Details</h4>
                <p>Adjust where it appears and update the wording without dealing with sort order or publish switches.</p>
            </div>

            <div class="announcement-grid announcement-grid-single">
                <div class="form-group">
                    <label for="category">Show On</label>
                    <select id="category" name="category" required>
                        <option value="main" <?= $announcement['category'] === 'main' ? 'selected' : '' ?>>Main (Homepage)</option>
                        <option value="youth" <?= $announcement['category'] === 'youth' ? 'selected' : '' ?>>Youth Page</option>
                        <option value="event" <?= $announcement['category'] === 'event' ? 'selected' : '' ?>>Event</option>
                        <option value="global" <?= $announcement['category'] === 'global' ? 'selected' : '' ?>>Global (All Pages)</option>
                    </select>
                </div>
            </div>

            <div class="announcement-grid announcement-grid-single">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required maxlength="255" value="<?= htmlspecialchars($announcement['title']) ?>">
                </div>
            </div>

            <div class="announcement-grid announcement-grid-single">
                <div class="form-group">
                    <label for="body">Announcement</label>
                    <textarea id="body" name="body" required rows="8"><?= htmlspecialchars($announcement['body']) ?></textarea>
                </div>
            </div>
        </section>

        <section class="announcement-section">
            <div class="announcement-section-head">
                <h4>Scheduling</h4>
                <p>Use dates if you want the announcement to show only during a certain window.</p>
            </div>

            <div class="announcement-grid">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars((string) ($announcement['start_date'] ?? '')) ?>">
                </div>

                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars((string) ($announcement['end_date'] ?? '')) ?>">
                </div>
            </div>
        </section>

        <div class="form-actions announcement-actions">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>

<?php
admin_page_end();
?>
