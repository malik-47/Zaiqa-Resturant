<?php
// ============================================================
// config.php — Database connection & global constants
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // ← your MySQL username
define('DB_PASS', '');             // ← your MySQL password (blank for XAMPP default)
define('DB_NAME', 'zaiqa_restaurant');
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL',  'http://localhost/zaiqa');
define('ADMIN_URL', SITE_URL . '/admin');
define('CURRENCY',  'Rs ');
define('DELIVERY_FEE', 150);

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($script, 'install.php') === false) {
                $base = rtrim(dirname($script), '/\\');
                header('Location: ' . $base . '/install.php');
                exit;
            }
            die('DB connection failed: ' . $e->getMessage());
        }
        try {
            $pdo->query("SELECT 1 FROM settings LIMIT 1");
        } catch (PDOException $e) {
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($script, 'install.php') === false) {
                $base = rtrim(dirname($script), '/\\');
                header('Location: ' . $base . '/install.php');
                exit;
            }
        }
    }
    return $pdo;
}

function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function generate_order_ref(): string {
    return 'ZQ-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function require_admin(): void {
    start_session();
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}
