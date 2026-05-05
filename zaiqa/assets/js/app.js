// ============================================================
// app.js — Zaiqa Restaurant Frontend Logic
// ============================================================

// ── Cart State ───────────────────────────────────────────────
let cart = JSON.parse(localStorage.getItem('zaiqaCart') || '[]');
let currentRating = 5;
let allMenuItems = [];

// ── On Load ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  updateCartUI();
  loadMenu('all');
  setMinDate();
  initSmoothScroll();
  initScrollBehaviors();
  setRating(5);
});

// ── Smooth Scroll ────────────────────────────────────────────
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const id = a.getAttribute('href').slice(1);
      const el = document.getElementById(id);
      if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth' }); }
    });
  });
}
function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth' });
}

// ── Scroll Behaviors ─────────────────────────────────────────
function initScrollBehaviors() {
  const nav   = document.getElementById('navbar');
  const back  = document.getElementById('backTop');
  window.addEventListener('scroll', () => {
    const y = window.scrollY;
    if (nav)  nav.style.boxShadow = y > 60 ? '0 4px 20px rgba(0,0,0,.4)' : 'none';
    if (back) back.classList.toggle('show', y > 400);
  });
}

// ── Navbar Mobile ────────────────────────────────────────────
function toggleMenu() {
  document.getElementById('mobileMenu')?.classList.toggle('open');
}

// ───────────────────────────────────────────────────────────────
// MENU
// ───────────────────────────────────────────────────────────────
async function loadMenu(category) {
  const grid = document.getElementById('menuGrid');
  if (!grid) return;
  grid.innerHTML = '<div class="loading-spinner">Loading menu…</div>';
  try {
    const res  = await fetch(`api/?action=menu&category=${category}`);
    const data = await res.json();
    if (!data.success) throw new Error('Failed');
    allMenuItems = data.items;
    renderMenuItems(data.items);
  } catch {
    grid.innerHTML = '<div class="loading-spinner">Could not load menu. Please refresh.</div>';
  }
}

