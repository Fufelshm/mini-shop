<?php
require 'inc.php';
$st = $db->prepare("SELECT * FROM products WHERE id = ?");
$st->execute([(int)($_GET['id'] ?? 0)]);
$p = $st->fetch();
if (!$p) { http_response_code(404); head('Не найдено'); echo '<h1>Товар не найден</h1><p><a href="index.php">Вернуться в каталог</a></p>'; foot(); exit; }
head($p['name']); ?>
<p class="crumbs"><a href="index.php">Каталог</a> / <?= e($p['name']) ?></p>
<div class="product" style="--c:<?= e($p['color']) ?>">
  <div class="tile big"><?= bag($p['color'], $p['name']) ?></div>
  <div class="details">
    <h1><?= e($p['name']) ?></h1>
    <p class="lead"><?= e($p['description']) ?></p>
    <p class="price big-price"><?= money($p['price']) ?> <small>за 250 г</small></p>
    <form method="post" action="cart.php" class="buy">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="id" value="<?= $p['id'] ?>">
      <label>Количество <input type="number" name="qty" value="1" min="1" max="99"></label>
      <button class="btn">Добавить в корзину</button>
    </form>
    <ul class="facts">
      <li>Упаковка 250 г с клапаном</li>
      <li>Обжарка под заказ</li>
      <li>Доставка от 2 дней</li>
    </ul>
  </div>
</div>
<?php foot();
