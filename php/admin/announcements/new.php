<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

$pdo = db();

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
        header('Location: new.php?error=' . urlencode('Title and body are required'));
        exit;
    }

    $sql = "INSERT INTO announcements (category, title, body, start_date, end_date, sort_order, is_published)
            VALUES (:category, :title, :body, :start_date, :end_date, :sort_order, :is_published)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':category' => $category,
        ':title' => $title,
        ':body' => $body,
        ':start_date' => $startDate ?: null,
        ':end_date' => $endDate ?: null,
        ':sort_order' => $sortOrder,
        ':is_published' => $isPublished,
    ]);

    header('Location: index.php?message=' . urlencode('Announcement created successfully'));
    exit;
}

admin_page_start('New Announcement', 'announcements');
?>

<section class="card">
    <h2>Create New Announcement</h2>
    <p class="muted">Add an announcement to the main homepage or youth page.</p>

    <form method="POST" class="form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label for="category">Category *</label>
            <select id="category" name="category" required>
                <option value="main">Main (Homepage)</option>
                <option value="youth">Youth Page</option>
                <option value="event">Event</option>
                <option value="global">Global (All Pages)</option>
            </select>
            <small class="form-help">Where should this announcement appear?</small>
        </div>

        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required maxlength="255" placeholder="e.g., Special Service This Sunday">
        </div>

        <div class="form-group">
            <label for="body">Body *</label>
            <textarea id="body" name="body" required rows="6" placeholder="Full announcement text..."></textarea>
            <small class="form-help">Use paragraphs to break up longer announcements.</small>
        </div>

        <div class="row">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date">
                <small class="form-help">Optional: announcement won't show before this date</small>
            </div>

            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date">
                <small class="form-help">Optional: announcement will hide after this date</small>
            </div>
        </div>

        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="0" min="0">
            <small class="form-help">Lower numbers appear first (0 = highest priority)</small>
        </div>

        <div class="form-group">
            <label class="checkbox">
                <input type="checkbox" name="is_published" value="1" checked>
                <span>Publish immediately</span>
            </label>
            <small class="form-help">Uncheck to save as draft</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Announcement</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>

<?php
admin_page_end();
?>
