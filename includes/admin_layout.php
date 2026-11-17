<?php
require_once __DIR__ . '/auth.php';

function admin_header(string $title, string $active = ''): void {
    $a = require_admin();
    $unread = (int) fetch_val('SELECT COUNT(*) FROM contact_messages WHERE is_read=0');
    $nav = [
        'index' => ['Dashboard', 'index.php'], 'brands' => ['Brands', 'brands.php'],
        'categories' => ['Categories', 'categories.php'], 'products' => ['Products', 'products.php'],
        'blogs' => ['Blogs', 'blogs.php'], 'banners' => ['Banners', 'banners.php'],
        'messages' => ['Messages' . ($unread ? " ($unread)" : ''), 'messages.php'],
        'activity' => ['Activity Log', 'activity.php'],
    ];
    if ($a['role'] === 'super_admin') $nav['admins'] = ['Admins', 'admins.php'];
    $nav['account'] = ['My Account', 'account.php'];
    ?>
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> – Allure Admin</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/admin.css"></head><body>
<aside class="side"><div class="brand">ALLURE<small>ADMIN</small></div><nav>
<?php foreach ($nav as $k => [$label, $href]): ?>
  <a href="<?= BASE_URL ?>/admin/<?= $href ?>" class="<?= $k === $active ? 'on' : '' ?>"><?= e($label) ?></a>
<?php endforeach; ?>
</nav>
<form method="post" action="<?= BASE_URL ?>/admin/logout.php"><?= csrf_field() ?><button class="link">Logout (<?= e($a['name']) ?>)</button></form>
</aside><main><h1><?= e($title) ?></h1>
<?php foreach (flashes() as [$t, $m]): ?><div class="flash <?= e($t) ?>"><?= e($m) ?></div><?php endforeach;
}
function admin_footer(): void { echo '</main><script src="' . BASE_URL . '/assets/admin.js"></script></body></html>'; }

function go(string $url): never { header('Location: ' . $url); exit; }

function pager(int $page, int $pages): string {
    if ($pages <= 1) return '';
    $qs = $_GET; $out = '<div class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        $qs['page'] = $i;
        $out .= '<a class="' . ($i === $page ? 'on' : '') . '" href="?' . e(http_build_query($qs)) . '">' . $i . '</a>';
    }
    return $out . '</div>';
}
