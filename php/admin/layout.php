<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function admin_page_start(string $title, string $active = ''): void
{
    $user = admin_current_user();
    $flashes = admin_consume_flashes();
    $roleLabels = admin_role_labels();
    $roleLabel = $user ? ($roleLabels[$user['role']] ?? ucfirst($user['role'])) : '';
    $cssPath = __DIR__ . '/../../assets/admin.css';
    $cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?= htmlspecialchars($title) ?> · Liberty Church Admin</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="/assets/admin.css?v=<?= rawurlencode($cssVersion) ?>">
    </head>
    <body>
        </head>
    <body>
    <div class="admin-shell">
        <div class="admin-mobile-header">
            <button class="mobile-menu-btn" aria-label="Toggle navigation" id="mobile-menu-btn">
                <span style="color: #e7f0eb; font-size: 1.5rem; line-height: 1;">☰</span>
            </button>
            <h2><?= htmlspecialchars($title) ?></h2>
        </div>
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h1>Liberty Admin</h1>
                <button class="sidebar-toggle" aria-label="Toggle navigation" aria-expanded="false">
                    <span class="hamburger"></span>
                </button>
            </div>
            <nav class="sidebar-nav">
                <a href="/php/admin/dashboard.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
                
                <div class="nav-section">Content</div>
                <a href="/php/admin/announcements/index.php" class="<?= $active === 'announcements' ? 'active' : '' ?>">📢 Announcements</a>
                <a href="/php/admin/youth-scripture/index.php" class="<?= $active === 'youth-scripture' ? 'active' : '' ?>">📖 Youth Scripture</a>
                <a href="/php/admin/youth-albums/index.php" class="<?= $active === 'youth-albums' ? 'active' : '' ?>">📸 Youth Albums</a>
                
                <div class="nav-section">Live</div>
                <a href="/php/admin/stream/index.php" class="<?= $active === 'stream' ? 'active' : '' ?>">📡 Live Stream</a>
                
                <div class="nav-section">Communications</div>
                <?php if (admin_can_view_visits()): ?>
                    <a href="/php/admin/visits/index.php" class="<?= $active === 'visits' ? 'active' : '' ?>">👋 Visit Submissions</a>
                <?php endif; ?>
                <?php if (admin_can_view_prayers()): ?>
                    <a href="/php/admin/prayers/index.php" class="<?= $active === 'prayers' ? 'active' : '' ?>">🙏 Prayer Requests</a>
                <?php endif; ?>
                
                <?php if (admin_can_manage_users()): ?>
                    <div class="nav-section">System</div>
                    <a href="/php/admin/users/index.php" class="<?= $active === 'users' ? 'active' : '' ?>">👥 Manage Users</a>
                <?php endif; ?>
                
                <a href="/php/admin/logout.php" class="logout-link">🚪 Sign Out</a>
            </nav>
            <?php if ($user): ?>
                <div class="user-chip">
                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                    <span><?= htmlspecialchars($roleLabel) ?></span>
                </div>
            <?php endif; ?>
        </aside>
        <main class="admin-content">
            <header>
                <h2><?= htmlspecialchars($title) ?></h2>
                <p>Liberty Church · Admin Console</p>
            </header>
            <?php if ($flashes): ?>
                <div class="flash-stack">
                    <?php foreach ($flashes as $flash): ?>
                        <div class="flash flash-<?= htmlspecialchars($flash['type']) ?>">
                            <?= htmlspecialchars($flash['message']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
    <?php
}

function admin_page_end(): void
{
    ?>
        </main>
    </div>
    <script>
    // Mobile sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.querySelector('.sidebar-toggle');
        const mobileBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.querySelector('.admin-sidebar');
        const nav = document.querySelector('.sidebar-nav');
        
        function toggleSidebar() {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', !isExpanded);
            sidebar.classList.toggle('nav-open');
        }
        
        if (toggle && sidebar && nav) {
            // Sidebar toggle button (inside sidebar)
            toggle.addEventListener('click', toggleSidebar);
            
            // Mobile header button (top bar)
            if (mobileBtn) {
                mobileBtn.addEventListener('click', toggleSidebar);
            }
            
            // Close menu when clicking a link on mobile
            nav.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 940) {
                        sidebar.classList.remove('nav-open');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
            });
            
            // Close menu when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 940 && 
                    !sidebar.contains(e.target) && 
                    !mobileBtn.contains(e.target) &&
                    sidebar.classList.contains('nav-open')) {
                    sidebar.classList.remove('nav-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
    </script>
    </body>
    </html>
    <?php
}
