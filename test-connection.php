<?php
// Quick database connection test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=golibert2_liberty_church;charset=utf8mb4',
        'golibert2_liberty_church_user',
        '@LibertyChurch1065!'
    );
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test if admin_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Admin_users table exists</p>";
        
        // Check if admin user exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
        $count = $stmt->fetchColumn();
        echo "<p style='color: " . ($count > 0 ? 'green' : 'red') . ";'>" . ($count > 0 ? '✅' : '❌') . " Admin user exists: $count</p>";
    } else {
        echo "<p style='color: red;'>❌ Users table NOT found - database needs setup!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p><strong>PHP Version:</strong> " . phpversion() . "</p>";
?>
