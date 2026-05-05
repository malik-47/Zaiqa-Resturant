# 🍽️ ZAIQA RESTAURANT — Full Stack PHP + MySQL Website
## Complete Setup Guide

---

## 📁 Project Structure

```
zaiqa/
├── index.php              ← Main public website
├── config.php             ← DB config & helpers
├── database.sql           ← Full schema + seed data
├── .htaccess              ← Apache security & caching rules
│
├── api/
│   └── index.php          ← REST API (menu, orders, reservations, reviews)
│
├── admin/
│   ├── login.php          ← Admin login
│   ├── index.php          ← Dashboard (stats, recent orders)
│   ├── orders.php         ← Order management + status updates
│   ├── reservations.php   ← Reservation management
│   ├── menu.php           ← Menu CRUD (add/edit/delete/toggle)
│   ├── categories.php     ← Category management
│   ├── gallery.php        ← Gallery management
│   ├── reviews.php        ← Review approval & moderation
│   ├── settings.php       ← Site-wide settings + password change
│   ├── logout.php         ← Logout
│   └── _sidebar.php       ← Sidebar navigation include
│
└── assets/
    ├── css/
    │   ├── style.css      ← Public website styles
    │   └── admin.css      ← Admin panel styles
    └── js/
        └── app.js         ← Frontend: cart, menu filter, checkout, reviews
```

---

## ⚡ Quick Setup (XAMPP / WAMP / Laragon)

### Step 1 — Copy Files
Place the entire `zaiqa/` folder inside your web server root:
- **XAMPP**: `C:/xampp/htdocs/zaiqa/`
- **WAMP**: `C:/wamp64/www/zaiqa/`
- **Laragon**: `C:/laragon/www/zaiqa/`
- **Linux (Apache)**: `/var/www/html/zaiqa/`

### Step 2 — Create the Database
1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **"New"** → create database named `zaiqa_restaurant`
3. Select the database → click **"Import"**
4. Choose `database.sql` → click **"Go"**

Or via terminal:
```bash
mysql -u root -p < database.sql
```

### Step 3 — Configure DB Connection
Edit `config.php` and update these 4 lines:
```php
define('DB_HOST', 'localhost');   // usually localhost
define('DB_USER', 'root');        // your MySQL username
define('DB_PASS', '');            // your MySQL password
define('DB_NAME', 'zaiqa_restaurant');
```

Also update SITE_URL:
```php
define('SITE_URL', 'http://localhost/zaiqa');
```

### Step 4 — Fix Admin Password
The default admin password hash in `database.sql` may not work out of the box (bcrypt hashes are environment-specific).

**Option A** — Run this PHP snippet to get the correct hash:
```php
<?php echo password_hash('zaiqa2024', PASSWORD_BCRYPT); ?>
```
Then update in phpMyAdmin:
```sql
UPDATE admin_users SET password_hash = 'your_new_hash' WHERE username = 'admin';
```

**Option B** — Temporarily change login.php to bypass bcrypt for first login, then use Settings to change password.

### Step 5 — Open the Website
- **Public site**: `http://localhost/zaiqa/`
- **Admin panel**: `http://localhost/zaiqa/admin/login.php`
  - Username: `admin`
  - Password: `zaiqa2024`

---

## 🌐 Live Hosting (cPanel / DirectAdmin)

1. Upload all files to `public_html/zaiqa/` via FTP or File Manager
2. Create a MySQL database in your hosting control panel
3. Import `database.sql` via phpMyAdmin
4. Update `config.php` with your hosting DB credentials
5. Change `SITE_URL` to your actual domain: `https://yourdomain.com/zaiqa`
6. Set correct file permissions: `755` for folders, `644` for files

---

## 🔌 API Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| GET | `api/?action=menu` | All menu items |
| GET | `api/?action=menu&category=desi` | Filter by category |
| GET | `api/?action=menu&search=karahi` | Search menu |
| GET | `api/?action=categories` | All categories |
| POST | `api/?action=order` | Place a new order |
| POST | `api/?action=reserve` | Submit reservation |
| POST | `api/?action=review` | Submit a review |
| GET | `api/?action=track&ref=ZQ-XXXXXX` | Track order |

---

## 🛠️ Admin Panel Features

| Feature | Description |
|---------|-------------|
| Dashboard | Live stats: orders, revenue, reservations |
| Orders | View all orders, update status (new→confirmed→preparing→ready→delivered) |
| Reservations | View/manage bookings, filter by date/status |
| Menu Items | Full CRUD: add, edit, delete, toggle availability, mark featured |
| Categories | Add/edit/hide menu categories |
| Gallery | Add/remove photos, toggle visibility, set grid span |
| Reviews | Approve/reject/feature guest reviews |
| Settings | Edit restaurant info, hours, video, delivery settings |
| Password | Change admin password from Settings page |

---

## 🔒 Security Notes

1. **Change the default password** immediately after setup
2. The `.htaccess` blocks direct access to `config.php`, `.sql` files, and `_sidebar.php`
3. All user inputs are sanitized via prepared PDO statements (SQL injection protected)
4. `htmlspecialchars()` used throughout (XSS protected)
5. For production, consider adding CSRF tokens to all POST forms

---

## 📞 Support

Restaurant: Zaiqa Restaurant, Gulgasht Colony, Multan
Phone: 0303 6417714
