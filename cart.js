// Меняем количество без перезагрузки страницы
const table = document.getElementById('cart');
const fmt = n => n.toLocaleString('ru-RU').replace(/\u00a0/g, ' ') + ' ₽';

function recalc() {
  let total = 0;
  table.querySelectorAll('tr[data-id]').forEach(row => {
    const qty = parseInt(row.querySelector('.qty').value, 10) || 0;
    const sum = qty * Number(row.dataset.price);
    row.querySelector('.sum').textContent = fmt(sum);
    total += sum;
  });
  document.getElementById('total').textContent = fmt(total);
}

table.addEventListener('change', async e => {
  if (!e.target.classList.contains('qty')) return;
  const row = e.target.closest('tr');
  const qty = Math.max(0, Math.min(99, parseInt(e.target.value, 10) || 0));
  e.target.value = qty;
  const data = new FormData();
  data.append('csrf', table.dataset.csrf);
  data.append('action', 'set');
  data.append('id', row.dataset.id);
  data.append('qty', qty);
  data.append('ajax', 1);
  try {
    const res = await fetch('cart.php', { method: 'POST', body: data });
    const json = await res.json();
    document.getElementById('cart-count').textContent = json.count;
    if (qty === 0) row.remove();
    recalc();
    if (!table.querySelector('tr[data-id]')) location.reload();
  } catch (err) {
    location.reload(); // если что-то пошло не так, покажем актуальную корзину
  }
});
