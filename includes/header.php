<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_public'])) { $_SESSION['csrf_public'] = bin2hex(random_bytes(16)); }
$csrf_public = $_SESSION['csrf_public'];
?><header class="site-header">
  <?php if (file_exists(__DIR__.'/../logo.png')): ?>
    <img src="/logo.png" alt="Go Liberty Church" class="logo">
  <?php endif; ?>
  <h1>Go Liberty Church</h1>
  <nav>
  <a href="https://www.golibertychurch.com/index.php">Home</a>
  <a href="https://www.golibertychurch.com/youth.php">Youth</a>
  <a href="https://www.golibertychurch.com/live.php">Live</a>
  <a href="https://www.golibertychurch.com/planvisit.php">Plan a Visit</a>
  <a href="https://www.golibertychurch.com/prayer.php">Prayer</a>
  <a href="https://www.golibertychurch.com/contact.php">Contact</a>
  </nav>
</header>
