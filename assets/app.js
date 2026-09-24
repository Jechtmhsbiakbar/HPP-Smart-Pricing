(function () {
  'use strict';
  var logoutDialog = document.getElementById('logout-dialog');
  if (logoutDialog) {
    document.querySelectorAll('[data-logout-trigger]').forEach(function (trigger) {
      trigger.addEventListener('click', function (event) {
        if (typeof logoutDialog.showModal !== 'function') return;
        event.preventDefault();
        logoutDialog.showModal();
        var cancel = logoutDialog.querySelector('[data-logout-cancel]');
        if (cancel) cancel.focus();
      });
    });
    logoutDialog.querySelectorAll('[data-logout-cancel]').forEach(function (cancel) {
      cancel.addEventListener('click', function () { logoutDialog.close(); });
    });
    logoutDialog.addEventListener('click', function (event) {
      if (event.target === logoutDialog) logoutDialog.close();
    });
  }
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) event.preventDefault();
    });
  });
  document.querySelectorAll('[data-filter-table]').forEach(function (input) {
    input.addEventListener('input', function () {
      var table = document.querySelector(input.getAttribute('data-filter-table')); if (!table) return;
      var needle = input.value.toLowerCase();
      table.querySelectorAll('tbody tr').forEach(function (row) { row.hidden = row.textContent.toLowerCase().indexOf(needle) < 0; });
    });
  });
  var add = document.getElementById('add-line'), lines = document.getElementById('ingredient-lines');
  if (add && lines) add.addEventListener('click', function () {
    var first = lines.querySelector('.ingredient-line'); if (!first) return;
    var clone = first.cloneNode(true); clone.querySelectorAll('input').forEach(function (i) { i.value = '1'; }); lines.appendChild(clone);
  });
  document.addEventListener('click', function (event) {
    if (!event.target.classList.contains('remove-line')) return;
    var all = document.querySelectorAll('.ingredient-line'); if (all.length > 1) event.target.closest('.ingredient-line').remove();
  });
  var products = document.getElementById('pos-products'), cartLines = document.getElementById('cart-lines'), cartJson = document.getElementById('cart-json'), totalEl = document.getElementById('cart-total');
  var cart = {};
  function renderCart() {
    if (!cartLines) return;
    var keys = Object.keys(cart); cartLines.innerHTML = '';
    if (!keys.length) cartLines.innerHTML = '<div class="empty"><strong>Belum ada item</strong><span>Pilih produk di sebelah kiri.</span></div>';
    var total = 0; keys.forEach(function (key) {
      var item = cart[key]; total += item.price * item.qty;
      var row = document.createElement('div'); row.className = 'cart-line';
      var name = document.createElement('strong'); name.textContent = item.name;
      var details = document.createElement('span'); details.textContent = item.qty + ' × Rp ' + Math.round(item.price).toLocaleString('id-ID');
      row.append(name, details);
      row.insertAdjacentHTML('beforeend', '<button type="button" class="button secondary small cart-minus">−</button><button type="button" class="button secondary small cart-plus">+</button>');
      row.querySelector('.cart-minus').onclick = function () { item.qty--; if (item.qty < 1) delete cart[key]; renderCart(); }; row.querySelector('.cart-plus').onclick = function () { item.qty++; renderCart(); }; cartLines.appendChild(row);
    });
    if (totalEl) totalEl.textContent = 'Rp ' + Math.round(total - Number((document.getElementById('sale-discount') || {}).value || 0)).toLocaleString('id-ID');
    if (cartJson) cartJson.value = JSON.stringify(keys.map(function (key) { return { id: cart[key].id, qty: cart[key].qty }; }));
  }
  if (products) products.addEventListener('click', function (event) { var tile = event.target.closest('.product-tile'); if (!tile) return; var id = tile.dataset.id; cart[id] = cart[id] || { id: Number(id), name: tile.dataset.name, price: Number(tile.dataset.price), qty: 0 }; cart[id].qty++; renderCart(); });
  var clear = document.getElementById('clear-cart'); if (clear) clear.onclick = function () { cart = {}; renderCart(); };
  var discount = document.getElementById('sale-discount'); if (discount) discount.addEventListener('input', renderCart);
  var search = document.getElementById('pos-search'); if (search) search.addEventListener('input', function () { var n = search.value.toLowerCase(); document.querySelectorAll('.product-tile').forEach(function (p) { p.hidden = p.textContent.toLowerCase().indexOf(n) < 0; }); });
  renderCart();
}());
