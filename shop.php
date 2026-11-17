<?php
require __DIR__ . '/includes/site.php';
$brandSlug = trim((string)($_GET['brand'] ?? '')); $catSlug = trim((string)($_GET['category'] ?? ''));
$q = trim((string)($_GET['q'] ?? '')); $sort = (string)($_GET['sort'] ?? 'new');
$orders = ['new' => 'p.id DESC', 'low' => 'p.price ASC', 'high' => 'p.price DESC', 'name' => 'p.name ASC'];
$order = $orders[$sort] ?? $orders['new'];

$where = ['p.is_active=1']; $params = [];
if ($brandSlug !== '') { $where[] = 'b.slug=?'; $params[] = $brandSlug; }
if ($catSlug !== '')   { $where[] = 'c.slug=?'; $params[] = $catSlug; }
if ($q !== '')         { $where[] = '(p.name LIKE ? OR b.name LIKE ? OR c.name LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
$W = 'WHERE ' . implode(' AND ', $where);
$from = 'FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id';

$per = 12; $page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)fetch_val("SELECT COUNT(*) $from $W", $params);
$pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages);
$items = fetch_all(PRODUCT_SELECT . " $W ORDER BY $order LIMIT " . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per), $params);

$brands = fetch_all('SELECT name,slug FROM brands WHERE is_active=1 ORDER BY sort_order, name');
$cats = fetch_all('SELECT name,slug FROM categories WHERE is_active=1 ORDER BY sort_order, name');
function shop_url(array $o): string {
    $qs = array_filter(array_merge(['brand' => $_GET['brand'] ?? '', 'category' => $_GET['category'] ?? '', 'q' => $_GET['q'] ?? '', 'sort' => $_GET['sort'] ?? ''], $o), fn($v) => $v !== '' && $v !== null);
    return url('/shop.php') . ($qs ? '?' . http_build_query($qs) : '');
}
site_header('Shop', 'shop');
render_banners('shop', true); ?>
<div class="wrap section shop">
<aside class="filters">
  <h4>Category</h4><a class="<?= $catSlug === '' ? 'on' : '' ?>" href="<?= e(shop_url(['category' => ''])) ?>">All</a>
  <?php foreach ($cats as $c): ?><a class="<?= $catSlug === $c['slug'] ? 'on' : '' ?>" href="<?= e(shop_url(['category' => $c['slug']])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
  <h4>Brand</h4><a class="<?= $brandSlug === '' ? 'on' : '' ?>" href="<?= e(shop_url(['brand' => ''])) ?>">All</a>
  <?php foreach ($brands as $b): ?><a class="<?= $brandSlug === $b['slug'] ? 'on' : '' ?>" href="<?= e(shop_url(['brand' => $b['slug']])) ?>"><?= e($b['name']) ?></a><?php endforeach; ?>
</aside>
<div><div class="sechead"><span class="muted"><?= (int)$total ?> product<?= $total === 1 ? '' : 's' ?><?= $q !== '' ? ' for “' . e($q) . '”' : '' ?></span>
  <form method="get"><?php foreach (['brand' => $brandSlug, 'category' => $catSlug, 'q' => $q] as $k => $v) if ($v !== '') echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">'; ?>
  <select name="sort" data-autosubmit><?php foreach (['new' => 'Newest', 'low' => 'Price: low to high', 'high' => 'Price: high to low', 'name' => 'Name'] as $k => $l): ?><option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select><noscript><button>Go</button></noscript></form></div>
  <?php if ($items): ?><div class="grid g4"><?php foreach ($items as $p) echo product_card($p); ?></div><?= site_pager($page, $pages) ?>
  <?php else: ?><p class="muted">No products found.</p><?php endif; ?></div></div>
<?php site_footer();
