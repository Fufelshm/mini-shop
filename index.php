<?php
require 'inc.php';
$products = $db->query("SELECT * FROM products ORDER BY id")->fetchAll();
head('Каталог'); ?>
<section class="hero">
  <div>
    <h1>Кофе, обжаренный под ваш заказ</h1>
    <p class="lead">Шесть сортов из разных стран. Зерно в упаковке 250 г, обжарка за день до отправки.</p>
    <a class="btn" href="#catalog">Выбрать кофе</a>
  </div>
  <div class="hero-bags" aria-hidden="true">
    <?php foreach (array_slice($products, 0, 3) as $p) echo bag($p['color'], $p['name']); ?>
  </div>
</section>
<h2 class="section" id="catalog">Каталог</h2>
<div class="grid">
<?php foreach ($products as $p): ?>
  <a class="card" href="product.php?id=<?= $p['id'] ?>" style="--c:<?= e($p['color']) ?>">
    <div class="tile"><?= bag($p['color'], $p['name']) ?></div>
    <div class="info">
      <h3><?= e($p['name']) ?></h3>
      <p class="muted"><?= e(mb_strimwidth($p['description'], 0, 70, '…')) ?></p>
      <p class="price"><?= money($p['price']) ?></p>
    </div>
  </a>
<?php endforeach; ?>
</div>
<?php foot();
