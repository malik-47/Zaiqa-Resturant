<?php
// admin/reservations.php
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['res_id'], $_POST['status'])) {
    $allowed = ['pending','confirmed','cancelled','completed'];
    $status  = in_array($_POST['status'], $allowed) ? $_POST['status'] : 'pending';
    $pdo->prepare("UPDATE reservations SET status=? WHERE id=?")->execute([$status, (int)$_POST['res_id']]);
    header('Location: reservations.php?updated=1'); exit;
}

// Delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM reservations WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: reservations.php?deleted=1'); exit;
}

$filter = $_GET['status'] ?? 'all';
$date   = $_GET['date']   ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$conds = [];
$params = [];
if ($filter !== 'all') { $conds[] = "status=?"; $params[] = $filter; }
if ($date)             { $conds[] = "reservation_date=?"; $params[] = $date; }
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$total = $pdo->prepare("SELECT COUNT(*) FROM reservations $where");
$total->execute($params);
$total = $total->fetchColumn();

$st = $pdo->prepare("SELECT * FROM reservations $where ORDER BY reservation_date, reservation_time LIMIT $limit OFFSET $offset");
$st->execute($params);
$reservations = $st->fetchAll();
$pages = ceil($total / $limit);

$statusColors = [
    'pending'=>'#e67e22','confirmed'=>'#27ae60','cancelled'=>'#e74c3c','completed'=>'#3498db'
];
$allStatuses = ['pending','confirmed','cancelled','completed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Reservations — Zaiqa Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/admin.css"/>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-header">
    <h1>Reservations</h1>
    <span><?= $total ?> total</span>
  </div>

  <?php if(isset($_GET['updated'])): ?><div class="alert-success">Status updated.</div><?php endif; ?>
  <?php if(isset($_GET['deleted'])): ?><div class="alert-success">Reservation deleted.</div><?php endif; ?>

  <!-- Filters -->
  <div class="filter-row">
    <div class="filter-tabs">
      <a href="reservations.php" class="ftab <?= $filter==='all'?'active':'' ?>">All</a>
      <?php foreach($allStatuses as $s): ?>
      <a href="reservations.php?status=<?=$s?>" class="ftab <?= $filter===$s?'active':'' ?>"><?= ucfirst($s) ?></a>
      <?php endforeach; ?>
    </div>
    <form method="GET" style="display:flex;gap:.5rem;align-items:center">
      <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="mini-input"/>
      <?php if($filter!=='all'): ?><input type="hidden" name="status" value="<?=htmlspecialchars($filter)?>"/><?php endif; ?>
      <button type="submit" class="mini-btn">Filter</button>
      <?php if($date): ?><a href="reservations.php" class="mini-btn">Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>Name</th><th>Phone</th><th>Date</th><th>Time</th><th>Guests</th><th>Cuisine</th><th>Special Requests</th><th>Status</th><th>Booked At</th><th>Action</th></tr>
      </thead>
      <tbody>
      <?php foreach($reservations as $r): ?>
      <tr>
        <td><strong><?= htmlspecialchars($r['full_name']) ?></strong>
          <?php if($r['email']): ?><br><small><?= htmlspecialchars($r['email']) ?></small><?php endif; ?></td>
        <td><?= htmlspecialchars($r['phone']) ?></td>
        <td><?= date('d M Y', strtotime($r['reservation_date'])) ?></td>
        <td><?= date('g:i A', strtotime($r['reservation_time'])) ?></td>
        <td><?= htmlspecialchars($r['guests']) ?></td>
        <td><?= htmlspecialchars($r['cuisine_pref']) ?></td>
        <td><small style="color:#666"><?= $r['special_requests'] ? htmlspecialchars(substr($r['special_requests'],0,50)).'…' : '—' ?></small></td>
        <td>
          <span class="status-pill" style="background:<?=$statusColors[$r['status']]?>20;color:<?=$statusColors[$r['status']]?>">
            <?= ucfirst($r['status']) ?>
          </span>
        </td>
        <td><small><?= date('d M, g:i A', strtotime($r['created_at'])) ?></small></td>
        <td>
          <form method="POST" style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
            <input type="hidden" name="res_id" value="<?= $r['id'] ?>"/>
            <select name="status" class="mini-select">
              <?php foreach($allStatuses as $s): ?>
              <option value="<?=$s?>" <?= $r['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="mini-btn">Save</button>
            <a href="reservations.php?delete=<?=$r['id']?>" class="mini-btn danger"
               onclick="return confirm('Delete this reservation?')">Del</a>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$reservations): ?>
      <tr><td colspan="10" style="text-align:center;color:#aaa;padding:2rem">No reservations found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if($pages > 1): ?>
  <div class="pagination">
    <?php for($i=1;$i<=$pages;$i++): ?>
    <a href="?status=<?=$filter?>&date=<?=$date?>&page=<?=$i?>" class="pg-btn <?= $page===$i?'active':'' ?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</main>
</body>
</html>
