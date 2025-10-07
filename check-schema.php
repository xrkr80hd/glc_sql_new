<?php
require_once __DIR__ . '/php/config.php';

$pdo = db();

echo "<h1>Database Schema Check</h1>";

// Check announcements table
echo "<h2>Announcements Table Columns:</h2><ul>";
$cols = $pdo->query("SHOW COLUMNS FROM announcements")->fetchAll();
foreach ($cols as $col) {
    echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
}
echo "</ul>";

// Check live_streams table
echo "<h2>Live_Streams Table Columns:</h2><ul>";
$cols = $pdo->query("SHOW COLUMNS FROM live_streams")->fetchAll();
foreach ($cols as $col) {
    echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
}
echo "</ul>";
?>
