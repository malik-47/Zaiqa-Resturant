<?php
// ============================================================
// index.php — Zaiqa Restaurant Main Website
// ============================================================
require_once __DIR__ . '/config.php';

$pdo = db();

// ── Fetch settings ───────────────────────────────────────────
$stSettings = $pdo->query("SELECT `key`, `value` FROM settings");
$settings = [];
foreach ($stSettings as $row) $settings[$row['key']] = $row['value'];

// ── Fetch categories ─────────────────────────────────────────
$categories = $pdo->query("SELECT * FROM categories WHERE active=1 ORDER BY sort_order")->fetchAll();

// ── Fetch featured menu items ─────────────────────────────────
$featured = $pdo->query(
    "SELECT m.*, c.slug AS cat_slug, c.name AS cat_name
     FROM menu_items m JOIN categories c ON m.category_id=c.id
     WHERE m.is_featured=1 AND m.is_available=1
     ORDER BY m.sort_order LIMIT 6"
)->fetchAll();

// ── Fetch gallery ─────────────────────────────────────────────
$gallery = $pdo->query("SELECT * FROM gallery WHERE active=1 ORDER BY sort_order")->fetchAll();

// ── Fetch approved reviews ────────────────────────────────────
$reviews = $pdo->query(
    "SELECT * FROM reviews WHERE is_approved=1 ORDER BY is_featured DESC, created_at DESC LIMIT 6"
)->fetchAll();

// ── Stats ─────────────────────────────────────────────────────
$totalItems   = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_available=1")->fetchColumn();
$totalOrders  = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$avgRating    = $pdo->query("SELECT ROUND(AVG(rating),1) FROM reviews WHERE is_approved=1")->fetchColumn() ?: '4.9';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<meta name="description" content="<?php echo $settings['tagline']; ?>. Located at <?php echo $settings['address']; ?>"/><title><?= htmlspecialchars($settings['restaurant_name']) ?> — <?= htmlspecialchars($settings['tagline']) ?></title>
<link rel="stylesheet" href="assets/css/style.css"/>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<!-- ===== CART SIDEBAR ===== -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
<aside class="cart-sidebar" id="cartSidebar">
  <div class="cart-header">
    <h3>🛒 Your Order</h3>
    <button class="cart-close" onclick="closeCart()">✕</button>
  </div>
  <div class="cart-items" id="cartItems"></div>
  <div class="cart-footer" id="cartFooter" style="display:none">
    <div class="cart-summary">
      <div class="cart-row"><span>Subtotal</span><span id="cartSubtotal">Rs 0</span></div>
      <div class="cart-row"><span>Delivery</span><span>Rs <?= DELIVERY_FEE ?></span></div>
      <div class="cart-row total"><span>Total</span><span id="cartTotal">Rs 0</span></div>
    </div>
    <button class="checkout-btn" onclick="openCheckout()">Proceed to Checkout →</button>
  </div>
  <div class="cart-empty" id="cartEmpty">
    <div style="font-size:3rem">🍽️</div>
    <p>Your cart is empty.<br>Add some delicious items!</p>
  </div>
</aside>

<!-- ===== CHECKOUT MODAL ===== -->
<div class="modal-overlay" id="checkoutModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Complete Your Order</h3>
      <button onclick="closeModal('checkoutModal')">✕</button>
    </div>
    <form id="checkoutForm">
      <div class="form-row">
        <div class="fg"><label>Full Name *</label><input type="text" name="name" placeholder="Ahmed Khan" required/></div>
        <div class="fg"><label>Phone *</label><input type="tel" name="phone" placeholder="0303 6417714" required/></div>
      </div>
      <div class="fg"><label>Email</label><input type="email" name="email" placeholder="you@email.com"/></div>
      <div class="fg">
        <label>Order Type *</label>
        <select name="order_type" onchange="toggleDelivery(this.value)">
          <option value="delivery">Delivery</option>
          <option value="takeaway">Takeaway</option>
          <option value="dine-in">Dine-in</option>
        </select>
      </div>
      <div class="fg" id="addressField">
        <label>Delivery Address *</label>
        <textarea name="address" placeholder="Street, Area, Multan" rows="2"></textarea>
      </div>
      <div class="fg"><label>Special Notes</label><textarea name="notes" placeholder="Spice level, allergies..." rows="2"></textarea></div>
      <div class="order-summary-mini" id="orderSummaryMini"></div>
      <button type="submit" class="sub-btn" id="placeOrderBtn">Place Order →</button>
    </form>
  </div>
