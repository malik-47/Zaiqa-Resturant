<?php
// admin/_sidebar.php
$current = basename($_SERVER['PHP_SELF']);
$pendingOrders  = db()->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn();
$pendingReviews = db()->query("SELECT COUNT(*) FROM reviews WHERE is_approved=0")->fetchColumn();
function navItem($file, $label, $icon, $current, $badge=0) {
    $active = ($current === $file) ? 'active' : '';
    $b = $badge ? "<span class='nav-badge'>$badge</span>" : '';
    echo "<a href='$file' class='nav-item $active'><span class='nav-icon'>$icon</span>$label$b</a>";
}
?>
<nav class="admin-sidebar">
  <div class="sidebar-logo">Zaiqa<span>.</span></div>
  <div class="sidebar-section">Main</div>
  <?php navItem('index.php',        'Dashboard',    '📊', $current) ?>
  <?php navItem('orders.php',       'Orders',       '📦', $current, $pendingOrders) ?>
  <?php navItem('reservations.php', 'Reservations', '📅', $current) ?>

  <div class="sidebar-section">Content</div>
  <?php navItem('menu.php',         'Menu Items',   '🍽️', $current) ?>
  <?php navItem('categories.php',   'Categories',   '🗂️', $current) ?>
  <?php navItem('gallery.php',      'Gallery',      '🖼️', $current) ?>
  <?php navItem('reviews.php',      'Reviews',      '⭐', $current, $pendingReviews) ?>

  <div class="sidebar-section">System</div>
  <?php navItem('settings.php',     'Settings',     '⚙️', $current) ?>

  <div class="sidebar-footer">
    <span><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
    <a href="logout.php">Logout</a>
  </div>
</nav>
