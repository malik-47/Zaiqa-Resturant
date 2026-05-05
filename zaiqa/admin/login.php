<?php
// admin/login.php
require_once __DIR__ . '/../config.php';
start_session();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $st = db()->prepare("SELECT * FROM admin_users WHERE username = ?");
        $st->execute([$username]);
        $admin = $st->fetch();

        // NOTE: For a new install, create the hash with:
        // echo password_hash('zaiqa2024', PASSWORD_BCRYPT);
        // and update the admin_users table.
        // Default credentials: admin / zaiqa2024
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            db()->prepare("UPDATE admin_users SET last_login=NOW() WHERE id=?")->execute([$admin['id']]);
            header('Location: index.php');
            exit;
        }
        $error = 'Invalid username or password.';
    } else {
        $error = 'Please enter both username and password.';
    }
}

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin Login — Zaiqa Restaurant</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem
        }

        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .4)
        }

        .login-logo {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            text-align: center;
            margin-bottom: .3rem
        }

        .login-logo span {
            color: #f0c040
        }

        .login-sub {
            text-align: center;
            color: #888;
            font-size: .85rem;
            margin-bottom: 2rem
        }

        .fg {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 1.1rem
        }

        .fg label {
            font-size: .78rem;
            font-weight: 600;
            color: #555;
            letter-spacing: .3px
        }

        .fg input {
            border: 1.5px solid #e0d8cc;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .9rem;
            font-family: inherit;
            outline: none;
            transition: border .2s
        }

        .fg input:focus {
            border-color: #c0392b
        }

        .login-btn {
            width: 100%;
            background: #c0392b;
            color: #fff;
            border: none;
            padding: .95rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background .2s;
            margin-top: .5rem
        }

        .login-btn:hover {
            background: #e74c3c
        }

        .error-box {
            background: #fde8d8;
            border: 1px solid #e59866;
            border-radius: 8px;
            padding: .8rem 1rem;
            color: #a04000;
            font-size: .85rem;
            margin-bottom: 1rem;
            text-align: center
        }

        .back-link {
            text-align: center;
            margin-top: 1.2rem;
            font-size: .82rem;
            color: #888
        }

        .back-link a {
            color: #c0392b;
            text-decoration: none
        }

        .hint {
            background: #fffbeb;
            border: 1px solid #f0c040;
            border-radius: 8px;
            padding: .7rem 1rem;
            font-size: .78rem;
            color: #7a5c00;
            margin-bottom: 1rem;
            text-align: center
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-logo">Zaiqa<span>.</span></div>
        <div class="login-sub">Restaurant Admin Panel</div>

        <div class="hint">Default login: <strong>admin</strong> / <strong>zaiqa2024</strong><br>Change this after first
            login!</div>

        <?php if ($error): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="fg"><label>Username</label><input type="text" name="username" placeholder="admin" required
                    autocomplete="username" /></div>
            <div class="fg"><label>Password</label><input type="password" name="password" placeholder="••••••••"
                    required autocomplete="current-password" /></div>
            <button type="submit" class="login-btn">Login to Dashboard →</button>
        </form>
        <div class="back-link"><a href="../index.php">← Back to Website</a></div>
    </div>
</body>

</html>