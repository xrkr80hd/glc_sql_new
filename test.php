<?php
// Simple test to verify PHP is working
echo "PHP is working! Server time: " . date('Y-m-d H:i:s');
echo "\n\nPHP Version: " . phpversion();
echo "\n\nServer Info: " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

// Test database connection
try {
    require_once 'php/config.php';
    $pdo = db();
    echo "\n\nDatabase: Connected successfully to " . DB_NAME;
} catch (Exception $e) {
    echo "\n\nDatabase Error: " . $e->getMessage();
}
?>SELECT username, password_hash, role
FROM admin_users;