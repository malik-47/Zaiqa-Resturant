<?php
// ============================================================
// install.php — Run this ONCE to create all tables + seed data
// Visit: http://localhost/zaiqa/install.php
// DELETE this file after successful installation!
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');    // ← change if needed
define('DB_PASS', '');        // ← change if needed
define('DB_NAME', 'zaiqa_restaurant');

$errors = [];
$steps  = [];

try {
    // Connect WITHOUT database first
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $steps[] = "✅ Connected to MySQL";

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $steps[] = "✅ Database <strong>" . DB_NAME . "</strong> created (or already exists)";

    // Switch to it
    $pdo->exec("USE `" . DB_NAME . "`");
    $steps[] = "✅ Using database <strong>" . DB_NAME . "</strong>";

    // ── DROP old tables (clean reinstall) ─────────────────────
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    foreach (['order_items','orders','reservations','reviews','gallery','menu_items','categories','settings','admin_users'] as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    $steps[] = "✅ Old tables cleared";

    // ── SETTINGS ──────────────────────────────────────────────
    $pdo->exec("CREATE TABLE settings (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        `key`      VARCHAR(100) NOT NULL UNIQUE,
        `value`    TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("INSERT INTO settings (`key`,`value`) VALUES
        ('restaurant_name','Zaiqa Restaurant'),
        ('tagline','Three Cuisines. One Destination.'),
        ('phone','0303 6417714'),
        ('address','Gulgasht Colony, Multan, Punjab, Pakistan'),
        ('hours_weekday','Mon–Thu: 12:00 PM – 11:00 PM'),
        ('hours_weekend','Fri–Sun: 11:00 AM – 12:00 AM'),
        ('hero_video_url','https://assets.mixkit.co/videos/preview/mixkit-cooking-a-meat-barbecue-in-a-restaurant-34702-large.mp4'),
        ('google_maps_embed','https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3472.8!2d71.4785!3d30.1968!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sGulgasht+Colony%2C+Multan!5e0!3m2!1sen!2spk!4v1'),
        ('currency','Rs'),
        ('delivery_radius','10'),
        ('min_order','500')
    ");
    $steps[] = "✅ Settings table created & seeded";

    // ── CATEGORIES ────────────────────────────────────────────
    $pdo->exec("CREATE TABLE categories (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        slug       VARCHAR(50)  NOT NULL UNIQUE,
        name       VARCHAR(100) NOT NULL,
        emoji      VARCHAR(10),
        sort_order INT DEFAULT 0,
        active     TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB");

    $pdo->exec("INSERT INTO categories (slug,name,emoji,sort_order) VALUES
        ('desi',   'Desi & Pakistani','🍛',1),
        ('fast',   'Fast Food',       '🍔',2),
        ('chinese','Chinese',         '🍜',3),
        ('drinks', 'Drinks',          '🥤',4),
        ('dessert','Desserts',        '🍮',5)
    ");
    $steps[] = "✅ Categories table created & seeded";

    // ── MENU ITEMS ────────────────────────────────────────────
    $pdo->exec("CREATE TABLE menu_items (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        category_id  INT NOT NULL,
        name         VARCHAR(150) NOT NULL,
        description  TEXT,
        price        DECIMAL(10,2) NOT NULL,
        image_url    VARCHAR(500),
        badge        VARCHAR(50) DEFAULT NULL,
        is_available TINYINT(1) DEFAULT 1,
        is_featured  TINYINT(1) DEFAULT 0,
        sort_order   INT DEFAULT 0,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $menuItems = [
        // DESI (cat 1)
        [1,'Lahori Karahi',       'Slow-cooked mutton in rich tomato masala with ginger & green chilli.',        2500,'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=600&q=80','spicy',1],
        [1,'Seekh Kebab Platter', 'Charcoal-grilled minced beef skewers served with chutney & raita.',           2200,'https://images.unsplash.com/photo-1574894709920-11b28e7367e3?w=600&q=80','popular',1],
        [1,'Mutton Biryani',      'Fragrant basmati layered with spiced mutton, fried onions & saffron.',        2500,'https://images.unsplash.com/photo-1589302168068-964664d93dc0?w=600&q=80','popular',1],
        [1,'Nihari',              'Traditional slow-cooked beef shank stew, served with naan & lemon.',          2200,'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600&q=80',null,0],
        [1,'Chicken Handi',       'Tender chicken in creamy handi-style aromatic gravy. Served with naan.',      2000,'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=600&q=80',null,0],
        [1,'Chicken Tikka',       'Marinated chicken chunks grilled in a clay oven (tandoor).',                  2200,'https://images.unsplash.com/photo-1596797038530-2c107229654b?w=600&q=80','popular',0],
        [1,'Daal Makhni',         'Slow-cooked black lentils in a buttery, spiced tomato base.',                 1200,'https://images.unsplash.com/photo-1606491956689-2ea866880c84?w=600&q=80','veg',0],
        [1,'Fish Curry',          'Fresh river fish in tangy desi masala with mustard seeds.',                   3000,'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?w=600&q=80','spicy',0],
        // FAST FOOD (cat 2)
        [2,'Zaiqa Special Burger','Double beef patty, special sauce, cheddar cheese, caramelised onions.',       2500,'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&q=80','popular',1],
        [2,'Crispy Chicken Burger','Crunchy fried chicken fillet with coleslaw & chipotle mayo.',                2000,'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?w=600&q=80',null,0],
        [2,'BBQ Chicken Pizza',   'Smoky BBQ sauce, grilled chicken, mozzarella & jalapeños. 12 inch.',          3200,'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&q=80','popular',0],
        [2,'Loaded Cheese Fries', 'Crispy fries loaded with cheese sauce, beef mince & pickled chilli.',         1500,'https://images.unsplash.com/photo-1555992336-03a23c7b20ee?w=600&q=80',null,0],
        [2,'Club Sandwich',       'Triple-decker with grilled chicken, egg, lettuce & tomato.',                  1800,'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?w=600&q=80',null,0],
        [2,'Zinger Wrap',         'Spicy crispy chicken wrapped in a toasted tortilla with coleslaw.',           1600,'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=600&q=80','spicy',0],
        [2,'Family Meal Deal',    '4 burgers + large fries + 4 drinks — best value for families!',               5000,'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&q=80','value',1],
        [2,'Shawarma Roll',       'Juicy grilled chicken shawarma with garlic sauce in a soft roll.',             1500,'https://images.unsplash.com/photo-1609167830220-7164aa360951?w=600&q=80','popular',0],
        // CHINESE (cat 3)
        [3,'Chicken Manchurian',  'Crispy chicken tossed in a tangy, garlicky Manchurian sauce.',                2200,'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=600&q=80','popular',1],
        [3,'Beef Chilli Dry',     'Wok-tossed beef strips with bell peppers, soy & chilli.',                    2800,'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600&q=80','spicy',0],
        [3,'Chicken Fried Rice',  'Wok-fried basmati with egg, vegetables & soy sauce.',                        1800,'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600&q=80',null,0],
        [3,'Hakka Noodles',       'Stir-fried noodles with shredded chicken & crunchy vegetables.',              1800,'https://images.unsplash.com/photo-1569050467447-ce54b3bbc37d?w=600&q=80',null,0],
        [3,'Hot & Sour Soup',     'Classic tangy-spicy broth with tofu, mushrooms & egg ribbons.',               1200,'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600&q=80',null,0],
        [3,'Spring Rolls (6 pcs)','Crispy golden rolls stuffed with spiced chicken & vegetables.',               1200,'https://images.unsplash.com/photo-1525755662778-989d0524087e?w=600&q=80',null,0],
        // DRINKS (cat 4)
        [4,'Mango Lassi',         'Chilled blend of mango pulp, yogurt & a hint of cardamom.',                   400,'https://images.unsplash.com/photo-1541614101331-1a5a3a194e92?w=600&q=80',null,0],
        [4,'Fresh Lemonade',      'Freshly squeezed lemon with mint, salt, sugar & chilled water.',              350,'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=600&q=80',null,0],
        [4,'Rooh Afza Sharbat',   'Classic rose sharbat with milk — a Pakistani summer staple.',                 300,'https://images.unsplash.com/photo-1572490122747-3f3c40c1d2e5?w=600&q=80',null,0],
        [4,'Doodh Pati Chai',     'Strong milky tea brewed with cardamom & cinnamon.',                           200,'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=600&q=80',null,0],
        // DESSERTS (cat 5)
        [5,'Gajar Halwa',         'Slow-cooked carrot pudding with milk, sugar & cardamom.',                     600,'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=600&q=80','popular',0],
        [5,'Gulab Jamun (4 pcs)', 'Soft fried milk-dough balls soaked in rose sugar syrup.',                     500,'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=600&q=80',null,0],
        [5,'Brownie Sundae',      'Warm chocolate brownie with vanilla ice cream & fudge sauce.',                 800,'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=600&q=80','new',0],
        [5,'Kheer',               'Rice pudding with saffron, rose water & crushed almonds.',                    450,'https://images.unsplash.com/photo-1517093157656-b9eccef91cb1?w=600&q=80',null,0],
    ];

    $si = $pdo->prepare("INSERT INTO menu_items (category_id,name,description,price,image_url,badge,is_featured) VALUES(?,?,?,?,?,?,?)");
    foreach ($menuItems as $m) {
        $si->execute($m);
    }
    $steps[] = "✅ Menu items table created & seeded (" . count($menuItems) . " items)";

    // ── GALLERY ───────────────────────────────────────────────
    $pdo->exec("CREATE TABLE gallery (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        title      VARCHAR(100),
        image_url  VARCHAR(500) NOT NULL,
        span_class VARCHAR(20) DEFAULT NULL,
        sort_order INT DEFAULT 0,
        active     TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB");

    $pdo->exec("INSERT INTO gallery (title,image_url,span_class,sort_order) VALUES
        ('Mutton Biryani',    'https://images.unsplash.com/photo-1589302168068-964664d93dc0?w=600&q=80','tall',1),
        ('Special Burger',    'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?w=500&q=80',NULL,2),
        ('Hakka Noodles',     'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=500&q=80',NULL,3),
        ('Lahori Karahi',     'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=800&q=80','wide',4),
        ('BBQ Pizza',         'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=500&q=80',NULL,5),
        ('Hot & Sour Soup',   'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=500&q=80',NULL,6),
        ('Desserts',          'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=600&q=80','tall',7),
        ('Seekh Kebab',       'https://images.unsplash.com/photo-1574894709920-11b28e7367e3?w=500&q=80',NULL,8)
    ");
    $steps[] = "✅ Gallery table created & seeded";

    // ── RESERVATIONS ──────────────────────────────────────────
    $pdo->exec("CREATE TABLE reservations (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        full_name        VARCHAR(150) NOT NULL,
        phone            VARCHAR(30)  NOT NULL,
        email            VARCHAR(150),
        reservation_date DATE NOT NULL,
        reservation_time TIME NOT NULL,
        guests           VARCHAR(30)  NOT NULL,
        cuisine_pref     VARCHAR(50)  DEFAULT 'All / Mixed',
        special_requests TEXT,
        status           ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
        created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $steps[] = "✅ Reservations table created";

    // ── ORDERS ────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE orders (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        order_ref       VARCHAR(20)  NOT NULL UNIQUE,
        customer_name   VARCHAR(150) NOT NULL,
        customer_phone  VARCHAR(30)  NOT NULL,
        customer_email  VARCHAR(150),
        delivery_address TEXT,
        order_type      ENUM('delivery','takeaway','dine-in') DEFAULT 'delivery',
        subtotal        DECIMAL(10,2) NOT NULL,
        delivery_fee    DECIMAL(10,2) DEFAULT 0,
        total           DECIMAL(10,2) NOT NULL,
        status          ENUM('new','confirmed','preparing','ready','delivered','cancelled') DEFAULT 'new',
        notes           TEXT,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE order_items (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        order_id     INT NOT NULL,
        menu_item_id INT,
        item_name    VARCHAR(150) NOT NULL,
        item_price   DECIMAL(10,2) NOT NULL,
        quantity     INT NOT NULL DEFAULT 1,
        subtotal     DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $steps[] = "✅ Orders & order_items tables created";

    // ── REVIEWS ───────────────────────────────────────────────
    $pdo->exec("CREATE TABLE reviews (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(100) NOT NULL,
        location    VARCHAR(100),
        rating      TINYINT NOT NULL DEFAULT 5,
        review_text TEXT NOT NULL,
        is_approved TINYINT(1) DEFAULT 0,
        is_featured TINYINT(1) DEFAULT 0,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("INSERT INTO reviews (name,location,rating,review_text,is_approved,is_featured) VALUES
        ('Ahmed Raza',  'Multan',   5,'Best karahi in Multan, hands down. The flavours are incredible and the portion sizes are generous. We come here every week!',1,1),
        ('Sara Khan',   'Gulgasht', 5,'Zaiqa has everything — desi, fast food, Chinese. Amazing food quality and great service. The biryani and burgers are must-tries!',1,1),
        ('Usman Ali',   'Multan',   5,'We had a family dinner here and everyone loved it. The Manchurian and seekh kebabs were the highlight. Highly recommended!',1,1),
        ('Fatima Malik','Multan',   5,'The Chicken Handi and Gajar Halwa are to die for. Best family restaurant in Gulgasht — always fresh and hot!',1,0)
    ");
    $steps[] = "✅ Reviews table created & seeded";

    // ── ADMIN USERS ───────────────────────────────────────────
    $pdo->exec("CREATE TABLE admin_users (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        username      VARCHAR(80)  NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name     VARCHAR(150),
        role          ENUM('superadmin','manager','staff') DEFAULT 'staff',
        last_login    TIMESTAMP NULL,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Generate hash dynamically — works on every server
    $hash = password_hash('zaiqa2024', PASSWORD_BCRYPT);
    $st   = $pdo->prepare("INSERT INTO admin_users (username,password_hash,full_name,role) VALUES(?,?,?,?)");
    $st->execute(['admin', $hash, 'Restaurant Admin', 'superadmin']);
    $steps[] = "✅ Admin user created — <strong>username: admin / password: zaiqa2024</strong>";

    $steps[] = "<hr><h3 style='color:#27ae60'>🎉 Installation Complete!</h3>";
    $success = true;

} catch (PDOException $e) {
    $errors[] = "❌ Database error: " . $e->getMessage();
    $success  = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Zaiqa — Database Installer</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet"/>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0ebe0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem}
.card{background:#fff;border-radius:16px;padding:2.5rem;max-width:640px;width:100%;box-shadow:0 10px 40px rgba(0,0,0,.1)}
.logo{font-size:2rem;font-weight:700;color:#111;margin-bottom:.3rem}
.logo span{color:#f0c040}
h2{font-size:1.1rem;color:#555;margin-bottom:2rem;font-weight:400}
.step{padding:.55rem .9rem;border-radius:8px;font-size:.88rem;margin-bottom:.5rem;border-left:3px solid #27ae60;background:#f0faf5;color:#1a5276}
.step hr{border:none;border-top:1px solid #e0d8cc;margin:.5rem 0}
.step h3{font-size:1rem}
.error{border-left-color:#e74c3c;background:#fde8d8;color:#a04000}
.links{display:flex;gap:1rem;margin-top:2rem;flex-wrap:wrap}
.btn{padding:.75rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:.9rem;display:inline-block;transition:opacity .2s}
.btn:hover{opacity:.85}
.btn-red{background:#c0392b;color:#fff}
.btn-dark{background:#222;color:#fff}
.warn{background:#fef9e7;border:1px solid #f0c040;border-radius:8px;padding:1rem;margin-top:1.5rem;font-size:.82rem;color:#7a5c00}
.warn strong{display:block;margin-bottom:4px}
.cred-box{background:#1c1c1c;color:#f0c040;border-radius:8px;padding:1rem 1.2rem;margin-top:1.2rem;font-family:monospace;font-size:.88rem;line-height:1.8}
</style>
</head>
<body>
<div class="card">
  <div class="logo">Zaiqa<span>.</span></div>
  <h2>Database Installer — Step-by-step log</h2>

  <?php foreach($steps as $step): ?>
  <div class="step"><?= $step ?></div>
  <?php endforeach; ?>

  <?php foreach($errors as $err): ?>
  <div class="step error"><?= $err ?></div>
  <?php endforeach; ?>

  <?php if(!empty($success)): ?>
  <div class="cred-box">
    🔐 Admin Login Details<br>
    URL &nbsp;&nbsp;&nbsp;: http://localhost/zaiqa/admin/login.php<br>
    Username: admin<br>
    Password: zaiqa2024
  </div>

  <div class="links">
    <a href="index.php" class="btn btn-red">🌐 Open Website</a>
    <a href="admin/login.php" class="btn btn-dark">🔑 Admin Panel</a>
  </div>

  <div class="warn">
    <strong>⚠️ IMPORTANT — Delete this file after setup!</strong>
    For security, delete <code>install.php</code> from your server once the site is working.
    Anyone who visits this URL can wipe and reset your database.
  </div>

  <?php else: ?>
  <div class="warn">
    <strong>❌ Installation failed — troubleshooting tips:</strong>
    1. Open <code>install.php</code> and check DB_USER / DB_PASS at the top match your MySQL credentials.<br>
    2. In XAMPP the default is: user = <strong>root</strong>, password = <strong>(empty)</strong>.<br>
    3. Make sure MySQL is running in XAMPP Control Panel.
  </div>
  <?php endif; ?>
</div>
</body>
</html>
