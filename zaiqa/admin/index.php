<?php
// admin/index.php — Admin Dashboard
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();

// Stats
$totalOrders       = $pdo->query("SELECT COUNT(*) FROM orders WHERE status!='cancelled'")->fetchColumn();
$todayOrders       = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$totalRevenue      = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='cancelled'")->fetchColumn();
$pendingReserv     = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn();
$totalMenuItems    = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_available=1")->fetchColumn();
$pendingReviews    = $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved=0")->fetchColumn();
$todayReservations = $pdo->query("SELECT COUNT(*) FROM reservations WHERE reservation_date=CURDATE()")->fetchColumn();

// Recent Orders
$recentOrders = $pdo->query(
    "SELECT * FROM orders ORDER BY created_at DESC LIMIT 10"
)->fetchAll();

// Upcoming Reservations
$reservations = $pdo->query(
    "SELECT * FROM reservations WHERE reservation_date >= CURDATE() ORDER BY reservation_date, reservation_time LIMIT 8"
)->fetchAll();

$statusColors = [
    'new'=>'#3498db','confirmed'=>'#2ecc71','preparing'=>'#e67e22',
    'ready'=>'#9b59b6','delivered'=>'#27ae60','cancelled'=>'#e74c3c',
    'pending'=>'#e67e22','completed'=>'#27ae60'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Dashboard — Zaiqa Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/admin.css"/>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-header">
    <h1>Dashboard</h1>
    <span class="admin-date"><?= date('l, d F Y') ?></span>
  </div>

  <!-- Stats Grid -->
  <div class="stats-grid">
    <div class="stat-card red">
      <div class="stat-icon">📦</div>
      <div class="stat-val"><?= $todayOrders ?></div>
      <div class="stat-lbl">Orders Today</div>
    </div>
    <div class="stat-card gold">
      <div class="stat-icon">💰</div>
      <div class="stat-val">Rs <?= number_format($totalRevenue) ?></div>
      <div class="stat-lbl">Total Revenue</div>
    </div>
    <div class="stat-card blue">
      <div class="stat-icon">📅</div>
      <div class="stat-val"><?= $todayReservations ?></div>
      <div class="stat-lbl">Reservations Today</div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon">⭐</div>
      <div class="stat-val"><?= $pendingReviews ?></div>
      <div class="stat-lbl">Pending Reviews</div>
    </div>
    <div class="stat-card purple">
      <div class="stat-icon">🍽️</div>
      <div class="stat-val"><?= $totalMenuItems ?></div>
      <div class="stat-lbl">Active Menu Items</div>
    </div>
    <div class="stat-card orange">
      <div class="stat-icon">🕐</div>
      <div class="stat-val"><?= $pendingReserv ?></div>
      <div class="stat-lbl">Pending Reservations</div>
    </div>
  </div>

  <div class="dash-grid">
    <!-- Recent Orders -->
    <div class="dash-card">
      <div class="dash-card-header">
        <h3>Recent Orders</h3>
        <a href="orders.php" class="view-all">View All →</a>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Ref</th><th>Customer</th><th>Total</th><th>Type</th><th>Status</th><th>Time</th></tr></thead>
          <tbody>
          <?php foreach($recentOrders as $o): ?>
          <tr>
            <td><strong><?= htmlspecialchars($o['order_ref']) ?></strong></td>
            <td><?= htmlspecialchars($o['customer_name']) ?><br><small><?= htmlspecialchars($o['customer_phone']) ?></small></td>
            <td>Rs <?= number_format($o['total']) ?></td>
            <td><span class="type-badge"><?= $o['order_type'] ?></span></td>
            <td><span class="status-pill" style="background:<?= $statusColors[$o['status']] ?? '#888' ?>20;color:<?= $statusColors[$o['status']] ?? '#888' ?>"><?= $o['status'] ?></span></td>
            <td><small><?= date('d M, g:i A', strtotime($o['created_at'])) ?></small></td>
          </tr>
          <?php endforeach; ?>
          <?php if(!$recentOrders): ?><tr><td colspan="6" style="text-align:center;color:#aaa;padding:2rem">No orders yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Upcoming Reservations -->
    <div class="dash-card">
      <div class="dash-card-header">
        <h3>Upcoming Reservations</h3>
        <a href="reservations.php" class="view-all">View All →</a>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Name</th><th>Date</th><th>Time</th><th>Guests</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach($reservations as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['full_name']) ?><br><small><?= htmlspecialchars($r['phone']) ?></small></td>
            <td><?= date('d M Y', strtotime($r['reservation_date'])) ?></td>
            <td><?= date('g:i A', strtotime($r['reservation_time'])) ?></td>
            <td><?= htmlspecialchars($r['guests']) ?></td>
            <td><span class="status-pill" style="background:<?= $statusColors[$r['status']] ?? '#888' ?>20;color:<?= $statusColors[$r['status']] ?? '#888' ?>"><?= $r['status'] ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if(!$reservations): ?><tr><td colspan="5" style="text-align:center;color:#aaa;padding:2rem">No upcoming reservations.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
</body>
</html>
