<?php
require 'inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $a = $_POST['action'] ?? '';
    if ($a === 'login') {
        if (hash_equals(ADMIN_PASSWORD, $_POST['password'] ?? '')) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
        } else {
            $loginError = 'Неверный пароль.';
        }
    } elseif (!empty($_SESSION['admin'])) {
        if ($a === 'logout') { unset($_SESSION['admin']); }
        elseif ($a === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $price = (int)($_POST['price'] ?? 0);
            $color = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#1f6f5c';
            if ($name !== '' && $price > 0) {
                if ($id) {
                    $db->prepare("UPDATE products SET name=?, description=?, price=?, color=? WHERE id=?")
                       ->execute([$name, trim($_POST['description'] ?? ''), $price, $color, $id]);
                } else {
                    $db->prepare("INSERT INTO products (name, description, price, color) VALUES (?,?,?,?)")
                       ->execute([$name, trim($_POST['description'] ?? ''), $price, $color]);
                }
            }
        } elseif ($a === 'delete') {
            $db->prepare("DELETE FROM products WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
        }
    }
    header('Location: admin.php');
    exit;
}

head('Админка');
if (empty($_SESSION['admin'])): ?>
<h1>Вход в админку</h1>
<?php if (!empty($loginError)) echo '<p class="error">' . e($loginError) . '</p>'; ?>
<form method="post" class="form">
  <?= csrf_field() ?><input type="hidden" name="action" value="login">
  <label>Пароль <input type="password" name="password" required></label>
  <button class="btn">Войти</button>
</form>
<?php else:
  $products = $db->query("SELECT * FROM products ORDER BY id")->fetchAll();
  $orders = $db->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
  $items = $db->prepare("SELECT name, qty FROM order_items WHERE order_id = ?"); ?>
<h1>Товары</h1>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="link">Выйти</button></form>
<?php foreach (array_merge($products, [['id' => 0, 'name' => '', 'description' => '', 'price' => '', 'color' => '#1f6f5c']]) as $p): ?>
<form method="post" class="admin-row">
  <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $p['id'] ?>">
  <input name="name" value="<?= e($p['name']) ?>" placeholder="Название" required>
  <input name="description" value="<?= e($p['description']) ?>" placeholder="Описание">
  <input name="price" type="number" min="1" value="<?= e($p['price']) ?>" placeholder="Цена" required>
  <input name="color" type="color" value="<?= e($p['color']) ?>">
  <button class="btn"><?= $p['id'] ? 'Сохранить' : 'Добавить товар' ?></button>
  <?php if ($p['id']): ?>
    <button class="link" name="action" value="delete" onclick="return confirm('Удалить товар?')">Удалить</button>
  <?php endif; ?>
</form>
<?php endforeach; ?>

<h1>Заказы</h1>
<?php if (!$orders): ?>
<p>Заказов пока нет.</p>
<?php else: ?>
<table class="table">
  <tr><th>№</th><th>Дата</th><th>Клиент</th><th>Состав</th><th>Сумма</th></tr>
  <?php foreach ($orders as $o): $items->execute([$o['id']]); ?>
  <tr>
    <td><?= $o['id'] ?></td><td><?= e($o['created_at']) ?></td>
    <td><?= e($o['name']) ?><br><?= e($o['phone']) ?><br><?= e($o['address']) ?></td>
    <td><?php foreach ($items->fetchAll() as $i) echo e($i['name']) . ' × ' . (int)$i['qty'] . '<br>'; ?></td>
    <td><?= money($o['total']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; endif; foot();
