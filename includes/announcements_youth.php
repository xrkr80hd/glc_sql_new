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
            echo '<div class="announcement-gallery youth">';
            foreach ($imgs as $im) {
                echo '<img loading="lazy" class="announcement-photo" src="', htmlspecialchars($im['file_path']), '" alt="', htmlspecialchars($im['alt'] ?? ''), '">';
            }
            echo '</div>';
        }
        echo '<p>', nl2br(htmlspecialchars($row['body'])), '</p></div>';
    }
    if ($count === 0) {
        echo '<div class="ann-item youth-cosmic announcement-empty">No youth announcements at this time.</div>';
    }
} catch (Throwable $e) {
    echo '<div class="ann-item youth-cosmic announcement-empty">Youth announcements are temporarily unavailable.</div>';
}
