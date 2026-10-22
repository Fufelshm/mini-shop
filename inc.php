<?php
// Общие настройки, подключение к БД и вспомогательные функции
const ADMIN_PASSWORD = 'admin123'; // ПОМЕНЯЙТЕ перед публикацией

session_start();
if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data');
$db = new PDO('sqlite:' . __DIR__ . '/data/shop.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$db->exec("CREATE TABLE IF NOT EXISTS products (
  id INTEGER PRIMARY KEY, name TEXT NOT NULL, description TEXT,
  price INTEGER NOT NULL, color TEXT DEFAULT '#1f6f5c')");
$db->exec("CREATE TABLE IF NOT EXISTS orders (
  id INTEGER PRIMARY KEY, name TEXT, phone TEXT, address TEXT,
  total INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$db->exec("CREATE TABLE IF NOT EXISTS order_items (
  order_id INTEGER, product_id INTEGER, name TEXT, price INTEGER, qty INTEGER)");

if (!$db->query("SELECT COUNT(*) FROM products")->fetchColumn()) {
    $st = $db->prepare("INSERT INTO products (name, description, price, color) VALUES (?,?,?,?)");
    foreach ([
        ['Эфиопия Иргачефф', 'Цветочный аромат, нотки бергамота и персика. Светлая обжарка.', 890, '#c9a227'],
        ['Колумбия Супремо', 'Сбалансированный вкус с шоколадом и орехом. Средняя обжарка.', 760, '#7a4e2d'],
        ['Бразилия Сантос', 'Мягкий, сладкий, с нотами карамели. Для турки и воронки.', 640, '#a0522d'],
        ['Кения АА', 'Яркая кислотность, чёрная смородина и грейпфрут.', 980, '#8e2f3a'],
        ['Гватемала Антигуа', 'Какао, специи и лёгкая дымность. Тёмная обжарка.', 820, '#4b3b32'],
        ['Декаф Колумбия', 'Без кофеина, вкус сохранён: ореховый, с мягкой сладостью.', 790, '#4f6d5a'],
    ] as $p) $st->execute($p);
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n) { return number_format((int)$n, 0, '', ' ') . ' ₽'; }
function bag($color, $name) {
    $c = e($color); $t = e(mb_strtoupper(mb_substr($name, 0, 1)));
    return '<svg class="bag" viewBox="0 0 200 240" role="img" aria-label="Упаковка кофе ' . e($name) . '">'
      . '<path d="M40 40h120l12 180q0 8-8 8H36q-8 0-8-8z" fill="' . $c . '"/>'
      . '<path d="M40 40h120l-2 22H42z" fill="#000" opacity=".2"/>'
      . '<rect x="58" y="100" width="84" height="92" rx="4" fill="#fff" opacity=".93"/>'
      . '<text x="100" y="160" text-anchor="middle" font-family="Georgia,serif" font-size="54" font-weight="600" fill="' . $c . '">' . $t . '</text>'
      . '<path d="M72 175h56" stroke="' . $c . '" stroke-width="2"/></svg>';
}
function cart_count() { return array_sum($_SESSION['cart'] ?? []); }

function csrf_field() {
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function check_csrf() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Неверный токен формы. Обновите страницу и попробуйте снова.');
    }
}

// Возвращает [строки корзины, общая сумма]
function cart_lines($db) {
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return [[], 0];
    $in = implode(',', array_fill(0, count($cart), '?'));
    $st = $db->prepare("SELECT * FROM products WHERE id IN ($in)");
    $st->execute(array_keys($cart));
    $lines = []; $total = 0;
    foreach ($st->fetchAll() as $p) {
        $p['qty'] = $cart[$p['id']];
        $p['sum'] = $p['qty'] * $p['price'];
        $total += $p['sum'];
        $lines[] = $p;
    }
    return [$lines, $total];
}

function head($title) { ?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> — Зерно</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="top">
  <a class="brand" href="index.php">Зерно</a>
  <nav><a href="index.php">Каталог</a><a href="cart.php">Корзина (<span id="cart-count"><?= cart_count() ?></span>)</a></nav>
</header>
<main>
<?php }

function foot() { ?>
</main>
<footer class="foot"><strong>Зерно</strong> · учебный проект: интернет-магазин на PHP и SQLite</footer>
</body>
</html>
<?php }
