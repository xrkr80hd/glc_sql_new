<?php
// Static Scripture of the Week - No database dependency
// This provides scripture content without requiring database setup

// CSS now handled by main site styles - using beautiful modern styling
static $glc_scripture_css_once = false;
if (!$glc_scripture_css_once) {
    $glc_scripture_css_once = true;
    // Scripture styling provided by main site CSS
}

// Static scripture (managed content)
$current_scripture = [
    'verse_ref' => 'Romans 8:28',
    'text' => 'And we know that in all things God works for the good of those who love him, who have been called according to his purpose.'
];

if ($current_scripture['verse_ref'] && $current_scripture['text']) {
    echo '<div class="ann-item scripture-highlight">';
    echo '<h3>📖 Scripture of the Week: ', htmlspecialchars($current_scripture['verse_ref']), '</h3>';
    echo '<blockquote style="font-style: italic; font-size: 1.1rem; line-height: 1.6; margin: 16px 0; padding: 20px; background: linear-gradient(135deg, rgba(79, 70, 229, 0.05), rgba(124, 58, 237, 0.05)); border-left: 4px solid var(--brand); border-radius: 8px; color: #374151;">';
    echo '"', nl2br(htmlspecialchars($current_scripture['text'])), '"';
    echo '</blockquote>';
    echo '</div>';
} else {
    echo '<div class="ann-item" style="opacity: 0.7; font-style: italic;">Scripture coming soon.</div>';
}