</div>

<!-- ===== ORDER SUCCESS MODAL ===== -->
<div class="modal-overlay" id="successModal">
  <div class="modal-box success-box">
    <div style="font-size:4rem;margin-bottom:1rem">✅</div>
    <h3>Order Placed!</h3>
    <p id="successMsg"></p>
    <p style="margin-top:.5rem;color:#666;font-size:.85rem">We'll call you on <strong><?= htmlspecialchars($settings['phone']) ?></strong> to confirm.</p>
    <button onclick="closeModal('successModal');resetCart()" class="sub-btn" style="margin-top:1.5rem">Continue Ordering</button>
  </div>
</div>

<!-- ===== REVIEW MODAL ===== -->
<div class="modal-overlay" id="reviewModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Write a Review</h3>
      <button onclick="closeModal('reviewModal')">✕</button>
    </div>
    <form id="reviewForm">
      <div class="form-row">
        <div class="fg"><label>Your Name *</label><input type="text" name="name" placeholder="Ahmed Khan" required/></div>
        <div class="fg"><label>Location</label><input type="text" name="location" placeholder="Multan"/></div>
      </div>
      <div class="fg">
        <label>Rating *</label>
        <div class="star-picker" id="starPicker">
          <?php for($i=1;$i<=5;$i++): ?>
          <span class="star" data-val="<?=$i?>" onclick="setRating(<?=$i?>)">★</span>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="ratingInput" value="5"/>
      </div>
      <div class="fg"><label>Your Review *</label><textarea name="review" placeholder="Tell us about your experience..." rows="3" required></textarea></div>
      <button type="submit" class="sub-btn">Submit Review</button>
    </form>
  </div>
</div>

<!-- ===== NAVBAR ===== -->
<nav id="navbar">
  <div class="nav-inner">
    <div class="logo">Zaiqa<span>.</span></div>
    <ul class="nav-links">
      <li><a href="#home">Home</a></li>
      <li><a href="#menu">Menu</a></li>
      <li><a href="#about">About</a></li>
      <li><a href="#gallery">Gallery</a></li>
      <li><a href="#reservation">Reserve</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>
    <div class="nav-right">
      <button class="cart-btn" onclick="openCart()">
        🛒 Cart <span class="cart-count" id="navCartCount">0</span>
      </button>
      <button class="nav-cta" onclick="scrollToSection('reservation')">Book a Table</button>
      <button class="hamburger" id="ham" onclick="toggleMenu()">☰</button>
    </div>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a href="#home"        onclick="toggleMenu()">Home</a>
    <a href="#menu"        onclick="toggleMenu()">Menu</a>
    <a href="#about"       onclick="toggleMenu()">About</a>
    <a href="#gallery"     onclick="toggleMenu()">Gallery</a>
    <a href="#reservation" onclick="toggleMenu()">Reserve</a>
    <a href="#contact"     onclick="toggleMenu()">Contact</a>
  </div>
</nav>

