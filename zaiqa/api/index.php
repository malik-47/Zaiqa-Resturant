<?php
// ============================================================
// api/index.php — REST API router
// Usage: api/?action=menu&category=desi
// ============================================================
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── GET MENU ─────────────────────────────────────────────
    case 'menu':
        $cat    = $_GET['category'] ?? 'all';
        $search = trim($_GET['search'] ?? '');
        $pdo = db();

        $sql = "SELECT m.id, m.name, m.description, m.price, m.image_url, m.badge,
                       c.slug AS category, c.name AS category_name, c.emoji
                FROM menu_items m JOIN categories c ON m.category_id = c.id
                WHERE m.is_available = 1";
        $params = [];

        if ($cat !== 'all') {
            $sql .= " AND c.slug = ?";
            $params[] = $cat;
        }
        if ($search !== '') {
            $sql .= " AND (m.name LIKE ? OR m.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY m.is_featured DESC, m.sort_order, m.id";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $items = $st->fetchAll();

        // Format price
        foreach ($items as &$item) {
            $item['price_display'] = 'Rs ' . number_format($item['price']);
        }

        json_response(['success' => true, 'items' => $items, 'count' => count($items)]);
        break;

    // ── GET CATEGORIES ────────────────────────────────────────
    case 'categories':
        $cats = db()->query("SELECT * FROM categories WHERE active=1 ORDER BY sort_order")->fetchAll();
        json_response(['success' => true, 'categories' => $cats]);
        break;

    // ── SUBMIT RESERVATION ────────────────────────────────────
    case 'reserve':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST required'], 405);
        }
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $name    = trim($data['name']    ?? '');
        $phone   = trim($data['phone']   ?? '');
        $email   = trim($data['email']   ?? '');
        $date    = trim($data['date']    ?? '');
        $time    = trim($data['time']    ?? '');
        $guests  = trim($data['guests']  ?? '');
        $cuisine = trim($data['cuisine'] ?? 'All / Mixed');
        $notes   = trim($data['requests'] ?? '');

        if (!$name || !$phone || !$date || !$time || !$guests) {
            json_response(['success' => false, 'message' => 'Please fill all required fields.'], 422);
        }
        if (strtotime($date) < strtotime('today')) {
            json_response(['success' => false, 'message' => 'Please choose a future date.'], 422);
        }

        $pdo = db();
        $st = $pdo->prepare(
            "INSERT INTO reservations (full_name, phone, email, reservation_date, reservation_time, guests, cuisine_pref, special_requests)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $st->execute([$name, $phone, $email, $date, $time, $guests, $cuisine, $notes]);

        json_response([
            'success' => true,
            'message' => "✅ Reservation received! We'll call $phone shortly to confirm.",
            'id'      => $pdo->lastInsertId()
        ]);
        break;

    // ── PLACE ORDER ───────────────────────────────────────────
    case 'order':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST required'], 405);
        }
        $data  = json_decode(file_get_contents('php://input'), true) ?: [];
        $name  = trim($data['name']       ?? '');
        $phone = trim($data['phone']      ?? '');
        $email = trim($data['email']      ?? '');
        $type  = trim($data['order_type'] ?? 'delivery');
        $addr  = trim($data['address']    ?? '');
        $notes = trim($data['notes']      ?? '');
        $items = $data['items']           ?? [];

        if (!$name || !$phone || empty($items)) {
            json_response(['success' => false, 'message' => 'Name, phone and items are required.'], 422);
        }
        if ($type === 'delivery' && !$addr) {
            json_response(['success' => false, 'message' => 'Delivery address is required.'], 422);
        }

        $pdo = db();
        // Validate items & compute totals
        $subtotal = 0;
        $validated = [];
        foreach ($items as $item) {
            $menuItem = $pdo->prepare("SELECT id, name, price FROM menu_items WHERE id=? AND is_available=1");
            $menuItem->execute([(int)$item['id']]);
            $row = $menuItem->fetch();
            if (!$row) continue;
            $qty = max(1, (int)($item['qty'] ?? 1));
            $sub = $row['price'] * $qty;
            $subtotal += $sub;
            $validated[] = ['id' => $row['id'], 'name' => $row['name'], 'price' => $row['price'], 'qty' => $qty, 'sub' => $sub];
        }
        if (empty($validated)) {
            json_response(['success' => false, 'message' => 'No valid items in order.'], 422);
        }
        $deliveryFee = ($type === 'delivery') ? DELIVERY_FEE : 0;
        $total = $subtotal + $deliveryFee;
        $ref   = generate_order_ref();

        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                "INSERT INTO orders (order_ref, customer_name, customer_phone, customer_email,
                 delivery_address, order_type, subtotal, delivery_fee, total, notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
            );
            $st->execute([$ref, $name, $phone, $email, $addr, $type, $subtotal, $deliveryFee, $total, $notes]);
            $orderId = $pdo->lastInsertId();

            $si = $pdo->prepare(
                "INSERT INTO order_items (order_id, menu_item_id, item_name, item_price, quantity, subtotal)
                 VALUES (?,?,?,?,?,?)"
            );
            foreach ($validated as $v) {
                $si->execute([$orderId, $v['id'], $v['name'], $v['price'], $v['qty'], $v['sub']]);
            }
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'Order failed. Please try again.'], 500);
        }

        json_response([
            'success'  => true,
            'message'  => "Order $ref placed! Total: Rs " . number_format($total),
            'order_ref'=> $ref,
            'total'    => $total
        ]);
        break;

    // ── SUBMIT REVIEW ─────────────────────────────────────────
    case 'review':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST required'], 405);
        }
        $data   = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $name   = trim($data['name']   ?? '');
        $loc    = trim($data['location'] ?? '');
        $rating = (int)($data['rating'] ?? 5);
        $text   = trim($data['review'] ?? '');

        if (!$name || !$text || $rating < 1 || $rating > 5) {
            json_response(['success' => false, 'message' => 'Name, review and valid rating required.'], 422);
        }

        $st = db()->prepare(
            "INSERT INTO reviews (name, location, rating, review_text) VALUES (?,?,?,?)"
        );
        $st->execute([$name, $loc, $rating, $text]);
        json_response(['success' => true, 'message' => 'Thank you! Your review is pending approval.']);
        break;

    // ── TRACK ORDER ───────────────────────────────────────────
    case 'track':
        $ref = trim($_GET['ref'] ?? '');
        if (!$ref) json_response(['success' => false, 'message' => 'Order reference required.'], 422);

        $pdo = db();
        $st  = $pdo->prepare("SELECT * FROM orders WHERE order_ref=?");
        $st->execute([$ref]);
        $order = $st->fetch();
        if (!$order) json_response(['success' => false, 'message' => 'Order not found.'], 404);

        $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
        $items->execute([$order['id']]);
        $order['items'] = $items->fetchAll();

        json_response(['success' => true, 'order' => $order]);
        break;

    default:
        json_response(['success' => false, 'message' => 'Unknown action.'], 400);
}
