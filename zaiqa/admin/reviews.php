<?php
// admin/reviews.php
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();

// Approve / Feature / Delete
if (isset($_GET['approve']))  { $pdo->prepare("UPDATE reviews SET is_approved=1 WHERE id=?")->execute([(int)$_GET['approve']]);  header('Location: reviews.php?msg=approved'); exit; }
if (isset($_GET['reject']))   { $pdo->prepare("UPDATE reviews SET is_approved=0 WHERE id=?")->execute([(int)$_GET['reject']]);   header('Location: reviews.php?msg=rejected'); exit; }
if (isset($_GET['feature']))  { $pdo->prepare("UPDATE reviews SET is_featured=1 WHERE id=?")->execute([(int)$_GET['feature']]);  header('Location: reviews.php?msg=featured'); exit; }
if (isset($_GET['unfeature'])){ $pdo->prepare("UPDATE reviews SET is_featured=0 WHERE id=?")->execute([(int)$_GET['unfeature']]);header('Location: reviews.php?msg=updated');  exit; }
if (isset($_GET['delete']))   { $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([(int)$_GET['delete']]);                header('Location: reviews.php?msg=deleted');  exit; }

$filter = $_GET['filter'] ?? 'pending';
$where  = match($filter) {
    'approved' => 'WHERE is_approved=1',
    'featured' => 'WHERE is_featured=1',
    'all'      => '',
    default    => 'WHERE is_approved=0',
};

$reviews = $pdo->query("SELECT * FROM reviews $where ORDER BY created_at DESC")->fetchAll();
$msgs = ['approved'=>'Review approved!','rejected'=>'Review hidden.','featured'=>'Marked as featured!','updated'=>'Updated.','deleted'=>'Review deleted.'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Reviews — Zaiqa Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/admin.css"/>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-header">
    <h1>Reviews</h1>
    <span><?= count($reviews) ?> shown</span>
  </div>

  <?php if(isset($_GET['msg']) && isset($msgs[$_GET['msg']])): ?>
  <div class="alert-success"><?= $msgs[$_GET['msg']] ?></div>
  <?php endif; ?>

  <div class="filter-tabs">
    <a href="reviews.php?filter=pending"  class="ftab <?= $filter==='pending' ?'active':'' ?>">⏳ Pending Approval</a>
    <a href="reviews.php?filter=approved" class="ftab <?= $filter==='approved'?'active':'' ?>">✅ Approved</a>
    <a href="reviews.php?filter=featured" class="ftab <?= $filter==='featured'?'active':'' ?>">⭐ Featured</a>
    <a href="reviews.php?filter=all"      class="ftab <?= $filter==='all'     ?'active':'' ?>">All</a>
  </div>

  <div class="reviews-grid">
    <?php foreach($reviews as $r): ?>
    <div class="review-card <?= $r['is_featured']?'featured':'' ?>">
      <div class="review-meta">
        <div>
          <strong><?= htmlspecialchars($r['name']) ?></strong>
          <?php if($r['location']): ?><span style="color:#888;font-size:.8rem"> · <?= htmlspecialchars($r['location']) ?></span><?php endif; ?>
          <div style="color:#f0c040;font-size:.9rem;margin-top:2px"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></div>
        </div>
        <div style="text-align:right">
          <div class="status-pill" style="background:<?=$r['is_approved']?'#d5f5e3':'#fde8d8'?>;color:<?=$r['is_approved']?'#1e8449':'#c0392b'?>">
            <?= $r['is_approved'] ? 'Approved' : 'Pending' ?>
          </div>
          <?php if($r['is_featured']): ?><div style="font-size:.75rem;color:#d4a017;margin-top:4px">⭐ Featured</div><?php endif; ?>
        </div>
      </div>
      <p class="review-text">"<?= htmlspecialchars($r['review_text']) ?>"</p>
      <div class="review-date"><?= date('d M Y, g:i A', strtotime($r['created_at'])) ?></div>
      <div class="review-actions">
        <?php if(!$r['is_approved']): ?>
        <a href="reviews.php?approve=<?=$r['id']?>&filter=<?=$filter?>" class="mini-btn green">✓ Approve</a>
        <?php else: ?>
        <a href="reviews.php?reject=<?=$r['id']?>&filter=<?=$filter?>"  class="mini-btn">Hide</a>
        <?php endif; ?>
        <?php if(!$r['is_featured']): ?>
        <a href="reviews.php?feature=<?=$r['id']?>&filter=<?=$filter?>"   class="mini-btn">⭐ Feature</a>
        <?php else: ?>
        <a href="reviews.php?unfeature=<?=$r['id']?>&filter=<?=$filter?>" class="mini-btn">Unfeature</a>
        <?php endif; ?>
        <a href="reviews.php?delete=<?=$r['id']?>&filter=<?=$filter?>" class="mini-btn danger"
           onclick="return confirm('Delete this review permanently?')">Delete</a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if(!$reviews): ?>
    <div style="grid-column:1/-1;text-align:center;color:#aaa;padding:3rem">No reviews found.</div>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
