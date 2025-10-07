<?php
require_once __DIR__ . '/../php/config.php';

// CSS now handled by main site styles
static $glc_ann_css_once = false;
if (!$glc_ann_css_once) {
    $glc_ann_css_once = true;
    // CSS styling provided by main site
}

try {
    $pdo = db();
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT id, title, body FROM announcements WHERE category='youth' AND is_published=1 AND (start_date IS NULL OR start_date<=?) AND (end_date IS NULL OR end_date>=?) ORDER BY sort_order ASC, start_date DESC, updated_at DESC");
    $stmt->execute([$today, $today]);
    $count = 0;
    while ($row = $stmt->fetch()) {
        $count++;
        echo '<div class="ann-item youth-cosmic">';
        echo '<h3>', htmlspecialchars($row['title']), '</h3>';
        $photos = $pdo->prepare('SELECT file_path, alt FROM announcement_photos WHERE announcement_id=? ORDER BY sort_order ASC, id ASC');
        $photos->execute([$row['id']]);
        $imgs = $photos->fetchAll();
        if ($imgs) {
            echo '<div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin:12px 0 16px;">';
            foreach ($imgs as $im) {
                echo '<img loading="lazy" src="', htmlspecialchars($im['file_path']), '" alt="', htmlspecialchars($im['alt'] ?? ''), '" style="width:100%;height:160px;object-fit:cover;border-radius:12px;box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">';
            }
            echo '</div>';
        }
        echo '<p>', nl2br(htmlspecialchars($row['body'])), '</p></div>';
    }
    if ($count === 0) {
        echo '<div class="ann-item youth-cosmic" style="opacity: 0.7; font-style: italic;">No youth announcements at this time.</div>';
    }
} catch (Throwable $e) {
    echo '<div class="ann-item youth-cosmic" style="opacity: 0.7; font-style: italic;">Youth announcements are temporarily unavailable.</div>';
}
