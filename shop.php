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
$catName = $catSlug !== '' ? (string)fetch_val('SELECT name FROM categories WHERE slug=?', [$catSlug]) : '';
$filtered = $brandSlug !== '' || $catSlug !== '' || $q !== '';

$canonPath = ($catSlug !== '' && $brandSlug === '') ? '/shop.php?category=' . urlencode($catSlug) : (($brandSlug !== '' && $catSlug === '') ? '/shop.php?brand=' . urlencode($brandSlug) : '/shop.php');
$shopDesc = $catName ? 'Shop ' . $catName . ' at ' . site_name() . '. Compare prices and brands, then send us an enquiry.' : 'Browse all products at ' . site_name() . ': TVs, speakers, headphones, gaming and more.';
site_header($catName ?: 'Shop', 'shop', $shopDesc, ['canonical' => $canonPath, 'noindex' => $q !== '']);
render_banners('shop', true); ?>
<div class="wrap section">
  <div class="sechead"><div><span class="eyebrow"><?= (int)$total ?> product<?= $total === 1 ? '' : 's' ?></span><h1><?= e($catName ?: 'All products') ?><?= $q !== '' ? ' · “' . e($q) . '”' : '' ?></h1></div></div>
  <form method="get" class="filterbar">
    <?php if ($catSlug !== ''): ?><input type="hidden" name="category" value="<?= e($catSlug) ?>"><?php endif; ?>
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
    <select name="brand" data-autosubmit aria-label="Brand"><option value="">All brands</option>
      <?php foreach ($brands as $b): ?><option value="<?= e($b['slug']) ?>" <?= $brandSlug === $b['slug'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select>
    <select name="sort" data-autosubmit aria-label="Sort"><?php foreach (['new' => 'Newest first', 'low' => 'Price: low to high', 'high' => 'Price: high to low', 'name' => 'Name A–Z'] as $k => $l): ?><option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <noscript><button class="btn sm">Apply</button></noscript>
    <?php if ($filtered): ?><a class="chip" href="<?= url('/shop.php') ?>">Clear filters &times;</a><?php endif; ?>
  </form>
  <?php if ($items): ?><div class="grid"><?php foreach ($items as $p) echo product_card($p); ?></div><?= site_pager($page, $pages) ?>
  <?php else: ?><div class="emptybox"><h3>No products found</h3><p class="muted">Try a different search or clear the filters.</p><a class="btn" href="<?= url('/shop.php') ?>">View all products</a></div><?php endif; ?>
</div>
<?php site_footer();
