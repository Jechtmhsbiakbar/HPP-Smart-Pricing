(function () {
  "use strict";
  var root = document.documentElement;
  var themeToggle = document.querySelector("[data-theme-toggle]");
  var themeColor = document.getElementById("theme-color");
  function applyTheme(theme) {
    root.setAttribute("data-theme", theme);
    if (themeColor)
      themeColor.setAttribute(
        "content",
        theme === "dark" ? "#0b1220" : "#2563eb",
      );
    if (themeToggle) {
      var icon = themeToggle.querySelector(".theme-toggle-icon");
      var label = themeToggle.querySelector(".theme-toggle-label");
      if (icon) icon.textContent = theme === "dark" ? "☀" : "☾";
      if (label)
        label.textContent = theme === "dark" ? "Mode terang" : "Mode gelap";
      themeToggle.setAttribute(
        "aria-label",
        theme === "dark" ? "Aktifkan light mode" : "Aktifkan dark mode",
      );
    }
  }
  var savedTheme = localStorage.getItem("hpp-theme");
  applyTheme(
    savedTheme === "dark" || savedTheme === "light"
      ? savedTheme
      : window.matchMedia &&
          window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light",
  );
  if (themeToggle)
    themeToggle.addEventListener("click", function () {
      var next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
      localStorage.setItem("hpp-theme", next);
      applyTheme(next);
    });
  var logoutDialog = document.getElementById("logout-dialog");
  if (logoutDialog) {
    document
      .querySelectorAll("[data-logout-trigger]")
      .forEach(function (trigger) {
        trigger.addEventListener("click", function (event) {
          if (typeof logoutDialog.showModal !== "function") return;
          event.preventDefault();
          logoutDialog.showModal();
          var cancel = logoutDialog.querySelector("[data-logout-cancel]");
          if (cancel) cancel.focus();
        });
      });
    logoutDialog
      .querySelectorAll("[data-logout-cancel]")
      .forEach(function (cancel) {
        cancel.addEventListener("click", function () {
          logoutDialog.close();
        });
      });
    logoutDialog.addEventListener("click", function (event) {
      if (event.target === logoutDialog) logoutDialog.close();
    });
  }
  document.querySelectorAll("form[data-confirm]").forEach(function (form) {
    form.addEventListener("submit", function (event) {
      if (!window.confirm(form.getAttribute("data-confirm")))
        event.preventDefault();
    });
  });
  document.querySelectorAll("[data-filter-table]").forEach(function (input) {
    input.addEventListener("input", function () {
      var table = document.querySelector(
        input.getAttribute("data-filter-table"),
      );
      if (!table) return;
      var needle = input.value.toLowerCase();
      table.querySelectorAll("tbody tr").forEach(function (row) {
        row.hidden = row.textContent.toLowerCase().indexOf(needle) < 0;
      });
    });
  });
  var add = document.getElementById("add-line"),
    lines = document.getElementById("ingredient-lines");
  if (add && lines)
    add.addEventListener("click", function () {
      var first = lines.querySelector(".ingredient-line");
      if (!first) return;
      var clone = first.cloneNode(true);
      clone.querySelectorAll("input").forEach(function (i) {
        i.value = "1";
      });
      lines.appendChild(clone);
    });
  document.addEventListener("click", function (event) {
    if (!event.target.classList.contains("remove-line")) return;
    var all = document.querySelectorAll(".ingredient-line");
    if (all.length > 1) event.target.closest(".ingredient-line").remove();
  });
  var products = document.getElementById("pos-products"),
    cartLines = document.getElementById("cart-lines"),
    cartJson = document.getElementById("cart-json"),
    totalEl = document.getElementById("cart-total"),
    paidInput = document.getElementById("sale-paid"),
    exactPay = document.getElementById("pay-exact");
  var cart = {};
  var currentTotal = 0;
  var activeCategory = "all";
  function filterPosProducts() {
    if (!products) return;
    var needle = ((document.getElementById("pos-search") || {}).value || "").toLowerCase();
    var visible = 0;
    products.querySelectorAll(".product-tile").forEach(function (tile) {
      var matchesCategory = activeCategory === "all" || tile.dataset.category === activeCategory;
      var matchesSearch = tile.textContent.toLowerCase().indexOf(needle) >= 0;
      tile.hidden = !(matchesCategory && matchesSearch);
      if (!tile.hidden) visible++;
    });
    var empty = document.getElementById("pos-empty");
    var hasActiveFilter = activeCategory !== "all" || needle !== "";
    if (empty) empty.hidden = !hasActiveFilter || visible > 0;
  }
  function renderCart() {
    if (!cartLines) return;
    var keys = Object.keys(cart);
    cartLines.innerHTML = "";
    if (!keys.length)
      cartLines.innerHTML =
        '<div class="empty"><strong>Belum ada item</strong><span>Pilih produk di sebelah kiri.</span></div>';
    var total = 0;
    keys.forEach(function (key) {
      var item = cart[key];
      total += item.price * item.qty;
      var row = document.createElement("div");
      row.className = "cart-line";
      var name = document.createElement("strong");
      name.textContent = item.name;
      var details = document.createElement("span");
      details.textContent =
        item.qty + " × Rp " + Math.round(item.price).toLocaleString("id-ID");
      row.append(name, details);
      row.insertAdjacentHTML(
        "beforeend",
        '<button type="button" class="button secondary small cart-minus">−</button><button type="button" class="button secondary small cart-plus">+</button>',
      );
      row.querySelector(".cart-minus").onclick = function () {
        item.qty--;
        if (item.qty < 1) delete cart[key];
        renderCart();
      };
      row.querySelector(".cart-plus").onclick = function () {
        item.qty++;
        renderCart();
      };
      cartLines.appendChild(row);
    });
    currentTotal = Math.max(
      0,
      Math.round(
        total -
          Number((document.getElementById("sale-discount") || {}).value || 0),
      ),
    );
    if (totalEl)
      totalEl.textContent = "Rp " + currentTotal.toLocaleString("id-ID");
    if (exactPay) {
      var exactLabel = exactPay.querySelector("span");
      if (exactLabel)
        exactLabel.textContent = "Rp " + currentTotal.toLocaleString("id-ID");
      exactPay.disabled = currentTotal <= 0;
    }
    if (cartJson)
      cartJson.value = JSON.stringify(
        keys.map(function (key) {
          return { id: cart[key].id, qty: cart[key].qty };
        }),
      );
  }
  if (products)
    products.addEventListener("click", function (event) {
      var tile = event.target.closest(".product-tile");
      if (!tile) return;
      var id = tile.dataset.id;
      cart[id] = cart[id] || {
        id: Number(id),
        name: tile.dataset.name,
        price: Number(tile.dataset.price),
        qty: 0,
      };
      cart[id].qty++;
      renderCart();
    });
  var clear = document.getElementById("clear-cart");
  if (clear)
    clear.onclick = function () {
      cart = {};
      renderCart();
    };
  var discount = document.getElementById("sale-discount");
  if (discount) discount.addEventListener("input", renderCart);
  if (exactPay)
    exactPay.addEventListener("click", function () {
      if (!paidInput) return;
      paidInput.value = currentTotal;
      paidInput.dispatchEvent(new Event("input", { bubbles: true }));
      paidInput.focus();
    });
  var search = document.getElementById("pos-search");
  if (search)
    search.addEventListener("input", function () {
      filterPosProducts();
    });
  document.querySelectorAll(".category-pill").forEach(function (pill) {
    pill.addEventListener("click", function () {
      activeCategory = pill.dataset.category || "all";
      document.querySelectorAll(".category-pill").forEach(function (item) {
        var isActive = item === pill;
        item.classList.toggle("is-active", isActive);
        item.setAttribute("aria-selected", isActive ? "true" : "false");
      });
      filterPosProducts();
    });
  });
  filterPosProducts();
  var salesExport = document.querySelector("[data-export-sales]");
  var exportFeedback = document.querySelector("[data-export-feedback]");
  var exportTitle = document.getElementById("sales-export-title");
  var exportDetail = document.getElementById("sales-export-detail");
  var exportPercent = document.getElementById("sales-export-percent");
  var exportProgress = document.getElementById("sales-export-progress");
  function setExportProgress(state, percent, title, detail) {
    if (exportFeedback) {
      exportFeedback.hidden = false;
      exportFeedback.classList.remove("is-loading", "is-success", "is-error");
      exportFeedback.classList.add("is-" + state);
    }
    if (exportTitle) exportTitle.textContent = title;
    if (exportDetail) exportDetail.textContent = detail;
    if (exportPercent) exportPercent.textContent = percent + "%";
    if (exportProgress) exportProgress.style.width = percent + "%";
  }
  if (salesExport)
    salesExport.addEventListener("click", function () {
      var from = document.getElementById("sales-from");
      var to = document.getElementById("sales-to");
      var api = salesExport.getAttribute("data-api");
      var originalLabel = "Export XLSX (File excel)";

      if (!from || !to || !api || typeof XLSX === "undefined") {
        setExportProgress("error", 100, "Export gagal", "Library export belum tersedia. Muat ulang halaman lalu coba lagi.");
        return;
      }
      if (!from.value || !to.value || from.value > to.value) {
        setExportProgress("error", 100, "Tanggal belum valid", "Pilih rentang tanggal yang benar terlebih dahulu.");
        return;
      }

      salesExport.disabled = true;
      salesExport.setAttribute("aria-busy", "true");
      salesExport.textContent = "Menyiapkan file…";
      setExportProgress("loading", 15, "Mengambil data penjualan", "Memuat data sesuai periode yang dipilih…");

      fetch(api + "?from=" + encodeURIComponent(from.value) + "&to=" + encodeURIComponent(to.value), {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
      })
        .then(function (response) {
          return response.json().then(function (payload) {
            if (!response.ok || !payload.success) throw new Error(payload.message || "Data export tidak tersedia.");
            return payload.data;
          });
        })
        .then(function (data) {
          setExportProgress("loading", 50, "Memproses data penjualan", "Menyiapkan " + (data.rows || []).length + " transaksi ke workbook…");
          var headers = ["Invoice", "Tanggal", "Item", "Total", "HPP", "Metode", "Status"];
          var values = (data.rows || []).map(function (row) {
            return [
              row.invoice_no,
              row.sold_at,
              row.items || "-",
              Number(row.total) || 0,
              Number(row.total_hpp) || 0,
              row.payment_method,
              row.status,
            ];
          });
          var worksheet = XLSX.utils.aoa_to_sheet([
            ["Laporan Penjualan"],
            ["Periode: " + data.from + " s/d " + data.to + " • Digenerate pada " + new Date().toLocaleString("id-ID")],
            [],
            headers,
          ].concat(values));
          worksheet["!merges"] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 6 } }, { s: { r: 1, c: 0 }, e: { r: 1, c: 6 } }];
          worksheet["!cols"] = [
            { wch: 18 },
            { wch: 20 },
            { wch: 40 },
            { wch: 16 },
            { wch: 16 },
            { wch: 14 },
            { wch: 14 },
          ];
          worksheet["!autofilter"] = { ref: "A4:G" + Math.max(4, values.length + 4) };
          worksheet["!freeze"] = { xSplit: 0, ySplit: 4 };
          values.forEach(function (_, index) {
            ["D", "E"].forEach(function (column) {
              var cell = worksheet[column + (index + 5)];
              if (cell) cell.z = '"Rp" #,##0';
            });
          });
          var workbook = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(workbook, worksheet, "Penjualan");
          setExportProgress("loading", 80, "Mengunduh file", "Workbook selesai dibuat. Browser sedang memulai download…");
          XLSX.writeFile(workbook, "laporan-penjualan.xlsx", { compression: true });
          setExportProgress("success", 100, "Export berhasil", "File laporan-penjualan.xlsx sudah dikirim ke folder download Anda.");
        })
        .catch(function (error) {
          var message = error.name === "SyntaxError"
            ? "Server mengirim respons yang tidak valid. Silakan muat ulang halaman dan coba lagi."
            : error.message || "Terjadi kendala. Silakan coba lagi.";
          setExportProgress("error", 100, "Export gagal", message);
        })
        .finally(function () {
          salesExport.disabled = false;
          salesExport.setAttribute("aria-busy", "false");
          salesExport.textContent = originalLabel;
        });
    });
  renderCart();
})();
