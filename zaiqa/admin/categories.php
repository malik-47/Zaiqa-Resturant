<?php
// admin/categories.php
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();

if (isset($_GET['delete'])) {
    // Check if items exist
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id=?");
    $cnt->execute([(int)$_GET['delete']]);
    if ($cnt->fetchColumn() > 0) {
        header('Location: categories.php?msg=haschild'); exit;
    }
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: categories.php?msg=deleted'); exit;
}
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = $pdo->prepare("SELECT active FROM categories WHERE id=?"); $cur->execute([$id]);
    $val = $cur->fetchColumn();
    $pdo->prepare("UPDATE categories SET active=? WHERE id=?")->execute([$val?0:1,$id]);
    header('Location: categories.php?msg=updated'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = (int)($_POST['id']??0);
    $slug  = strtolower(preg_replace('/[^a-z0-9]/','-',trim($_POST['slug']??'')));
    $name  = trim($_POST['name']??'');
    $emoji = trim($_POST['emoji']??'');
    $sort  = (int)($_POST['sort_order']??0);
    if ($id) {
        $pdo->prepare("UPDATE categories SET slug=?,name=?,emoji=?,sort_order=? WHERE id=?")->execute([$slug,$name,$emoji,$sort,$id]);
    } else {
        $pdo->prepare("INSERT INTO categories (slug,name,emoji,sort_order) VALUES(?,?,?,?)")->execute([$slug,$name,$emoji,$sort]);
    }
    header('Location: categories.php?msg=saved'); exit;
}
$edit = null;
if (isset($_GET['edit'])) { $st=$pdo->prepare("SELECT * FROM categories WHERE id=?"); $st->execute([(int)$_GET['edit']]); $edit=$st->fetch(); }
$cats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM menu_items WHERE category_id=c.id) as item_count FROM categories c ORDER BY sort_order")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Categories — Zaiqa Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/admin.css"/>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-header">
    <h1>Menu Categories</h1>
    <button class="btn-primary" onclick="toggleForm()">+ Add Category</button>
  </div>

  <?php
  $msgs = ['saved'=>'Category saved!','deleted'=>'Category deleted.','updated'=>'Updated.','haschild'=>'Cannot delete — category has menu items.'];
  if(isset($_GET['msg']) && isset($msgs[$_GET['msg']])): ?>
  <div class="alert-success <?= $_GET['msg']==='haschild'?'alert-warn':'' ?>"><?= $msgs[$_GET['msg']] ?></div>
  <?php endif; ?>

  <!-- Form -->
  <div class="admin-form-card" id="catForm" style="display:<?= $edit?'block':'none' ?>">
    <h3><?= $edit ? 'Edit Category' : 'Add Category' ?></h3>
    <form method="POST">
      <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"/><?php endif; ?>
      <div class="form-row-2">
        <div class="fg"><label>Display Name *</label><input type="text" name="name" value="<?= htmlspecialchars($edit['name']??'') ?>" required placeholder="e.g. Desi &amp; Pakistani"/></div>
        <div class="fg"><label>Slug (URL key) *</label><input type="text" name="slug" value="<?= htmlspecialchars($edit['slug']??'') ?>" required placeholder="e.g. desi"/></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Emoji</label><input type="text" name="emoji" value="<?= htmlspecialchars($edit['emoji']??'') ?>" placeholder="🍛" maxlength="5"/></div>
        <div class="fg"><label>Sort Order</label><input type="number" name="sort_order" value="<?= $edit['sort_order']??0 ?>" min="0"/></div>
      </div>
      <div style="display:flex;gap:1rem;margin-top:1rem">
        <button type="submit" class="btn-primary">💾 Save</button>
        <a href="categories.php" class="btn-secondary">Cancel</a>
      </div>
    </form>
  </div>

  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Emoji</th><th>Name</th><th>Slug</th><th>Items</th><th>Sort</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($cats as $c): ?>
      <tr>
        <td style="font-size:1.4rem"><?= htmlspecialchars($c['emoji']??'') ?></td>
        <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
        <td><code style="background:#f0ebe0;padding:2px 8px;border-radius:4px;font-size:.8rem"><?= htmlspecialchars($c['slug']) ?></code></td>
        <td><?= $c['item_count'] ?> items</td>
        <td><?= $c['sort_order'] ?></td>
        <td>
          <span class="status-pill" style="background:<?=$c['active']?'#d5f5e3':'#fde8d8'?>;color:<?=$c['active']?'#1e8449':'#c0392b'?>">
            <?= $c['active'] ? 'Active' : 'Hidden' ?>
          </span>
        </td>
        <td style="white-space:nowrap">
          <a href="categories.php?edit=<?=$c['id']?>" class="mini-btn">Edit</a>
          <a href="categories.php?toggle=<?=$c['id']?>" class="mini-btn"><?=$c['active']?'Hide':'Show'?></a>
          <a href="categories.php?delete=<?=$c['id']?>" class="mini-btn danger" onclick="return confirm('Delete category?')">Del</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<script>function toggleForm(){const f=document.getElementById('catForm');f.style.display=f.style.display==='none'?'block':'none'}</script>
</body>
</html>
