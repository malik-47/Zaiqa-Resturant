<?php
// admin/orders.php
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $allowed = ['new','confirmed','preparing','ready','delivered','cancelled'];
    $status  = in_array($_POST['status'], $allowed) ? $_POST['status'] : 'new';
    $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, (int)$_POST['order_id']]);
    header('Location: orders.php?updated=1'); exit;
}

$filter = $_GET['status'] ?? 'all';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$where  = $filter !== 'all' ? "WHERE status='$filter'" : '';
$total  = $pdo->query("SELECT COUNT(*) FROM orders $where")->fetchColumn();
$orders = $pdo->query("SELECT * FROM orders $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
$pages  = ceil($total / $limit);

$statusColors = [
    'new'=>'#3498db','confirmed'=>'#2ecc71','preparing'=>'#e67e22',
    'ready'=>'#9b59b6','delivered'=>'#27ae60','cancelled'=>'#e74c3c'
];
$allStatuses = ['new','confirmed','preparing','ready','delivered','cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Orders — Zaiqa Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/admin.css"/>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-header">
    <h1>Orders <?php if($filter!=='all'): ?><span class="filter-badge"><?= $filter ?></span><?php endif; ?></h1>
    <span><?= $total ?> total</span>
  </div>

  <?php if(isset($_GET['updated'])): ?>
  <div class="alert-success">Order status updated.</div>
  <?php endif; ?>

  <!-- Filter Tabs -->
  <div class="filter-tabs">
    <a href="orders.php" class="ftab <?= $filter==='all'?'active':'' ?>">All</a>
    <?php foreach($allStatuses as $s): ?>
    <a href="orders.php?status=<?=$s?>" class="ftab <?= $filter===$s?'active':'' ?>"><?= ucfirst($s) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>Ref</th><th>Customer</th><th>Items</th><th>Total</th><th>Type</th><th>Status</th><th>Date</th><th>Action</th></tr>
      </thead>
      <tbody>
      <?php foreach($orders as $o):
        $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
        $items->execute([$o['id']]);
        $orderItems = $items->fetchAll();
      ?>
      <tr>
        <td><strong style="color:#c0392b"><?= htmlspecialchars($o['order_ref']) ?></strong></td>
        <td>
          <?= htmlspecialchars($o['customer_name']) ?><br>
          <small><?= htmlspecialchars($o['customer_phone']) ?></small>
          <?php if($o['delivery_address']): ?>
          <br><small style="color:#888"><?= htmlspecialchars(substr($o['delivery_address'],0,40)) ?>…</small>
          <?php endif; ?>
        </td>
        <td>
          <?php foreach($orderItems as $i): ?>
          <div style="font-size:.78rem"><?= $i['quantity'] ?>× <?= htmlspecialchars($i['item_name']) ?></div>
          <?php endforeach; ?>
        </td>
        <td><strong>Rs <?= number_format($o['total']) ?></strong><br><small>Del: Rs <?= $o['delivery_fee'] ?></small></td>
        <td><span class="type-badge"><?= $o['order_type'] ?></span></td>
        <td><span class="status-pill" style="background:<?= $statusColors[$o['status']]?>20;color:<?= $statusColors[$o['status']] ?>"><?= $o['status'] ?></span></td>
        <td><small><?= date('d M, g:i A', strtotime($o['created_at'])) ?></small></td>
        <td>
          <form method="POST" style="display:flex;gap:.4rem;align-items:center">
            <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
            <select name="status" class="mini-select">
              <?php foreach($allStatuses as $s): ?>
              <option value="<?=$s?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="mini-btn">Save</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$orders): ?><tr><td colspan="8" style="text-align:center;color:#aaa;padding:2rem">No orders found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if($pages > 1): ?>
  <div class="pagination">
    <?php for($i=1;$i<=$pages;$i++): ?>
    <a href="?status=<?=$filter?>&page=<?=$i?>" class="pg-btn <?= $page===$i?'active':'' ?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</main>
</body>
</html>
