<?php
// Reset admin password
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Reset Admin Password</h1>";

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=golibert2_liberty_church;charset=utf8mb4',
        'golibert2_liberty_church_user',
        '@LibertyChurch1065!'
    );
    
    // Hash the password
    $password = '@LibertyChurch1065!';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    // Update admin password
    $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);
    
    echo "<p style='color: green;'>✅ Admin password reset successfully!</p>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> @LibertyChurch1065!</p>";
    echo "<p><a href='/php/admin/login.php'>Go to login page</a></p>";
    echo "<hr><p style='color: red;'><strong>DELETE THIS FILE after logging in!</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
