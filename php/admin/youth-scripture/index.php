<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

$pdo = db();

// Fetch current active scripture
$stmt = $pdo->query("SELECT * FROM youth_scripture WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
$current = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    $scriptureText = trim($_POST['scripture_text'] ?? '');
    $scriptureRef = trim($_POST['scripture_reference'] ?? '');
    $devotional = trim($_POST['devotional'] ?? '');

    if ($scriptureText === '' || $scriptureRef === '' || $devotional === '') {
        header('Location: index.php?error=' . urlencode('All fields are required'));
        exit;
    }

    // Deactivate old scriptures
    $pdo->exec("UPDATE youth_scripture SET is_active = 0");

    // Insert new scripture
    $sql = "INSERT INTO youth_scripture (scripture_text, scripture_reference, devotional, is_active)
            VALUES (:text, :ref, :devotional, 1)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':text' => $scriptureText,
        ':ref' => $scriptureRef,
        ':devotional' => $devotional,
    ]);

    header('Location: index.php?message=' . urlencode('Scripture & devotional updated successfully'));
    exit;
}

admin_page_start('Youth Scripture & Devotional', 'youth-scripture');
?>

<?php if (isset($_GET['message'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['message']) ?></div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<section class="card">
    <h2>Weekly Scripture & Devotional</h2>
    <p class="muted">Update the scripture verse and devotional that students see at the top of the youth page.</p>

    <form method="POST" class="form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label for="scripture_text">Scripture Text *</label>
            <textarea id="scripture_text" name="scripture_text" required rows="4" placeholder="Write out the full verse text..."><?= $current ? htmlspecialchars($current['scripture_text']) : '' ?></textarea>
            <small class="form-help">Copy the verse exactly as it appears in the Bible</small>
        </div>

        <div class="form-group">
            <label for="scripture_reference">Scripture Reference *</label>
            <input type="text" id="scripture_reference" name="scripture_reference" required maxlength="255" 
                   placeholder="e.g., Psalm 119:105" value="<?= $current ? htmlspecialchars($current['scripture_reference']) : '' ?>">
            <small class="form-help">Include book, chapter, and verse</small>
        </div>

        <div class="form-group">
            <label for="devotional">Weekly Devotional *</label>
            <textarea id="devotional" name="devotional" required rows="8" 
                      placeholder="Share a 2-3 paragraph devotional for students..."><?= $current ? htmlspecialchars($current['devotional']) : '' ?></textarea>
            <small class="form-help">Keep it conversational and relevant to students' lives</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Save Scripture & Devotional</button>
        </div>
    </form>
</section>

<?php if ($current): ?>
    <section class="card">
        <h3>Current Live Content</h3>
        <div class="preview">
            <div class="preview-section">
                <strong>Scripture:</strong>
                <blockquote><?= nl2br(htmlspecialchars($current['scripture_text'])) ?></blockquote>
                <cite>— <?= htmlspecialchars($current['scripture_reference']) ?></cite>
            </div>
            <div class="preview-section">
                <strong>Devotional:</strong>
                <?= nl2br(htmlspecialchars($current['devotional'])) ?>
            </div>
            <small class="muted">Last updated: <?= htmlspecialchars(format_datetime($current['updated_at'])) ?></small>
        </div>
    </section>
<?php endif; ?>

<?php
admin_page_end();
?>
