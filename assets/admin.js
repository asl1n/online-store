(function () {
  var $ = function (s, r) {
    return (r || document).querySelector(s);
  };
  var $$ = function (s, r) {
    return [].slice.call((r || document).querySelectorAll(s));
  };

  // Live "customers pay" preview under the discount field
  $$("[data-price-preview]").forEach(function (box) {
    var price = $("#price"),
      disc = $("#discount_percent");
    if (!price || !disc) return;
    function fmt(n) {
      return "Rs. " + Math.round(n).toLocaleString("en-US");
    }
    function upd() {
      var p = parseFloat(price.value),
        d = parseFloat(disc.value);
      if (!(p > 0) || !(d > 0) || d >= 100) {
        box.textContent = "";
        box.classList.remove("on");
        return;
      }
      box.innerHTML =
        "Customers will see <s>" +
        fmt(p) +
        "</s> <b>" +
        fmt(p * (1 - d / 100)) +
        "</b>";
      box.classList.add("on");
    }
    price.addEventListener("input", upd);
    disc.addEventListener("input", upd);
    upd();
  });

  // Sidebar (mobile)
  $$("[data-side-toggle]").forEach(function (b) {
    b.addEventListener("click", function () {
      document.body.classList.toggle("side-open");
    });
  });
  $$("[data-side-close]").forEach(function (b) {
    b.addEventListener("click", function () {
      document.body.classList.remove("side-open");
    });
  });

  // User dropdown
  $$("[data-dropdown]").forEach(function (d) {
    var btn = $(".user-btn", d);
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      var o = d.classList.toggle("open");
      btn.setAttribute("aria-expanded", o);
    });
    document.addEventListener("click", function (e) {
      if (!d.contains(e.target)) {
        d.classList.remove("open");
        btn.setAttribute("aria-expanded", "false");
      }
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") d.classList.remove("open");
    });
  });

  // Dialogs
  $$("dialog.dlg").forEach(function (dlg) {
    dlg.addEventListener("click", function (e) {
      if (e.target === dlg) dlg.close();
    });
    $$("[data-dlg-close]", dlg).forEach(function (b) {
      b.addEventListener("click", function () {
        dlg.close();
      });
    });
  });
  $$("[data-open-logout]").forEach(function (b) {
    b.addEventListener("click", function () {
      var d = $("#logoutDlg");
      if (d) d.showModal();
    });
  });

  // Styled confirmation for any <form data-confirm="message">
  var cdlg = $("#confirmDlg"),
    pending = null;
  document.addEventListener("submit", function (e) {
    var f = e.target,
      msg = f.getAttribute && f.getAttribute("data-confirm");
    if (!msg || !cdlg) return;
    e.preventDefault();
    pending = { form: f, sub: e.submitter };
    $("#cTitle").textContent =
      f.getAttribute("data-confirm-title") || "Are you sure?";
    $("#cMsg").textContent = msg;
    cdlg.showModal();
  });
  var ok = $("#cOk");
  if (ok)
    ok.addEventListener("click", function () {
      if (!pending) return;
      var f = pending.form,
        s = pending.sub;
      if (s && s.name) {
        var h = document.createElement("input");
        h.type = "hidden";
        h.name = s.name;
        h.value = s.value;
        f.appendChild(h);
      }
      cdlg.close();
      f.submit();
    });

  // Live search: submits 1 second after you stop typing (Enter submits instantly)
  $$("[data-live-search]").forEach(function (f) {
    var i = $("input", f),
      t;
    if (i.value) {
      i.focus();
      var n = i.value.length;
      i.setSelectionRange(n, n);
    }
    i.addEventListener("input", function () {
      clearTimeout(t);
      t = setTimeout(function () {
        f.submit();
      }, 1000);
    });
  });

  // Filter dropdowns apply as soon as you pick a value
  $$("form[data-autosubmit] select").forEach(function (sel) {
    sel.addEventListener("change", function () {
      sel.form.submit();
    });
  });

  // Gallery: preview newly chosen images before saving
  $$("[data-gallery-input]").forEach(function (inp) {
    var box = inp.parentNode.querySelector("[data-gallery-previews]");
    inp.addEventListener("change", function () {
      box.innerHTML = "";
      Array.prototype.forEach.call(inp.files, function (f) {
        if (!/^image\//.test(f.type)) return;
        var d = document.createElement("div");
        d.className = "gprev";
        var im = document.createElement("img");
        im.src = URL.createObjectURL(f);
        im.alt = "";
        var sm = document.createElement("small");
        sm.textContent = f.name;
        d.appendChild(im);
        d.appendChild(sm);
        box.appendChild(d);
      });
    });
  });

  // Auto-hide success alerts
  $$(".alert.ok").forEach(function (a) {
    setTimeout(function () {
      a.style.display = "none";
    }, 5000);
  });
})();
