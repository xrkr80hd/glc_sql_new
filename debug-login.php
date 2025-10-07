<?php
// Debug login issues
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Login Debug</h1>";

try {
    require_once __DIR__ . '/php/admin/bootstrap.php';
    echo "<p style='color: green;'>✅ Bootstrap loaded</p>";
    
    $pdo = db();
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    $stmt = $pdo->query("SELECT * FROM admin_users WHERE username = 'admin'");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p style='color: green;'>✅ Admin user found</p>";
        echo "<p>Username: " . htmlspecialchars($user['username']) . "</p>";
        echo "<p>Is Active: " . ($user['is_active'] ? 'Yes' : 'No') . "</p>";
        echo "<p>Has password hash: " . (strlen($user['password_hash']) > 0 ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Admin user NOT found</p>";
    }
    
    echo "<hr><p><a href='/php/admin/login.php'>Try login page now</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
