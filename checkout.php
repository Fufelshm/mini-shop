<?php
require 'inc.php';
[$lines, $total] = cart_lines($db);
if (!$lines && empty($_SESSION['last_order'])) { header('Location: cart.php'); exit; }

$errors = []; $v = ['name' => '', 'phone' => '', 'address' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lines) {
    check_csrf();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    if (mb_strlen($v['name']) < 2) $errors[] = 'Укажите имя (минимум 2 символа).';
    if (!preg_match('/^[\d\s()+-]{7,20}$/', $v['phone'])) $errors[] = 'Телефон: от 7 до 20 символов, только цифры, пробелы, +, - и скобки.';
    if (mb_strlen($v['address']) < 5) $errors[] = 'Укажите адрес доставки.';
    if (!$errors) {
        $db->beginTransaction();
        $db->prepare("INSERT INTO orders (name, phone, address, total) VALUES (?,?,?,?)")
           ->execute([$v['name'], $v['phone'], $v['address'], $total]);
        $orderId = $db->lastInsertId();
        $ins = $db->prepare("INSERT INTO order_items (order_id, product_id, name, price, qty) VALUES (?,?,?,?,?)");
        foreach ($lines as $l) $ins->execute([$orderId, $l['id'], $l['name'], $l['price'], $l['qty']]);
        $db->commit();
        $_SESSION['cart'] = [];
        $_SESSION['last_order'] = $orderId;
        header('Location: checkout.php?done=1');
        exit;
    }
}

if (isset($_GET['done']) && !empty($_SESSION['last_order'])) {
    head('Заказ оформлен');
    echo '<h1>Спасибо за заказ!</h1><p>Номер заказа: <strong>' . (int)$_SESSION['last_order'] . '</strong>. Мы свяжемся с вами по телефону.</p>';
    echo '<p><a href="index.php">Вернуться в каталог</a></p>';
    unset($_SESSION['last_order']);
    foot(); exit;
}

head('Оформление заказа'); ?>
<h1>Оформление заказа</h1>
<p>Сумма к оплате: <strong><?= money($total) ?></strong></p>
<?php foreach ($errors as $err): ?><p class="error"><?= e($err) ?></p><?php endforeach; ?>
<form method="post" class="form">
  <?= csrf_field() ?>
  <label>Имя <input name="name" value="<?= e($v['name']) ?>" required></label>
  <label>Телефон <input name="phone" value="<?= e($v['phone']) ?>" required></label>
  <label>Адрес доставки <textarea name="address" rows="3" required><?= e($v['address']) ?></textarea></label>
  <button class="btn">Подтвердить заказ</button>
</form>
<?php foot();
