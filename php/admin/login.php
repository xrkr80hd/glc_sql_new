<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (admin_current_user()) {
    admin_redirect('/php/admin/dashboard.php');
}

$error = '';
$usernameInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $usernameInput = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($usernameInput === '' || $password === '') {
        $error = 'Both username and password are required.';
    } else {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $usernameInput]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Account not found.';
        } elseif (!(int)$user['is_active']) {
            $error = 'This account is disabled. Contact a pastor to restore access.';
        } elseif (!password_verify($password, (string)$user['password_hash'])) {
            $error = 'Incorrect password.';
        } else {
            $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id')
                ->execute([':id' => (int)$user['id']]);

            admin_login($user);
            admin_flash('success', 'Welcome back, ' . $user['username'] . '!');
            admin_redirect('/php/admin/dashboard.php');
        }
    }
}

$csrf = csrf_token();
$cssPath = __DIR__ . '/../../assets/admin.css';
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sign in · Liberty Church Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/admin.css?v=<?= rawurlencode($cssVersion) ?>">
</head>
<body style="background:#0f2417;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:32px;">
    <div class="card" style="max-width:420px;width:100%;background:#ffffff;border-radius:18px;padding:40px;">
        <h2 style="margin:0 0 18px;font-size:2rem;">Liberty Church Admin</h2>
        <p style="margin:0 0 26px;color:#5a6f61;">Sign in with your administrator credentials.</p>

        <?php if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <label>
                Username
                <input type="text" name="username" autocomplete="username" required value="<?= htmlspecialchars($usernameInput) ?>">
            </label>
            <label>
                Password
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:12px;">Sign In</button>
        </form>
    </div>
</body>
</html>
