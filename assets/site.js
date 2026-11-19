document.querySelectorAll("[data-slider]").forEach(function (el) {
  var slides = el.querySelectorAll(".slide"),
    dots = el.querySelectorAll(".dots span"),
    i = 0,
    t;
  if (slides.length < 2) return;
  function show(n) {
    slides[i].classList.remove("on");
    if (dots[i]) dots[i].classList.remove("on");
    i = (n + slides.length) % slides.length;
    slides[i].classList.add("on");
    if (dots[i]) dots[i].classList.add("on");
  }
  function auto() {
    clearInterval(t);
    t = setInterval(function () {
      show(i + 1);
    }, 5000);
  }
  el.querySelector("[data-next]").addEventListener("click", function () {
    show(i + 1);
    auto();
  });
  el.querySelector("[data-prev]").addEventListener("click", function () {
    show(i - 1);
    auto();
  });
  auto();
});
/* phone menu: burger toggles it; tapping anywhere outside (or Esc) closes it */
(function () {
  var nb = document.querySelector("[data-nav-toggle]"),
    nav = document.getElementById("mainnav");
  if (!nb || !nav) return;
  function set(open) {
    nav.classList.toggle("open", open);
    nb.setAttribute("aria-expanded", open ? "true" : "false");
  }
  nb.setAttribute("aria-expanded", "false");
  nb.addEventListener("click", function (e) {
    e.stopPropagation();
    set(!nav.classList.contains("open"));
  });
  document.addEventListener("click", function (e) {
    if (
      nav.classList.contains("open") &&
      !nav.contains(e.target) &&
      !nb.contains(e.target)
    )
      set(false);
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") set(false);
  });
  window.addEventListener("resize", function () {
    if (window.innerWidth > 760) set(false);
  });
})();
document.querySelectorAll("[data-autosubmit]").forEach(function (s) {
  s.addEventListener("change", function () {
    s.form.submit();
  });
});

document.querySelectorAll(".navdrop").forEach(function (d) {
  var b = d.querySelector("[data-drop]");
  b.addEventListener("click", function (e) {
    e.stopPropagation();
    var o = d.classList.toggle("open");
    b.setAttribute("aria-expanded", o);
  });
  document.addEventListener("click", function (e) {
    if (!d.contains(e.target)) {
      d.classList.remove("open");
      b.setAttribute("aria-expanded", "false");
    }
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      d.classList.remove("open");
      b.setAttribute("aria-expanded", "false");
    }
  });
});

/* featured rail: arrows, wraps around at either end */
document.querySelectorAll("[data-rail]").forEach(function (w) {
  var t = w.querySelector("[data-rail-track]"),
    p = w.querySelector("[data-rail-prev]"),
    n = w.querySelector("[data-rail-next]");
  function atEnd() {
    return t.scrollLeft + t.clientWidth >= t.scrollWidth - 4;
  }
  function check() {
    w.classList.toggle("static", t.scrollWidth <= t.clientWidth + 2);
  }
  n.addEventListener("click", function () {
    if (atEnd()) t.scrollTo({ left: 0, behavior: "smooth" });
    else t.scrollBy({ left: t.clientWidth * 0.9, behavior: "smooth" });
  });
  p.addEventListener("click", function () {
    if (t.scrollLeft <= 4)
      t.scrollTo({ left: t.scrollWidth, behavior: "smooth" });
    else t.scrollBy({ left: -t.clientWidth * 0.9, behavior: "smooth" });
  });
  window.addEventListener("resize", check);
  window.addEventListener("load", check);
  check();
});

/* phone fields: letters and symbols are removed as the person types or pastes */
document.querySelectorAll("[data-phone]").forEach(function (i) {
  i.addEventListener("input", function () {
    var c = i.value.replace(/[^0-9+\-\s()]/g, "");
    if (c !== i.value) i.value = c;
  });
});

/* product gallery: thumbnails swap the main image; clicking the main image opens a full-screen viewer */
(function () {
  var g = document.querySelector("[data-gallery]");
  if (!g) return;
  var main = g.querySelector("[data-gallery-main]"),
    openBtn = g.querySelector("[data-gallery-open]"),
    thumbs = [].slice.call(g.querySelectorAll("[data-gallery-thumb]")),
    srcs = thumbs.length
      ? thumbs.map(function (t) {
          return t.getAttribute("data-full");
        })
      : [main.getAttribute("src")],
    cur = 0,
    lb = null,
    lastFocus = null,
    startX = null;

  function show(i) {
    cur = (i + srcs.length) % srcs.length;
    main.setAttribute("src", srcs[cur]);
    thumbs.forEach(function (t, k) {
      t.classList.toggle("active", k === cur);
      if (k === cur && t.scrollIntoView)
        t.scrollIntoView({ block: "nearest", inline: "nearest" });
    });
    if (lb) {
      lb.querySelector(".lb-img").setAttribute("src", srcs[cur]);
      lb.querySelector(".lb-count").textContent =
        srcs.length > 1 ? cur + 1 + " / " + srcs.length : "";
    }
  }
  thumbs.forEach(function (t, k) {
    t.addEventListener("click", function () {
      show(k);
    });
  });

  function build() {
    lb = document.createElement("div");
    lb.className = "lb";
    lb.setAttribute("role", "dialog");
    lb.setAttribute("aria-modal", "true");
    lb.setAttribute("aria-label", "Image viewer");
    lb.innerHTML =
      '<button type="button" class="lb-close" aria-label="Close">&#10005;</button>' +
      (srcs.length > 1
        ? '<button type="button" class="lb-nav lb-prev" aria-label="Previous image">&#8249;</button><button type="button" class="lb-nav lb-next" aria-label="Next image">&#8250;</button>'
        : "") +
      '<img class="lb-img" alt=""><div class="lb-count"></div>';
    document.body.appendChild(lb);
    lb.addEventListener("click", function (e) {
      if (e.target === lb || e.target.classList.contains("lb-close")) close();
      else if (e.target.classList.contains("lb-prev")) show(cur - 1);
      else if (e.target.classList.contains("lb-next")) show(cur + 1);
    });
    lb.addEventListener(
      "touchstart",
      function (e) {
        startX = e.touches[0].clientX;
      },
      { passive: true },
    );
    lb.addEventListener("touchend", function (e) {
      if (startX === null || srcs.length < 2) return;
      var dx = e.changedTouches[0].clientX - startX;
      startX = null;
      if (Math.abs(dx) > 50) show(cur + (dx < 0 ? 1 : -1));
    });
  }
  function open() {
    if (!lb) build();
    lastFocus = document.activeElement;
    lb.classList.add("open");
    document.documentElement.classList.add("lb-lock");
    show(cur);
    lb.querySelector(".lb-close").focus();
  }
  function close() {
    if (!lb) return;
    lb.classList.remove("open");
    document.documentElement.classList.remove("lb-lock");
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }
  document.addEventListener("keydown", function (e) {
    if (!lb || !lb.classList.contains("open")) return;
    if (e.key === "Escape") close();
    else if (e.key === "ArrowLeft") show(cur - 1);
    else if (e.key === "ArrowRight") show(cur + 1);
  });
  if (openBtn) openBtn.addEventListener("click", open);
})();