<!-- ===== HERO ===== -->
<section class="hero" id="home">
  <div class="hero-video-wrap">
    <video autoplay muted loop playsinline>
      <source src="<?= htmlspecialchars($settings['']) ?>" type="video/mp4"/>
    </video>
    <div class="hero-overlay"></div>
  </div>
  <div class="hero-content">
    <div class="hero-badge">🍽️ Multan's Favourite Restaurant — Gulgasht Colony</div>
    <h1>Three Cuisines.<br><span class="gold-text"><?= htmlspecialchars($settings['tagline']) ?></span></h1>
    <p>Authentic Desi curries, crispy Fast Food, and flavourful Chinese — all under one roof. Freshly made, boldly spiced, served with love since 2009.</p>
    <div class="hero-btns">
      <a href="#menu" class="btn-red">Explore Menu</a>
      <a href="#reservation" class="btn-ghost">Reserve a Table</a>
    </div>
    <div class="hero-stats">
      <div class="stat"><div class="stat-val">3</div><div class="stat-lbl">Cuisines</div></div>
      <div class="stat-divider"></div>
      <div class="stat"><div class="stat-val"><?= $totalItems ?>+</div><div class="stat-lbl">Menu Items</div></div>
      <div class="stat-divider"></div>
      <div class="stat"><div class="stat-val"><?= $avgRating ?>★</div><div class="stat-lbl">Rating</div></div>
      <div class="stat-divider"></div>
      <div class="stat"><div class="stat-val">15+</div><div class="stat-lbl">Years Serving</div></div>
    </div>
  </div>
  <a href="#menu" class="scroll-down">↓ Scroll</a>
</section>

<!-- ===== CUISINE STRIP ===== -->
<section class="cuisines-strip">
  <?php foreach($categories as $cat): if($cat['slug'] === 'drinks' || $cat['slug'] === 'dessert') continue; ?>
  <div class="cuisine-card" onclick="filterMenu('<?= $cat['slug'] ?>')">
    <?php
      $catImgs = [
        'desi'    => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=600&q=80',
        'fast'    => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&q=80',
        'chinese' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=600&q=80',
      ];
      $catDesc = [
        'desi'    => 'Karahi · Nihari · Biryani · Kebabs',
        'fast'    => 'Burgers · Pizza · Wraps · Fries',
        'chinese' => 'Manchurian · Noodles · Fried Rice · Soup',
      ];
    ?>
    <img src="<?= $catImgs[$cat['slug']] ?? '' ?>" alt="<?= htmlspecialchars($cat['name']) ?>" loading="lazy"/>
    <div class="cuisine-overlay">
      <span class="cuisine-emoji"><?= $cat['emoji'] ?></span>
      <h3><?= htmlspecialchars($cat['name']) ?></h3>
      <p><?= $catDesc[$cat['slug']] ?? '' ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</section>

<!-- ===== MENU ===== -->
<section class="menu-section" id="menu">
  <div class="container">
    <div class="section-label">Our Menu</div>
    <h2 class="section-h">Crafted Fresh Every Day</h2>
    <div class="gold-bar"></div>

    <!-- Search Bar -->
    <div class="menu-search-wrap">
      <input type="text" id="menuSearch" placeholder="🔍  Search dishes..." oninput="searchMenu(this.value)"/>
    </div>

    <div class="cat-tabs">
      <button class="tab active" onclick="filterMenu('all',this)">🍽️ All Items</button>
      <?php foreach($categories as $cat): ?>
      <button class="tab" onclick="filterMenu('<?= $cat['slug'] ?>',this)"><?= $cat['emoji'] ?> <?= htmlspecialchars($cat['name']) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="menu-grid" id="menuGrid">
      <div class="loading-spinner">Loading menu…</div>
    </div>
  </div>
</section>

<!-- ===== ABOUT ===== -->
<section class="about-section" id="about">
  <div class="about-bg-img">
    <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1600&q=80" alt="Restaurant interior" loading="lazy"/>
    <div class="about-bg-overlay"></div>
  </div>
  <div class="container about-grid">
    <div class="about-text">
      <div class="section-label light">Our Story</div>
      <h2 class="section-h light">More Than a Meal —<br>It's a Memory</h2>
      <div class="gold-bar"></div>
      <p>At Zaiqa, we believe food brings people together. Since 2009, we've been serving Multan with bold desi flavours, crowd-pleasing fast food, and rich Chinese dishes — all made fresh daily with handpicked ingredients.</p>
      <p>Located in the heart of Gulgasht Colony, we're a family restaurant with a soul — where every plate tells a story of tradition, spice, and care.</p>
      <div class="features">
        <div class="feat"><div class="feat-icon">✓</div><div><strong>100% Halal Certified</strong><span>Every ingredient is certified halal</span></div></div>
        <div class="feat"><div class="feat-icon">✓</div><div><strong>Freshly Cooked Daily</strong><span>No frozen shortcuts — made fresh every morning</span></div></div>
        <div class="feat"><div class="feat-icon">✓</div><div><strong>Dine-in &amp; Takeaway</strong><span>Enjoy here or take your favourites home</span></div></div>
        <div class="feat"><div class="feat-icon">✓</div><div><strong>Online Ordering</strong><span>Order online with home delivery available</span></div></div>
      </div>
    </div>
    <div class="about-stats-grid">
      <div class="astat"><div class="astat-val">15+</div><div class="astat-lbl">Years Open</div></div>
      <div class="astat"><div class="astat-val"><?= $totalItems ?>+</div><div class="astat-lbl">Menu Items</div></div>
      <div class="astat"><div class="astat-val">3</div><div class="astat-lbl">Cuisines</div></div>
      <div class="astat"><div class="astat-val">500+</div><div class="astat-lbl">Guests/Day</div></div>
    </div>
  </div>
