<?php
// admin/menu.php — CRUD for menu items
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();

$msg = '';

// DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM menu_items WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: menu.php?msg=deleted'); exit;
}

// TOGGLE AVAILABILITY
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = $pdo->prepare("SELECT is_available FROM menu_items WHERE id=?");
    $cur->execute([$id]);
    $val = $cur->fetchColumn();
    $pdo->prepare("UPDATE menu_items SET is_available=? WHERE id=?")->execute([$val ? 0 : 1, $id]);
    header('Location: menu.php?msg=updated'); exit;
}

// ADD / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = (int)($_POST['id'] ?? 0);
    $catId = (int)$_POST['category_id'];
    $name  = trim($_POST['name']);
    $desc  = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $img   = trim($_POST['image_url']);
    $badge = trim($_POST['badge'] ?? '');
    $avail = isset($_POST['is_available']) ? 1 : 0;
    $feat  = isset($_POST['is_featured'])  ? 1 : 0;

    if ($id) {
        $pdo->prepare(
            "UPDATE menu_items SET category_id=?,name=?,description=?,price=?,image_url=?,badge=?,is_available=?,is_featured=? WHERE id=?"
        )->execute([$catId,$name,$desc,$price,$img,$badge?:null,$avail,$feat,$id]);
    } else {
        $pdo->prepare(
            "INSERT INTO menu_items (category_id,name,description,price,image_url,badge,is_available,is_featured) VALUES(?,?,?,?,?,?,?,?)"
        )->execute([$catId,$name,$desc,$price,$img,$badge?:null,$avail,$feat]);
    }
    header('Location: menu.php?msg=saved'); exit;
}

$categories = $pdo->query("SELECT * FROM categories WHERE active=1 ORDER BY sort_order")->fetchAll();

// Edit mode
$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM menu_items WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}

$catFilter = $_GET['cat'] ?? 'all';
$where = $catFilter !== 'all' ? "AND c.slug='$catFilter'" : '';
$items = $pdo->query(
    "SELECT m.*, c.name AS cat_name, c.slug AS cat_slug
     FROM menu_items m JOIN categories c ON m.category_id=c.id
     WHERE 1 $where ORDER BY c.sort_order, m.sort_order, m.id"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Menu Items — Zaiqa Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/admin.css"/>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-header">
    <h1>Menu Items</h1>
    <button class="btn-primary" onclick="toggleForm()">+ Add Item</button>
  </div>

  <?php if(isset($_GET['msg'])): ?>
  <div class="alert-success">
    <?php echo ['saved'=>'Item saved!','deleted'=>'Item deleted.','updated'=>'Item updated.'][$_GET['msg']] ?? 'Done.'; ?>
  </div>
  <?php endif; ?>

  <!-- Add/Edit Form -->
  <div class="admin-form-card" id="itemForm" style="display:<?= $edit?'block':'none' ?>">
    <h3><?= $edit ? 'Edit Item' : 'Add New Item' ?></h3>
    <form method="POST">
      <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"/><?php endif; ?>
      <div class="form-row-2">
        <div class="fg"><label>Category *</label>
          <select name="category_id" required>
            <?php foreach($categories as $c): ?>
            <option value="<?=$c['id']?>" <?= ($edit && $edit['category_id']==$c['id'])||(!$edit&&$c['slug']==='desi')?'selected':'' ?>><?=$c['emoji']?> <?=htmlspecialchars($c['name'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label>Badge</label>
          <select name="badge">
            <option value="">None</option>
            <?php foreach(['popular','spicy','new','veg','value'] as $b): ?>
            <option value="<?=$b?>" <?= ($edit&&$edit['badge']===$b)?'selected':'' ?>><?= ucfirst($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="fg"><label>Name *</label><input type="text" name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required/></div>
      <div class="fg"><label>Description *</label><textarea name="description" rows="2" required><?= htmlspecialchars($edit['description'] ?? '') ?></textarea></div>
      <div class="form-row-2">
        <div class="fg"><label>Price (Rs) *</label><input type="number" name="price" value="<?= $edit['price'] ?? '' ?>" step="0.01" required/></div>
        <div class="fg"><label>Image URL</label><input type="url" name="image_url" value="<?= htmlspecialchars($edit['image_url'] ?? '') ?>" placeholder="https://..."/></div>
      </div>
      <div class="form-row-2">
        <label class="check-label"><input type="checkbox" name="is_available" <?= (!$edit||$edit['is_available'])?'checked':'' ?>/> Available on menu</label>
        <label class="check-label"><input type="checkbox" name="is_featured"  <?= ($edit&&$edit['is_featured'])?'checked':'' ?>/> Featured item</label>
      </div>
      <div style="display:flex;gap:1rem;margin-top:1rem">
        <button type="submit" class="btn-primary">💾 Save Item</button>
        <a href="menu.php" class="btn-secondary">Cancel</a>
      </div>
    </form>
  </div>

  <!-- Filter -->
  <div class="filter-tabs" style="margin-top:1.5rem">
    <a href="menu.php" class="ftab <?=$catFilter==='all'?'active':''?>">All</a>
    <?php foreach($categories as $c): ?>
    <a href="menu.php?cat=<?=$c['slug']?>" class="ftab <?=$catFilter===$c['slug']?'active':''?>"><?=$c['emoji']?> <?=htmlspecialchars($c['name'])?></a>
    <?php endforeach; ?>
  </div>

  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Badge</th><th>Status</th><th>Featured</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($items as $item): ?>
      <tr>
        <td><img src="<?=htmlspecialchars($item['image_url'])?>" style="width:50px;height:50px;object-fit:cover;border-radius:6px" onerror="this.style.display='none'"/></td>
        <td><strong><?=htmlspecialchars($item['name'])?></strong><br><small style="color:#888"><?=htmlspecialchars(substr($item['description'],0,50))?>…</small></td>
        <td><?=htmlspecialchars($item['cat_name'])?></td>
        <td><strong>Rs <?=number_format($item['price'])?></strong></td>
        <td><?= $item['badge'] ? "<span class='type-badge'>{$item['badge']}</span>" : '—' ?></td>
        <td>
          <a href="menu.php?toggle=<?=$item['id']?>" class="status-pill" style="background:<?=$item['is_available']?'#d5f5e3':'#fde8d8'?>;color:<?=$item['is_available']?'#1e8449':'#a04000'?>">
            <?=$item['is_available']?'✓ Active':'✗ Hidden'?>
          </a>
        </td>
        <td><?=$item['is_featured']?'⭐':''?></td>
        <td style="white-space:nowrap">
          <a href="menu.php?edit=<?=$item['id']?>" class="mini-btn">Edit</a>
          <a href="menu.php?delete=<?=$item['id']?>" class="mini-btn danger" onclick="return confirm('Delete this item?')">Del</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<script>function toggleForm(){const f=document.getElementById('itemForm');f.style.display=f.style.display==='none'?'block':'none'}</script>
</body>
</html>
