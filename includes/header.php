<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_public'])) { $_SESSION['csrf_public'] = bin2hex(random_bytes(16)); }
$csrf_public = $_SESSION['csrf_public'];
?><header class="header site-header">
  <div class="container nav">
    <a class="brand" href="https://www.golibertychurch.com/index.html">
      <img src="/assets/logo.png" alt="Liberty Church logo">
      <span class="name">Liberty Church</span>
    </a>
    <button class="nav-toggle" aria-expanded="false" aria-controls="mainNav">
      <span class="hamburger" aria-hidden="true"></span>
      <span class="sr-only">Open navigation</span>
    </button>
    <nav id="mainNav">
      <ul>
        <li><a href="https://www.golibertychurch.com/index.html">Home</a></li>
        <li><a href="https://www.golibertychurch.com/live.html">Live</a></li>
        <li><a href="https://www.golibertychurch.com/youth.html">Youth</a></li>
        <li><a href="https://www.golibertychurch.com/sermons.html">Sermons</a></li>
        <li><a href="https://www.golibertychurch.com/prayer.html">Prayer</a></li>
        <li><a href="https://www.golibertychurch.com/beliefs.html">Beliefs</a></li>
        <li><a href="https://www.golibertychurch.com/give.html">Give</a></li>
        <li><a class="nav-cta" href="https://www.golibertychurch.com/visit.html">Plan Visit</a></li>
      </ul>
    </nav>
  </div>
</header>
