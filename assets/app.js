(function () {
  "use strict";
  var form = document.getElementById("recipe-form");
  var lines = document.getElementById("ingredient-lines");
  var format = function (value) {
    return "Rp " + Math.round(value).toLocaleString("id-ID");
  };
  var factor = {
    kg: 1000,
    liter: 1000,
    meter: 100,
    gram: 1,
    ml: 1,
    cm: 1,
    pcs: 1,
  };
  var moneyInputs = document.querySelectorAll("[data-money-input]");
  function digits(value) {
    return value.replace(/[^\d]/g, "");
  }
  function syncMoneyInput(input) {
    var numeric = digits(input.value);
    var hidden = document.getElementById(input.dataset.hiddenTarget);
    if (hidden) hidden.value = numeric || "0";
    input.value = format(Number(numeric || 0));
  }
  function formatMoneyWhileTyping(input) {
    var numeric = digits(input.value);
    var hidden = document.getElementById(input.dataset.hiddenTarget);
    if (hidden) hidden.value = numeric || "0";
    input.value = numeric ? format(Number(numeric)) : "";
  }
  function tierMarkup(value) {
    return (
      { murah: 15, normal: 30, mahal: 50, 15: 15, 30: 30, 50: 50 }[value] || 30
    );
  }
  function tierName(value) {
    return { murah: "Murah", normal: "Normal", mahal: "Mahal", 15: "Murah", 30: "Normal", 50: "Mahal" }[value] || "Normal";
  }
  var backToTop = document.createElement("button");
  backToTop.type = "button";
  backToTop.className = "back-to-top";
  backToTop.setAttribute("aria-label", "Kembali ke atas");
  backToTop.setAttribute("title", "Kembali ke atas");
  backToTop.setAttribute("aria-hidden", "true");
  backToTop.tabIndex = -1;
  backToTop.innerHTML = '<span aria-hidden="true">↑</span><span class="back-to-top-label">Atas</span>';
  document.body.appendChild(backToTop);
  function updateBackToTop() {
    var visible = window.scrollY > 280;
    backToTop.classList.toggle("is-visible", visible);
    backToTop.setAttribute("aria-hidden", visible ? "false" : "true");
    backToTop.tabIndex = visible ? 0 : -1;
  }
  window.addEventListener("scroll", updateBackToTop, { passive: true });
  backToTop.addEventListener("click", function () {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
  updateBackToTop();
  var logoViewer = document.createElement("dialog");
  logoViewer.className = "logo-viewer";
  logoViewer.setAttribute("aria-label", "Tampilan logo penuh");
  logoViewer.innerHTML = '<button type="button" class="logo-viewer-close" aria-label="Tutup logo">×</button><img class="logo-viewer-image" alt="Logo Toko Bunga Paubut Cantik">';
  document.body.appendChild(logoViewer);
  var logoViewerImage = logoViewer.querySelector(".logo-viewer-image");
  document.querySelectorAll(".brand-logo").forEach(function (logo) {
    logo.setAttribute("tabindex", "0");
    logo.setAttribute("role", "button");
    logo.setAttribute("aria-label", "Lihat logo dalam ukuran penuh");
    function openLogoViewer(event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      logoViewerImage.src = logo.currentSrc || logo.src;
      if (typeof logoViewer.showModal === "function") logoViewer.showModal();
    }
    logo.addEventListener("click", openLogoViewer);
    logo.addEventListener("keydown", function (event) {
      if (event.key === "Enter" || event.key === " ") openLogoViewer(event);
    });
  });
  logoViewer.querySelector(".logo-viewer-close").addEventListener("click", function () {
    logoViewer.close();
  });
  logoViewer.addEventListener("click", function (event) {
    if (event.target === logoViewer) logoViewer.close();
  });
  moneyInputs.forEach(function (input) {
    input.addEventListener("focus", function () {
      input.value = digits(input.value);
      input.select();
    });
    input.addEventListener("input", function () {
      formatMoneyWhileTyping(input);
    });
    input.addEventListener("blur", function () {
      syncMoneyInput(input);
    });
    syncMoneyInput(input);
  });
  document.querySelectorAll("[data-modal-open]").forEach(function (button) {
    button.addEventListener("click", function () {
      var modal = document.getElementById(button.dataset.modalOpen);
      if (modal && typeof modal.showModal === "function") {
        modal.showModal();
        var tier = modal.querySelector("[data-pricing-tier]");
        if (tier) tier.dispatchEvent(new Event("change", { bubbles: true }));
      }
    });
  });
  document.querySelectorAll("[data-modal-close]").forEach(function (button) {
    button.addEventListener("click", function () {
      var modal = button.closest("dialog");
      if (modal) modal.close();
    });
  });
  document.querySelectorAll("dialog").forEach(function (modal) {
    modal.addEventListener("click", function (event) {
      if (event.target === modal) modal.close();
    });
  });
  document.querySelectorAll("form").forEach(function (productForm) {
    productForm.addEventListener("submit", function () {
      var tier =
        productForm.querySelector("[data-pricing-tier]") ||
        document.getElementById("pricing-tier");
      if (
        !tier ||
        !productForm.querySelector(
          'input[name="action"][value="add_recipe"], input[name="action"][value="update_recipe"]',
        )
      )
        return;
      var hiddenTier = productForm.querySelector('input[name="price_tier"]');
      if (!hiddenTier) {
        hiddenTier = document.createElement("input");
        hiddenTier.type = "hidden";
        hiddenTier.name = "price_tier";
        productForm.appendChild(hiddenTier);
      }
      hiddenTier.value =
        { 15: "murah", 30: "normal", 50: "mahal" }[tier.value] ||
        tier.value ||
        "normal";
    });
  });
  document
    .querySelectorAll(".edit-recipe-form")
    .forEach(function (pricingForm) {
      if (!pricingForm.querySelector("[data-pricing-tier]")) {
        var dialog = pricingForm.closest("dialog");
        var productCard = dialog ? dialog.previousElementSibling : null;
        var hppElement = productCard
          ? productCard.querySelector(":scope > strong:last-of-type")
          : null;
        var hpp = hppElement ? Number(digits(hppElement.textContent)) || 0 : 0;
        var pricingBox = document.createElement("div");
        pricingBox.className = "edit-pricing-box";
        pricingBox.innerHTML =
          '<p class="eyebrow">REKOMENDASI HARGA JUAL</p><label>Pilih posisi harga<select data-pricing-tier><option value="15">Murah · markup 15%</option><option value="30" selected>Normal · markup 30%</option><option value="50">Mahal · markup 50%</option></select></label><div class="price-result"><span>Harga jual rekomendasi</span><strong data-pricing-result>Rp 0</strong></div>';
        var actions = pricingForm.querySelector(".modal-actions");
        if (actions) pricingForm.insertBefore(pricingBox, actions);
        var savedTier = productCard ? productCard.dataset.priceTier : "";
        if (savedTier)
          pricingBox.querySelector("[data-pricing-tier]").value = savedTier;
      }
    });
  document
    .querySelectorAll("[data-pricing-form], .edit-recipe-form")
    .forEach(function (pricingForm) {
      var hpp = Number(pricingForm.dataset.hpp) || 0;
      if (!hpp) {
        var dialog = pricingForm.closest("dialog");
        var productCard = dialog ? dialog.previousElementSibling : null;
        var hppElement = productCard
          ? productCard.querySelector(":scope > strong:last-of-type")
          : null;
        hpp = hppElement ? Number(digits(hppElement.textContent)) || 0 : 0;
      }
      var tier = pricingForm.querySelector("[data-pricing-tier]");
      var result = pricingForm.querySelector("[data-pricing-result]");
      if (!tier || !result) return;
      function updatePrice() {
        var markup = tierMarkup(tier.value);
        result.textContent = format(hpp * (1 + markup / 100));
        var resultLabel = result.parentElement.querySelector("span");
        if (resultLabel) resultLabel.textContent = "Harga Jual Rekomendasi (" + tierName(tier.value) + ")";
      }
      tier.addEventListener("change", updatePrice);
      tier.addEventListener("input", updatePrice);
      updatePrice();
    });
  if (!form || !lines) return;
  form.addEventListener("submit", function () {
    moneyInputs.forEach(syncMoneyInput);
  });
  function calculate() {
    var total = 0;
    lines.querySelectorAll(".ingredient-line").forEach(function (line) {
      var selected =
        line.querySelector(".ingredient-select").selectedOptions[0];
      var quantity = Number(line.querySelector(".quantity-input").value) || 0;
      if (!selected || !selected.dataset.price) return;
      var price = Number(selected.dataset.price),
        priceQuantity = Number(selected.dataset.quantity) || 1;
      var priceUnit = selected.dataset.unit,
        selectedUnit = line.querySelector(".unit-select").value;
      var priceFactor = factor[priceUnit] || 1,
        selectedFactor = factor[selectedUnit] || 1;
      total +=
        (quantity * selectedFactor * (price / priceQuantity)) / priceFactor;
    });
    var equipment =
      Number(document.getElementById("equipment-cost").value) || 0;
    var operational =
      Number(document.getElementById("operational-cost").value) || 0;
    var hpp = total + equipment + operational;
    document.getElementById("ingredient-total").textContent = format(total);
    document.getElementById("equipment-total").textContent = format(equipment);
    document.getElementById("operational-total").textContent =
      format(operational);
    document.getElementById("hpp-total").textContent = format(hpp);
    var pricingTier = document.getElementById("pricing-tier");
    var markup = pricingTier ? tierMarkup(pricingTier.value) : 30;
    var sellingLabel = document.getElementById("selling-price");
    if (sellingLabel && sellingLabel.parentElement) {
      var label = sellingLabel.parentElement.querySelector("span");
      if (label) label.textContent = "Harga Jual Rekomendasi (" + tierName(pricingTier ? pricingTier.value : "normal") + ")";
    }
    document.getElementById("selling-price").textContent = format(
      hpp * (1 + markup / 100),
    );
  }
  document.getElementById("add-line").addEventListener("click", function () {
    var first = lines.querySelector(".ingredient-line");
    var clone = first.cloneNode(true);
    clone.querySelector(".ingredient-select").value = "";
    clone.querySelector(".quantity-input").value = "1";
    lines.appendChild(clone);
    calculate();
  });
  lines.addEventListener("click", function (event) {
    if (!event.target.classList.contains("remove-line")) return;
    var all = lines.querySelectorAll(".ingredient-line");
    if (all.length > 1) event.target.closest(".ingredient-line").remove();
    calculate();
  });
  form.addEventListener("input", calculate);
  form.addEventListener("change", calculate);
  var pricingTier = document.getElementById("pricing-tier");
  if (pricingTier)
    pricingTier.addEventListener("change", function () {
      var hiddenTier = document.getElementById("price-tier-value");
      if (hiddenTier)
        hiddenTier.value =
          { 15: "murah", 30: "normal", 50: "mahal" }[pricingTier.value] ||
          "normal";
      calculate();
    });
  calculate();
})();