</section>

<!-- ===== GALLERY ===== -->
<section class="gallery-section" id="gallery">
  <div class="container">
    <div class="section-label">Food Gallery</div>
    <h2 class="section-h">Fresh From Our Kitchen</h2>
    <div class="gold-bar"></div>
  </div>
  <div class="gallery-grid">
    <?php foreach($gallery as $g): ?>
    <div class="gitem <?= htmlspecialchars($g['span_class'] ?? '') ?>">
      <img src="<?= htmlspecialchars($g['image_url']) ?>" alt="<?= htmlspecialchars($g['title'] ?? '') ?>" loading="lazy"/>
      <?php if($g['title']): ?><div class="g-label"><?= htmlspecialchars($g['title']) ?></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<section class="testimonials" id="reviews">
  <div class="container">
    <div class="section-label">What Guests Say</div>
    <h2 class="section-h">Loved by Multan</h2>
    <div class="gold-bar"></div>
    <div class="testi-grid">
      <?php foreach($reviews as $i => $r): if($i >= 3) break; ?>
      <div class="tcard <?= $r['is_featured'] ? 'featured' : '' ?>">
        <div class="stars"><?= str_repeat('★', (int)$r['rating']) ?></div>
        <p>"<?= htmlspecialchars($r['review_text']) ?>"</p>
        <div class="tname">— <?= htmlspecialchars($r['name']) ?><?= $r['location'] ? ', ' . htmlspecialchars($r['location']) : '' ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:2rem">
      <button class="btn-red" onclick="openModal('reviewModal')">Write a Review</button>
    </div>
  </div>
</section>

<!-- ===== RESERVATION ===== -->
<section class="res-section" id="reservation">
  <div class="res-bg-img">
    <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1600&q=80" alt="Restaurant" loading="lazy"/>
    <div class="res-bg-overlay"></div>
  </div>
  <div class="container res-grid">
    <div class="res-left">
      <div class="section-label light">Book a Table</div>
      <h2 class="section-h light">Reserve Your Seat<br>at Zaiqa</h2>
      <div class="gold-bar"></div>
      <div class="res-info">
        <div class="ri"><div class="ri-icon">🕐</div><div><h4>Opening Hours</h4><p><?= htmlspecialchars($settings['hours_weekday']) ?><br><?= htmlspecialchars($settings['hours_weekend']) ?></p></div></div>
        <div class="ri"><div class="ri-icon">📞</div><div><h4>Call Us</h4><p><?= htmlspecialchars($settings['phone']) ?></p></div></div>
        <div class="ri"><div class="ri-icon">📍</div><div><h4>Location</h4><p><?= htmlspecialchars($settings['address']) ?></p></div></div>
        <div class="ri"><div class="ri-icon">💰</div><div><h4>Price Range</h4><p>Rs 500 – Rs 5,000 per head</p></div></div>
      </div>
    </div>
    <div class="form-card">
      <h3>Make a Reservation</h3>
      <form id="reservationForm">
        <div class="frow">
          <div class="fg"><label>Full Name *</label><input type="text" name="name" id="fn" placeholder="Ahmed Khan" required/></div>
          <div class="fg"><label>Phone *</label><input type="tel" name="phone" id="fp" placeholder="0303 6417714" required/></div>
        </div>
        <div class="fg"><label>Email</label><input type="email" name="email" placeholder="you@email.com"/></div>
        <div class="frow">
          <div class="fg"><label>Date *</label><input type="date" name="date" id="fd" required/></div>
          <div class="fg"><label>Time *</label><input type="time" name="time" id="ft" required/></div>
        </div>
        <div class="frow">
          <div class="fg"><label>Guests *</label>
            <select name="guests" id="fg2">
              <option>1 Person</option><option>2 People</option>
              <option selected>3–4 People</option><option>5–6 People</option>
              <option>7–10 People</option><option>10+ People</option>
            </select>
          </div>
          <div class="fg"><label>Cuisine Preference</label>
            <select name="cuisine">
              <option>All / Mixed</option><option>Desi Only</option>
              <option>Fast Food</option><option>Chinese</option>
            </select>
          </div>
        </div>
        <div class="fg full"><label>Special Requests</label>
          <textarea name="requests" id="fnote" placeholder="Dietary needs, occasion, seating preference..." rows="3"></textarea>
        </div>
        <button type="submit" class="sub-btn" id="reserveBtn">Confirm Reservation</button>
        <div class="ok-msg" id="okMsg"></div>
        <div class="err-msg" id="errMsg"></div>
      </form>
    </div>
  </div>
