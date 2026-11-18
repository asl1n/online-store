<?php
require __DIR__ . '/includes/site.php';
$p = fetch_one(PRODUCT_SELECT . ' WHERE p.is_active=1 AND p.slug=?', [(string)($_GET['slug'] ?? '')]);
if (!$p) not_found();
$pd = seo_trim($p['short_description'] ?: $p['description']); $canonPath = '/product.php?slug=' . urlencode($p['slug']); $origin = site_origin();
site_header($p['name'], 'shop', $pd, ['type' => 'product', 'image' => $p['image'], 'canonical' => $canonPath, 'jsonld' => array_filter([
    '@type' => 'Product', 'name' => $p['name'], 'description' => $pd, 'image' => $p['image'] ? [$origin . upload_url($p['image'])] : null,
    'brand' => $p['brand_name'] ? ['@type' => 'Brand', 'name' => $p['brand_name']] : null, 'category' => $p['category_name'] ?: null,
    'offers' => ['@type' => 'Offer', 'url' => $origin . BASE_URL . $canonPath, 'priceCurrency' => 'NPR', 'price' => number_format((float)$p['price'], 2, '.', '')]])]); ?>
<div class="wrap section"><p class="muted"><a href="<?= url('/shop.php') ?>">Shop</a><?php if ($p['category_name']): ?> / <a href="<?= e(url('/shop.php?category=' . urlencode($p['category_slug']))) ?>"><?= e($p['category_name']) ?></a><?php endif; ?></p>
<div class="detail"><div class="pimg big"><?= $p['image'] ? '<img src="' . e(upload_url($p['image'])) . '" alt="' . e($p['name']) . '">' : '<div class="noimg">No image</div>' ?></div>
<div><h1><?= e($p['name']) ?></h1>
<?php if ($p['brand_name']): ?><p class="muted">Brand: <a href="<?= e(url('/shop.php?brand=' . urlencode($p['brand_slug']))) ?>"><?= e($p['brand_name']) ?></a></p><?php endif; ?>
<p class="price xl"><?= e(money($p['price'])) ?></p>
<?php if ($p['short_description']): ?><p><?= e($p['short_description']) ?></p><?php endif; ?>
<p><a class="btn" href="<?= e(url('/contact.php?product=' . urlencode($p['slug']))) ?>">Enquire about this product</a></p>
<?php if (setting('phones')): ?><p class="muted">Or call us: <?= e(setting('phones')) ?></p><?php endif; ?></div></div>
<?php if ($p['description']): ?><h2 class="sec">Description</h2><div class="prose"><?= render_content($p['description']) ?></div><?php endif;
$rel = $p['category_slug'] ? fetch_all(PRODUCT_SELECT . ' WHERE p.is_active=1 AND p.category_id=? AND p.id<>? ORDER BY p.id DESC LIMIT 4', [$p['category_id'], $p['id']]) : [];
if ($rel): ?><h2 class="sec">Related products</h2><div class="grid g4"><?php foreach ($rel as $r) echo product_card($r); ?></div><?php endif; ?></div>
<?php site_footer();
