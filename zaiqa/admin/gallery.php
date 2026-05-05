<?php
// admin/gallery.php
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM gallery WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: gallery.php?msg=deleted'); exit;
}
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = $pdo->prepare("SELECT active FROM gallery WHERE id=?"); $cur->execute([$id]);
    $val = $cur->fetchColumn();
    $pdo->prepare("UPDATE gallery SET active=? WHERE id=?")->execute([$val?0:1, $id]);
    header('Location: gallery.php?msg=updated'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $url   = trim($_POST['image_url'] ?? '');
    $span  = trim($_POST['span_class'] ?? '');
    $sort  = (int)($_POST['sort_order'] ?? 0);
    if ($id) {
        $pdo->prepare("UPDATE gallery SET title=?,image_url=?,span_class=?,sort_order=? WHERE id=?")
            ->execute([$title,$url,$span?:null,$sort,$id]);
    } else {
        $pdo->prepare("INSERT INTO gallery (title,image_url,span_class,sort_order) VALUES(?,?,?,?)")
            ->execute([$title,$url,$span?:null,$sort]);
    }
    header('Location: gallery.php?msg=saved'); exit;
}
$edit  = null;
if (isset($_GET['edit'])) { $st=$pdo->prepare("SELECT * FROM gallery WHERE id=?"); $st->execute([(int)$_GET['edit']]); $edit=$st->fetch(); }
$items = $pdo->query("SELECT * FROM gallery ORDER BY sort_order, id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Gallery — Zaiqa Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/admin.css"/>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-header">
    <h1>Gallery</h1>
    <button class="btn-primary" onclick="toggleForm()">+ Add Image</button>
  </div>

  <?php if(isset($_GET['msg'])): ?>
  <div class="alert-success"><?= ['saved'=>'Image saved!','deleted'=>'Image deleted.','updated'=>'Updated.'][$_GET['msg']] ?? 'Done.' ?></div>
  <?php endif; ?>

  <!-- Form -->
  <div class="admin-form-card" id="galleryForm" style="display:<?= $edit?'block':'none' ?>">
    <h3><?= $edit ? 'Edit Image' : 'Add Gallery Image' ?></h3>
    <form method="POST">
      <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"/><?php endif; ?>
      <div class="form-row-2">
        <div class="fg"><label>Title</label><input type="text" name="title" value="<?= htmlspecialchars($edit['title']??'') ?>" placeholder="e.g. Biryani Special"/></div>
        <div class="fg"><label>Grid Span</label>
          <select name="span_class">
            <option value="" <?= (!$edit||!$edit['span_class'])?'selected':''?>>Normal (1×1)</option>
            <option value="tall" <?= ($edit&&$edit['span_class']==='tall')?'selected':''?>>Tall (1×2 rows)</option>
            <option value="wide" <?= ($edit&&$edit['span_class']==='wide')?'selected':''?>>Wide (2×1 cols)</option>
          </select>
        </div>
      </div>
      <div class="fg"><label>Image URL *</label><input type="url" name="image_url" value="<?= htmlspecialchars($edit['image_url']??'') ?>" placeholder="https://..." required/></div>
      <div class="fg"><label>Sort Order</label><input type="number" name="sort_order" value="<?= $edit['sort_order']??0 ?>" min="0"/></div>
      <div style="display:flex;gap:1rem;margin-top:1rem">
        <button type="submit" class="btn-primary">💾 Save</button>
        <a href="gallery.php" class="btn-secondary">Cancel</a>
      </div>
    </form>
  </div>

  <!-- Gallery Grid Preview -->
  <div class="gallery-admin-grid">
    <?php foreach($items as $g): ?>
    <div class="g-admin-card <?= !$g['active']?'hidden-card':'' ?>">
      <img src="<?= htmlspecialchars($g['image_url']) ?>" alt="<?= htmlspecialchars($g['title']??'') ?>"
           onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'"/>
      <div class="g-admin-info">
        <strong><?= htmlspecialchars($g['title']??'Untitled') ?></strong>
        <span style="font-size:.75rem;color:#888"><?= $g['span_class']?ucfirst($g['span_class']):'Normal' ?> · Sort: <?= $g['sort_order'] ?></span>
        <div class="g-admin-actions">
          <a href="gallery.php?edit=<?=$g['id']?>" class="mini-btn">Edit</a>
          <a href="gallery.php?toggle=<?=$g['id']?>" class="mini-btn"><?= $g['active']?'Hide':'Show' ?></a>
          <a href="gallery.php?delete=<?=$g['id']?>" class="mini-btn danger" onclick="return confirm('Delete?')">Del</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</main>
<script>function toggleForm(){const f=document.getElementById('galleryForm');f.style.display=f.style.display==='none'?'block':'none'}</script>
</body>
</html>