function renderMenuItems(items) {
  const grid = document.getElementById('menuGrid');
  if (!grid) return;
  if (!items.length) {
    grid.innerHTML = '<div class="loading-spinner">No items found.</div>';
    return;
  }

  const badgeLabels = {
    popular: 'Popular', spicy: '🌶 Spicy', new: '✨ New', veg: '🌿 Veg', value: '💰 Best Value'
  };
  const badgeClass = {
    popular: 'b-popular', spicy: 'b-spicy', new: 'b-new', veg: 'b-veg', value: 'b-value'
  };

  grid.innerHTML = items.map(item => `
    <div class="mcard">
      <div class="mcard-img">
        <img src="${item.image_url || 'assets/images/food-placeholder.jpg'}"
             alt="${escHtml(item.name)}" loading="lazy"
             onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&q=80'"/>
      </div>
      <div class="mcard-body">
        <h4>${escHtml(item.name)}
          ${item.badge ? `<span class="badge ${badgeClass[item.badge] || ''}">${badgeLabels[item.badge] || item.badge}</span>` : ''}
        </h4>
        <p>${escHtml(item.description)}</p>
        <div class="mcard-footer">
          <span class="price">${item.price_display}</span>
          <button class="add-btn" onclick="addToCart(${item.id},'${escAttr(item.name)}',${item.price},'${escAttr(item.image_url)}')">
            + Add
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

function filterMenu(cat, btn) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  if (btn) btn.classList.add('active');
  document.getElementById('menuSearch').value = '';
  loadMenu(cat);
  document.getElementById('menu')?.scrollIntoView({ behavior: 'smooth' });
}

function searchMenu(query) {
  if (!query.trim()) {
    renderMenuItems(allMenuItems);
    return;
  }
  const q = query.toLowerCase();
  const filtered = allMenuItems.filter(i =>
    i.name.toLowerCase().includes(q) || i.description.toLowerCase().includes(q)
  );
  renderMenuItems(filtered);
}

// ───────────────────────────────────────────────────────────────
// CART
// ───────────────────────────────────────────────────────────────
function addToCart(id, name, price, img) {
  const existing = cart.find(i => i.id === id);
  if (existing) {
    existing.qty++;
  } else {
    cart.push({ id, name, price, img, qty: 1 });
  }
  saveCart();
  updateCartUI();
  showToast(`${name} added to cart!`);
}

function removeFromCart(id) {
  cart = cart.filter(i => i.id !== id);
  saveCart();
  updateCartUI();
}

function changeQty(id, delta) {
  const item = cart.find(i => i.id === id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) removeFromCart(id);
  else { saveCart(); updateCartUI(); }
}

function saveCart() {
  localStorage.setItem('zaiqaCart', JSON.stringify(cart));
}

function resetCart() {
  cart = [];
  saveCart();
  updateCartUI();
}

function updateCartUI() {
  const totalQty  = cart.reduce((s, i) => s + i.qty, 0);
  const subtotal  = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const total     = subtotal + (cart.length ? 150 : 0);
  const countEl   = document.getElementById('navCartCount');
  const itemsEl   = document.getElementById('cartItems');
  const footerEl  = document.getElementById('cartFooter');
  const emptyEl   = document.getElementById('cartEmpty');
  const subEl     = document.getElementById('cartSubtotal');
  const totEl     = document.getElementById('cartTotal');

  if (countEl) countEl.textContent = totalQty;

  if (itemsEl) {
    itemsEl.innerHTML = cart.map(item => `
      <div class="cart-item">
        <img class="cart-item-img" src="${escHtml(item.img)}"
             alt="${escHtml(item.name)}"
             onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=100&q=60'"/>
        <div class="cart-item-info">
          <h5>${escHtml(item.name)}</h5>
          <p>Rs ${(item.price * item.qty).toLocaleString()}</p>
        </div>
        <div class="cart-item-controls">
          <button class="qty-btn" onclick="changeQty(${item.id}, -1)">−</button>
          <span class="qty-num">${item.qty}</span>
          <button class="qty-btn" onclick="changeQty(${item.id},  1)">+</button>
        </div>
      </div>
    `).join('');
  }

  const hasItems = cart.length > 0;
  if (footerEl) footerEl.style.display  = hasItems ? 'block' : 'none';
  if (emptyEl)  emptyEl.style.display   = hasItems ? 'none'  : 'flex';
  if (subEl)    subEl.textContent  = 'Rs ' + subtotal.toLocaleString();
  if (totEl)    totEl.textContent  = 'Rs ' + total.toLocaleString();
}

function openCart()  {
  document.getElementById('cartSidebar')?.classList.add('open');
  document.getElementById('cartOverlay')?.classList.add('active');
}
function closeCart() {
  document.getElementById('cartSidebar')?.classList.remove('open');
  document.getElementById('cartOverlay')?.classList.remove('active');
}

// ───────────────────────────────────────────────────────────────
// CHECKOUT / ORDER
// ───────────────────────────────────────────────────────────────
function openCheckout() {
  if (!cart.length) return showToast('Cart is empty!');
  closeCart();
  buildOrderSummary();
  openModal('checkoutModal');
}

function buildOrderSummary() {
  const el = document.getElementById('orderSummaryMini');
  if (!el) return;
  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const total    = subtotal + 150;
  el.innerHTML = `
    <h5>Order Summary</h5>
    ${cart.map(i => `
      <div class="order-mini-item">
        <span>${i.qty}× ${escHtml(i.name)}</span>
        <span>Rs ${(i.price * i.qty).toLocaleString()}</span>
      </div>`).join('')}
    <div class="order-mini-total">
      <span>Total (incl. delivery)</span>
      <span>Rs ${total.toLocaleString()}</span>
    </div>
  `;
}

function toggleDelivery(type) {
  const f = document.getElementById('addressField');
  if (f) f.style.display = type === 'delivery' ? 'flex' : 'none';
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('checkoutForm');
  if (form) form.addEventListener('submit', async e => {
    e.preventDefault();
    const fd   = new FormData(form);
    const btn  = document.getElementById('placeOrderBtn');
    btn.disabled = true;
    btn.textContent = 'Placing Order…';

    const payload = {
      name:       fd.get('name'),
      phone:      fd.get('phone'),
      email:      fd.get('email'),
      order_type: fd.get('order_type'),
      address:    fd.get('address'),
      notes:      fd.get('notes'),
      items: cart.map(i => ({ id: i.id, qty: i.qty }))
    };

    try {
      const res  = await fetch('api/?action=order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      closeModal('checkoutModal');
      if (data.success) {
        document.getElementById('successMsg').textContent = data.message;
        openModal('successModal');
      } else {
        showToast(data.message || 'Order failed. Try again.');
      }
    } catch {
      showToast('Network error. Please try again.');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Place Order →';
    }
  });
});

// ───────────────────────────────────────────────────────────────
// RESERVATION
// ───────────────────────────────────────────────────────────────
function setMinDate() {
  const fd = document.getElementById('fd');
  if (fd) fd.min = new Date().toISOString().split('T')[0];
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('reservationForm');
  if (!form) return;
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const fd  = new FormData(form);
    const btn = document.getElementById('reserveBtn');
    const ok  = document.getElementById('okMsg');
    const err = document.getElementById('errMsg');
    ok.classList.remove('show');
    err.classList.remove('show');
    btn.disabled = true;
    btn.textContent = 'Submitting…';

    const payload = {
      name:     fd.get('name'),
      phone:    fd.get('phone'),
      email:    fd.get('email'),
      date:     fd.get('date'),
      time:     fd.get('time'),
      guests:   fd.get('guests'),
      cuisine:  fd.get('cuisine'),
      requests: fd.get('requests')
    };

    try {
      const res  = await fetch('api/?action=reserve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        ok.textContent = data.message;
        ok.classList.add('show');
        form.reset();
      } else {
        err.textContent = data.message;
        err.classList.add('show');
      }
    } catch {
      err.textContent = 'Network error. Call us directly on 0303 6417714.';
      err.classList.add('show');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Confirm Reservation';
    }
  });
});

// ───────────────────────────────────────────────────────────────
// REVIEW
// ───────────────────────────────────────────────────────────────
function setRating(val) {
  currentRating = val;
  document.getElementById('ratingInput').value = val;
  document.querySelectorAll('.star-picker .star').forEach((s, i) => {
    s.classList.toggle('active', i < val);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('reviewForm');
  if (!form) return;
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(form);
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Submitting…';

    const payload = {
      name:     fd.get('name'),
      location: fd.get('location'),
      rating:   parseInt(fd.get('rating')) || 5,
      review:   fd.get('review')
    };

    try {
      const res  = await fetch('api/?action=review', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      closeModal('reviewModal');
      showToast(data.message || 'Thank you for your review!');
      form.reset();
      setRating(5);
    } catch {
      showToast('Failed to submit. Please try again.');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Submit Review';
    }
  });
});

// ───────────────────────────────────────────────────────────────
// MODAL HELPERS
// ───────────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

// Close modal on backdrop click
document.addEventListener('click', e => {
  ['checkoutModal','successModal','reviewModal'].forEach(id => {
    const el = document.getElementById(id);
    if (el && e.target === el) closeModal(id);
  });
});

// ── Toast ────────────────────────────────────────────────────
function showToast(msg, duration = 2800) {
  let t = document.getElementById('zToast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'zToast';
    t.style.cssText = 'position:fixed;bottom:2rem;left:50%;transform:translateX(-50%) translateY(80px);background:#1c1c1c;color:#fff;padding:.7rem 1.4rem;border-radius:40px;font-size:.85rem;font-weight:500;z-index:9999;transition:transform .3s;pointer-events:none;white-space:nowrap;font-family:"Plus Jakarta Sans",sans-serif';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.transform = 'translateX(-50%) translateY(0)';
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.style.transform = 'translateX(-50%) translateY(80px)'; }, duration);
}

// ── HTML Escape ──────────────────────────────────────────────
function escHtml(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
  return String(s ?? '').replace(/'/g,"\\'").replace(/"/g,'&quot;');
}
