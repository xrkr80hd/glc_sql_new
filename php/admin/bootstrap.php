<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function admin_current_user(): ?array
{
    if (defined('ADMIN_LOGIN_DISABLED') && ADMIN_LOGIN_DISABLED) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] ??= [
            'id' => 0,
            'username' => 'local-admin',
            'role' => 'pastor',
        ];
        $_SESSION['admin_user_id'] = 0;
    }

    return $_SESSION['admin_user'] ?? null;
}

function admin_login(array $user): void
{
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user'] = [
        'id'       => (int) $user['id'],
        'username' => $user['username'],
        'role'     => $user['role'],
    ];
    $_SESSION['admin_user_id'] = (int) $user['id'];

    session_regenerate_id(true);
}

function admin_logout(): void
{
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_user'], $_SESSION['admin_user_id'], $_SESSION['csrf_token']);
    session_regenerate_id(true);
}

function admin_require_login(): void
{
    if (!admin_current_user()) {
        header('Location: /php/admin/login.php');
        exit;
    }
}

function admin_require_role(string ...$roles): void
{
    $user = admin_current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to access this area.');
    }
}

function admin_role_labels(): array
{
    return [
        'pastor'         => 'Pastor (full access)',
        'admin'          => 'Administrator',
    'music_minister' => 'Music Minister',
    'worship_team'   => 'Worship Team',
        'youth_minister' => 'Youth Minister',
        'media'          => 'Media Team',
        'sound'          => 'Sound Team',
    ];
}

function admin_has_role(string ...$roles): bool
{
    $user = admin_current_user();
    return $user && in_array($user['role'], $roles, true);
}

function admin_can_manage_users(): bool
{
    return admin_has_role('pastor', 'admin');
}

function admin_is_super_admin(): bool
{
    $user = admin_current_user();
    return $user && $user['role'] === 'pastor';
}

function admin_can_manage_visits(): bool
{
    return admin_has_role('pastor', 'admin');
}

function admin_can_view_visits(): bool
{
    return admin_has_role('pastor', 'admin', 'music_minister', 'worship_team', 'media', 'sound', 'youth_minister');
}

function admin_can_manage_prayers(): bool
{
    return admin_has_role('pastor', 'admin');
}

function admin_can_view_prayers(): bool
{
    return admin_has_role('pastor', 'admin', 'music_minister', 'youth_minister');
}

function admin_is_last_active_role(string $role, int $excludeId = 0): bool
{
    $pdo = db();
    $sql = 'SELECT COUNT(*) FROM admin_users WHERE role = :role AND is_active = 1';
    if ($excludeId > 0) {
        $sql .= ' AND id <> :exclude';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':role', $role, PDO::PARAM_STR);
    if ($excludeId > 0) {
        $stmt->bindValue(':exclude', $excludeId, PDO::PARAM_INT);
    }
    $stmt->execute();
    return (int) $stmt->fetchColumn() === 0;
}

function admin_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'][] = ['type' => $type, 'message' => $message];
}

function admin_consume_flashes(): array
{
    $messages = $_SESSION['admin_flash'] ?? [];
    unset($_SESSION['admin_flash']);
    return $messages;
}

function admin_redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}
