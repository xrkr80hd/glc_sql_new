<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: index.php?error=' . urlencode('Invalid announcement ID'));
    exit;
}

// Fetch announcement
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->execute([$id]);
$announcement = $stmt->fetch();

if (!$announcement) {
    header('Location: index.php?error=' . urlencode('Announcement not found'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    $category = trim($_POST['category'] ?? 'main');
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if ($title === '' || $body === '') {
        header('Location: edit.php?id=' . $id . '&error=' . urlencode('Title and body are required'));
        exit;
    }

    $sql = "UPDATE announcements 
            SET category = :category, title = :title, body = :body, 
                start_date = :start_date, end_date = :end_date, 
                sort_order = :sort_order, is_published = :is_published
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':category' => $category,
        ':title' => $title,
        ':body' => $body,
        ':start_date' => $startDate ?: null,
        ':end_date' => $endDate ?: null,
        ':sort_order' => $sortOrder,
        ':is_published' => $isPublished,
        ':id' => $id,
    ]);

    header('Location: index.php?message=' . urlencode('Announcement updated successfully'));
    exit;
}

admin_page_start('Edit Announcement', 'announcements');
?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<section class="card">
    <h2>Edit Announcement</h2>

    <form method="POST" class="form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label for="category">Category *</label>
            <select id="category" name="category" required>
                <option value="main" <?= $announcement['category'] === 'main' ? 'selected' : '' ?>>Main (Homepage)</option>
                <option value="youth" <?= $announcement['category'] === 'youth' ? 'selected' : '' ?>>Youth Page</option>
                <option value="event" <?= $announcement['category'] === 'event' ? 'selected' : '' ?>>Event</option>
                <option value="global" <?= $announcement['category'] === 'global' ? 'selected' : '' ?>>Global (All Pages)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required maxlength="255" value="<?= htmlspecialchars($announcement['title']) ?>">
        </div>

        <div class="form-group">
            <label for="body">Body *</label>
            <textarea id="body" name="body" required rows="6"><?= htmlspecialchars($announcement['body']) ?></textarea>
        </div>

        <div class="row">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($announcement['start_date'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($announcement['end_date'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= (int)$announcement['sort_order'] ?>" min="0">
        </div>

        <div class="form-group">
            <label class="checkbox">
                <input type="checkbox" name="is_published" value="1" <?= $announcement['is_published'] ? 'checked' : '' ?>>
                <span>Published</span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>

<?php
admin_page_end();
?>
