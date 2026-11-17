document.querySelectorAll('[data-slider]').forEach(function (el) {
  var slides = el.querySelectorAll('.slide'), dots = el.querySelectorAll('.dots span'), i = 0, t;
  if (slides.length < 2) return;
  function show(n) {
    slides[i].classList.remove('on'); if (dots[i]) dots[i].classList.remove('on');
    i = (n + slides.length) % slides.length;
    slides[i].classList.add('on'); if (dots[i]) dots[i].classList.add('on');
  }
  function auto() { clearInterval(t); t = setInterval(function () { show(i + 1); }, 5000); }
  el.querySelector('[data-next]').addEventListener('click', function () { show(i + 1); auto(); });
  el.querySelector('[data-prev]').addEventListener('click', function () { show(i - 1); auto(); });
  auto();
});
var nb = document.querySelector('[data-nav-toggle]');
if (nb) nb.addEventListener('click', function () { document.getElementById('mainnav').classList.toggle('open'); });
document.querySelectorAll('[data-autosubmit]').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });
