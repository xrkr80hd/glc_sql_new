<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (admin_current_user()) {
    $name = admin_current_user()['username'] ?? 'admin';
    admin_logout();
    admin_flash('info', 'Signed out ' . $name . '.');
}

admin_redirect('/php/admin/login.php');