</section>

<!-- ===== CONTACT ===== -->
<section class="contact-section" id="contact">
  <div class="container">
    <div class="section-label">Find Us</div>
    <h2 class="section-h">We're in Gulgasht, Multan</h2>
    <div class="gold-bar"></div>
    <div class="contact-grid">
      <div class="contact-cards">
        <div class="cc"><div class="cc-icon">📍</div><div><h4>Address</h4><p><?= htmlspecialchars($settings['address']) ?></p></div></div>
        <div class="cc"><div class="cc-icon">📞</div><div><h4>Phone</h4><p><?= htmlspecialchars($settings['phone']) ?></p></div></div>
        <div class="cc"><div class="cc-icon">🕐</div><div><h4>Hours</h4><p><?= htmlspecialchars($settings['hours_weekday']) ?><br><?= htmlspecialchars($settings['hours_weekend']) ?></p></div></div>
        <div class="cc"><div class="cc-icon">💰</div><div><h4>Price Range</h4><p>Rs 500 – Rs 5,000 per head</p></div></div>
      </div>
      <div class="map-wrap">
        <iframe src="<?= htmlspecialchars($settings['google_maps_embed']) ?>"
          width="100%" height="360" style="border:0;border-radius:14px" allowfullscreen loading="lazy"></iframe>
      </div>
    </div>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer>
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="logo" style="font-size:2rem">Zaiqa<span>.</span></div>
      <p><?= htmlspecialchars($settings['address']) ?></p>
      <p style="margin-top:.5rem">📞 <?= htmlspecialchars($settings['phone']) ?></p>
    </div>
    <div class="footer-links">
      <h4>Quick Links</h4>
      <a href="#home">Home</a><a href="#menu">Menu</a>
      <a href="#about">About Us</a><a href="#gallery">Gallery</a>
      <a href="#reservation">Reservation</a><a href="#contact">Contact</a>
    </div>
    <div class="footer-links">
      <h4>Cuisines</h4>
      <?php foreach($categories as $cat): ?>
      <a href="#menu" onclick="filterMenu('<?= $cat['slug'] ?>')"><?= $cat['emoji'] ?> <?= htmlspecialchars($cat['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="footer-links">
      <h4>Hours</h4>
      <p><?= htmlspecialchars($settings['hours_weekday']) ?></p>
      <p style="margin-top:.5rem"><?= htmlspecialchars($settings['hours_weekend']) ?></p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© <?= date('Y') ?> <?= htmlspecialchars($settings['restaurant_name']) ?> · Gulgasht Colony, Multan · All Rights Reserved · Made with ❤️ in Pakistan
      | <a href="admin/login.php" style="color:#555">Admin</a>
    </p>
  </div>
</footer>

<!-- Back to top -->
<button class="back-top" id="backTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<script src="assets/js/app.js"></script>
</body>
</html>
