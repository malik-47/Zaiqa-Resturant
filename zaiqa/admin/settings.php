<?php
// admin/settings.php
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['restaurant_name','tagline','phone','address','hours_weekday','hours_weekend','hero_video_url','currency','delivery_radius','min_order'];
    foreach ($fields as $field) {
        $val = trim($_POST[$field] ?? '');
        $pdo->prepare("UPDATE settings SET `value`=? WHERE `key`=?")->execute([$val, $field]);
    }
    // Change admin password
    if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE admin_users SET password_hash=? WHERE id=?")->execute([$hash, $_SESSION['admin_id']]);
            $msg = 'Settings and password saved!';
        } else {
            $msg = 'Passwords do not match — other settings saved.';
        }
    } else {
        $msg = 'Settings saved successfully!';
    }
}

$st = $pdo->query("SELECT `key`,`value` FROM settings");
$s  = [];
foreach ($st as $row) $s[$row['key']] = $row['value'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Settings — Zaiqa Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/admin.css"/>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-header"><h1>⚙️ Settings</h1></div>
  <?php if($msg): ?><div class="alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <form method="POST">
    <div class="settings-grid">
      <!-- Restaurant Info -->
      <div class="settings-card">
        <h3>🍽️ Restaurant Info</h3>
        <div class="fg"><label>Restaurant Name</label><input type="text" name="restaurant_name" value="<?= htmlspecialchars($s['restaurant_name']??'') ?>"/></div>
        <div class="fg"><label>Tagline</label><input type="text" name="tagline" value="<?= htmlspecialchars($s['tagline']??'') ?>"/></div>
        <div class="fg"><label>Phone Number</label><input type="text" name="phone" value="<?= htmlspecialchars($s['phone']??'') ?>"/></div>
        <div class="fg"><label>Address</label><textarea name="address" rows="2"><?= htmlspecialchars($s['address']??'') ?></textarea></div>
      </div>

      <!-- Hours -->
      <div class="settings-card">
        <h3>🕐 Opening Hours</h3>
        <div class="fg"><label>Weekday Hours</label><input type="text" name="hours_weekday" value="<?= htmlspecialchars($s['hours_weekday']??'') ?>" placeholder="Mon–Thu: 12:00 PM – 11:00 PM"/></div>
        <div class="fg"><label>Weekend Hours</label><input type="text" name="hours_weekend" value="<?= htmlspecialchars($s['hours_weekend']??'') ?>" placeholder="Fri–Sun: 11:00 AM – 12:00 AM"/></div>

        <h3 style="margin-top:1.5rem">💰 Ordering</h3>
        <div class="fg"><label>Currency Symbol</label><input type="text" name="currency" value="<?= htmlspecialchars($s['currency']??'Rs') ?>" style="max-width:120px"/></div>
        <div class="fg"><label>Delivery Radius (km)</label><input type="number" name="delivery_radius" value="<?= htmlspecialchars($s['delivery_radius']??'10') ?>"/></div>
        <div class="fg"><label>Minimum Order (Rs)</label><input type="number" name="min_order" value="<?= htmlspecialchars($s['min_order']??'500') ?>"/></div>
      </div>

      <!-- Media -->
      <div class="settings-card">
        <h3>🎥 Hero Video</h3>
        <div class="fg"><label>Hero Video URL (.mp4)</label><input type="url" name="hero_video_url" value="<?= htmlspecialchars($s['hero_video_url']??'') ?>"/></div>
        <p style="font-size:.78rem;color:#888;margin-top:-.5rem">Upload your video to a CDN and paste the direct .mp4 link above.</p>
      </div>

      <!-- Change Password -->
      <div class="settings-card">
        <h3>🔐 Change Password</h3>
        <div class="fg"><label>New Password</label><input type="password" name="new_password" placeholder="Leave blank to keep current"/></div>
        <div class="fg"><label>Confirm Password</label><input type="password" name="confirm_password" placeholder="Repeat new password"/></div>
        <p style="font-size:.78rem;color:#888">Only filled if you want to change the password.</p>
      </div>
    </div>

    <div style="margin-top:1.5rem">
      <button type="submit" class="btn-primary" style="min-width:200px">💾 Save All Settings</button>
    </div>
  </form>
</main>
</body>
</html>
