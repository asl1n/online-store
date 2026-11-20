<?php
require_once __DIR__ . '/auth.php';

function initials(string $n): string {
    $p = preg_split('/\s+/', trim($n)); $i = mb_strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[1] ?? '', 0, 1));
    return $i ?: 'A';
}
/** Badge text: 1-99 as is, anything bigger as 99+ so it never grows past 3 characters. */
function badge_count(int $n): string { return $n > 99 ? '99+' : (string)$n; }
function go(string $url): never { header('Location: ' . $url); exit; }

// ---------- shared UI components ----------
function ui_icon_plus(): string { return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>'; }
function ui_icon_back(): string { return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>'; }
/** $o keys: error, help, half, req, for */
function ui_field(string $label, string $control, array $o = []): string {
    $cls = 'field' . (empty($o['half']) ? ' span2' : '') . (!empty($o['error']) ? ' has-error' : '');
    return '<div class="' . $cls . '"><label' . (!empty($o['for']) ? ' for="' . e($o['for']) . '"' : '') . '>' . e($label) . (!empty($o['req']) ? ' <i class="req">*</i>' : '') . '</label>'
        . $control . (!empty($o['help']) ? '<small class="help">' . e($o['help']) . '</small>' : '')
        . (!empty($o['error']) ? '<div class="err">' . e($o['error']) . '</div>' : '') . '</div>';
}
function ui_input(string $name, $value, array $a = []): string {
    $a += ['type' => 'text']; $attrs = '';
    foreach ($a as $k => $v) { if ($v === false || $v === null) continue; $attrs .= ' ' . e($k) . ($v === true ? '' : '="' . e($v) . '"'); }
    return '<input id="' . e($name) . '" name="' . e($name) . '" value="' . e($value) . '"' . $attrs . '>';
}
function ui_textarea(string $name, $value, array $a = []): string {
    $attrs = ''; foreach ($a as $k => $v) { if ($v === false || $v === null) continue; $attrs .= ' ' . e($k) . ($v === true ? '' : '="' . e($v) . '"'); }
    return '<textarea id="' . e($name) . '" name="' . e($name) . '"' . $attrs . '>' . e($value) . '</textarea>';
}
function ui_alert(string $type, string $msg): string { return '<div class="alert ' . e($type) . '">' . e($msg) . '</div>'; }
function ui_errors_summary(array $errors): string {
    if (!empty($errors['_form'])) return ui_alert('err', $errors['_form']);
    $n = count($errors);
    return $n ? ui_alert('err', "Please fix the $n error" . ($n > 1 ? 's' : '') . ' highlighted below.') : '';
}

function admin_header(string $title, string $active = '', string $actions = ''): void {
    $a = require_admin();
    $unread = (int) fetch_val('SELECT COUNT(*) FROM contact_messages WHERE is_read=0');
    $groups = [
        'Overview' => [['index', 'Dashboard', 'index.php']],
        'Catalog'  => [['brands', 'Brands', 'brands.php'], ['categories', 'Categories', 'categories.php'], ['products', 'Products', 'products.php']],
        'Content'  => [['blogs', 'Blogs', 'blogs.php'], ['banners', 'Banners', 'banners.php'], ['messages', 'Messages', 'messages.php', $unread]],
        'System'   => [['activity', 'Activity Log', 'activity.php']],
    ];
    if ($a['role'] === 'super_admin') { $groups['System'][] = ['admins', 'Admins', 'admins.php']; $groups['System'][] = ['settings', 'Settings', 'settings.php']; }
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> – <?= e(site_name()) ?> Admin</title><?= favicon_tag() ?>
<meta name="robots" content="noindex,nofollow"><?php if (!empty($GLOBALS['need_editor'])): ?><link rel="stylesheet" href="<?= asset('/assets/vendor/ckeditor5/ckeditor5.css') ?>"><?php endif; ?><link rel="stylesheet" href="<?= asset('/assets/admin.css') ?>"></head><body class="admin">
<div class="shell">
<aside class="side" id="side">
  <a class="brand" href="<?= BASE_URL ?>/admin/index.php"><?= e(mb_strtoupper(site_name())) ?><small>ADMIN PANEL</small></a>
  <nav>
  <?php foreach ($groups as $g => $items): ?><div class="navgroup"><?= e($g) ?></div>
    <?php foreach ($items as $it): ?><a href="<?= BASE_URL ?>/admin/<?= $it[2] ?>" class="<?= $it[0] === $active ? 'on' : '' ?>"><?= e($it[1]) ?><?php if (!empty($it[3])): ?><span class="badge" aria-label="<?= (int)$it[3] ?> unread"><?= badge_count((int)$it[3]) ?></span><?php endif; ?></a><?php endforeach; ?>
  <?php endforeach; ?>
  </nav>
</aside>
<div class="scrim" data-side-close></div>
<div class="main">
<header class="topbar">
  <button class="icon-btn menu" type="button" data-side-toggle aria-label="Open menu">&#9776;</button>
  <div class="crumbs">Admin <span>/</span> <b><?= e($title) ?></b></div>
  <div class="top-right">
    <a class="icon-link<?= $unread ? ' has-new' : '' ?>" href="<?= BASE_URL ?>/admin/messages.php" aria-label="<?= $unread ? (int)$unread . ' unread messages' : 'Messages' ?>" title="<?= $unread ? (int)$unread . ' unread' : 'Messages' ?>"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg><?php if ($unread): ?><span class="dot<?= $unread > 99 ? ' wide' : ($unread > 9 ? ' two' : '') ?>" aria-hidden="true"><?= badge_count($unread) ?></span><?php endif; ?></a>
    <a class="btn ghost sm hide-xs" href="<?= BASE_URL ?>/index.php" target="_blank" rel="noopener">View site &#8599;</a>
    <div class="user" data-dropdown>
      <button class="user-btn" type="button" aria-haspopup="true" aria-expanded="false">
        <span class="avatar"><?= e(initials($a['name'])) ?></span>
        <span class="who"><b><?= e($a['name']) ?></b><small><?= $a['role'] === 'super_admin' ? 'Super admin' : 'Admin' ?></small></span><span class="caret">&#9662;</span>
      </button>
      <div class="dropmenu" role="menu">
        <div class="dm-head"><b><?= e($a['name']) ?></b><small><?= e($a['email']) ?></small></div>
        <a href="<?= BASE_URL ?>/admin/account.php" role="menuitem">View profile</a>
        <button type="button" class="dm-logout" data-open-logout role="menuitem">Logout</button>
      </div>
    </div>
  </div>
</header>
<main class="content">
<div class="pagehead"><h1><?= e($title) ?></h1><div class="actions"><?= $actions ?></div></div>
<?php foreach (flashes() as [$t, $m]): ?><?= ui_alert($t === 'ok' ? 'ok' : 'err', $m) ?><?php endforeach;
}

function admin_footer(): void { ?>
</main></div></div>
<dialog id="logoutDlg" class="dlg"><form method="post" action="<?= BASE_URL ?>/admin/logout.php"><?= csrf_field() ?>
  <h3>Log out?</h3><p>You will need to sign in again to continue managing the site.</p>
  <div class="dlg-actions"><button type="button" class="btn ghost" data-dlg-close>Cancel</button><button class="btn danger">Yes, log out</button></div></form></dialog>
<dialog id="confirmDlg" class="dlg"><h3 id="cTitle">Are you sure?</h3><p id="cMsg"></p>
  <div class="dlg-actions"><button type="button" class="btn ghost" data-dlg-close>Cancel</button><button type="button" class="btn danger" id="cOk">Yes, continue</button></div></dialog>
<?php if (!empty($GLOBALS['need_editor'])): ?><script src="<?= asset('/assets/vendor/ckeditor5/ckeditor5.umd.js') ?>"></script><script src="<?= asset('/assets/editor-init.js') ?>"></script><?php endif; ?>
<script src="<?= asset('/assets/admin.js') ?>"></script></body></html>
<?php }

function pager(int $page, int $pages): string {
    if ($pages <= 1) return '';
    $qs = $_GET; $out = '<div class="pager">';
    if ($page > 1) { $qs['page'] = $page - 1; $out .= '<a href="?' . e(http_build_query($qs)) . '">&laquo;</a>'; }
    for ($i = 1; $i <= $pages; $i++) { $qs['page'] = $i; $out .= '<a class="' . ($i === $page ? 'on' : '') . '" href="?' . e(http_build_query($qs)) . '">' . $i . '</a>'; }
    if ($page < $pages) { $qs['page'] = $page + 1; $out .= '<a href="?' . e(http_build_query($qs)) . '">&raquo;</a>'; }
    return $out . '</div>';
}
