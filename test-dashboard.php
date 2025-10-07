<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Dashboard Debug</h1>";

try {
    require_once __DIR__ . '/php/admin/bootstrap.php';
    echo "<p style='color: green;'>✅ Bootstrap loaded</p>";
    
    admin_require_login();
    echo "<p style='color: green;'>✅ Login check passed</p>";
    
    $pdo = db();
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Test each query individually
    echo "<h2>Testing Dashboard Queries:</h2>";
    
    $count = $pdo->query("SELECT COUNT(*) FROM announcements WHERE is_published = 1")->fetchColumn();
    echo "<p>✅ Announcements: $count</p>";
    
    $count = $pdo->query("SELECT COUNT(*) FROM prayer_requests WHERE is_prayed = 0")->fetchColumn();
    echo "<p>✅ Prayer requests: $count</p>";
    
    $count = $pdo->query("SELECT COUNT(*) FROM visit_submissions WHERE is_read = 0")->fetchColumn();
    echo "<p>✅ Visit submissions: $count</p>";
    
    $count = $pdo->query("SELECT COUNT(*) FROM youth_albums WHERE is_published = 1")->fetchColumn();
    echo "<p>✅ Youth albums: $count</p>";
    
    $count = $pdo->query("SELECT COUNT(*) FROM youth_media")->fetchColumn();
    echo "<p>✅ Youth media: $count</p>";
    
    echo "<hr><p style='color: green;'><strong>All queries work! Dashboard should load.</strong></p>";
    echo "<p><a href='/php/admin/dashboard.php'>Try dashboard now</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
