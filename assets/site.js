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
    nb.innerHTML = open ? "&#10005;" : "&#9776;";
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
