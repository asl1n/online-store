(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };

  // Responsive tables: copy header text into each cell as a label
  $$('table.table').forEach(function (t) {
    var heads = $$('thead th', t).map(function (h) { return h.textContent.trim(); });
    $$('tbody tr', t).forEach(function (tr) {
      $$(':scope > td', tr).forEach(function (td, i) {
        if (td.colSpan > 1 || td.hasAttribute('data-label')) return;
        if (heads[i]) td.setAttribute('data-label', heads[i]);
      });
    });
  });

  // Sidebar (mobile)
  $$('[data-side-toggle]').forEach(function (b) { b.addEventListener('click', function () { document.body.classList.toggle('side-open'); }); });
  $$('[data-side-close]').forEach(function (b) { b.addEventListener('click', function () { document.body.classList.remove('side-open'); }); });

  // User dropdown
  $$('[data-dropdown]').forEach(function (d) {
    var btn = $('.user-btn', d);
    btn.addEventListener('click', function (e) { e.stopPropagation(); var o = d.classList.toggle('open'); btn.setAttribute('aria-expanded', o); });
    document.addEventListener('click', function (e) { if (!d.contains(e.target)) { d.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') d.classList.remove('open'); });
  });

  // Dialogs
  $$('dialog.dlg').forEach(function (dlg) {
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    $$('[data-dlg-close]', dlg).forEach(function (b) { b.addEventListener('click', function () { dlg.close(); }); });
  });
  $$('[data-open-logout]').forEach(function (b) { b.addEventListener('click', function () { var d = $('#logoutDlg'); if (d) d.showModal(); }); });

  // Styled confirmation for any <form data-confirm="message">
  var cdlg = $('#confirmDlg'), pending = null;
  document.addEventListener('submit', function (e) {
    var f = e.target, msg = f.getAttribute && f.getAttribute('data-confirm');
    if (!msg || !cdlg) return;
    e.preventDefault(); pending = { form: f, sub: e.submitter };
    $('#cTitle').textContent = f.getAttribute('data-confirm-title') || 'Are you sure?';
    $('#cMsg').textContent = msg; cdlg.showModal();
  });
  var ok = $('#cOk');
  if (ok) ok.addEventListener('click', function () {
    if (!pending) return;
    var f = pending.form, s = pending.sub;
    if (s && s.name) { var h = document.createElement('input'); h.type = 'hidden'; h.name = s.name; h.value = s.value; f.appendChild(h); }
    cdlg.close(); f.submit();
  });

  // Live search: submits 1 second after you stop typing (Enter submits instantly)
  $$('[data-live-search]').forEach(function (f) {
    var i = $('input', f), t;
    if (i.value) { i.focus(); var n = i.value.length; i.setSelectionRange(n, n); }
    i.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { f.submit(); }, 1000); });
  });

  // Auto-hide success alerts
  $$('.alert.ok').forEach(function (a) { setTimeout(function () { a.style.display = 'none'; }, 5000); });
})();
