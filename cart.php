<?php
require 'inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $qty = max(0, min(99, (int)($_POST['qty'] ?? 1)));
    $action = $_POST['action'] ?? '';
    $exists = $db->prepare("SELECT 1 FROM products WHERE id = ?");
    $exists->execute([$id]);
    if ($exists->fetchColumn()) {
        if ($action === 'add') {
            $_SESSION['cart'][$id] = min(99, ($_SESSION['cart'][$id] ?? 0) + max(1, $qty));
        } elseif ($action === 'set' && $qty > 0) {
            $_SESSION['cart'][$id] = $qty;
        } else { // remove или set с нулём
            unset($_SESSION['cart'][$id]);
        }
    }
    if (!empty($_POST['ajax'])) { // запрос из JS
        header('Content-Type: application/json');
        echo json_encode(['count' => cart_count()]);
        exit;
    }
    header('Location: cart.php');
    exit;
}

[$lines, $total] = cart_lines($db);
head('Корзина'); ?>
<h1>Корзина</h1>
<?php if (!$lines): ?>
  <p>Пока пусто. <a href="index.php">Выберите кофе в каталоге</a>.</p>
<?php else: ?>
<table class="table" id="cart" data-csrf="<?= e($_SESSION['csrf'] ?? '') ?>">
  <tr><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Сумма</th><th></th></tr>
  <?php foreach ($lines as $l): ?>
  <tr data-id="<?= $l['id'] ?>" data-price="<?= $l['price'] ?>">
    <td><?= e($l['name']) ?></td>
    <td><?= money($l['price']) ?></td>
    <td><input type="number" class="qty" value="<?= $l['qty'] ?>" min="0" max="99"></td>
    <td class="sum"><?= money($l['sum']) ?></td>
    <td>
      <form method="post"><?= csrf_field() ?>
        <input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= $l['id'] ?>">
        <button class="link">Удалить</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <tr class="total"><td colspan="3">Итого</td><td id="total"><?= money($total) ?></td><td></td></tr>
</table>
<p><a class="btn" href="checkout.php">Оформить заказ</a></p>
<script src="cart.js"></script>
<?php endif; ?>
<?php foot();
