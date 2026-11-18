<?php
require __DIR__ . '/includes/site.php';
$p = fetch_one(PRODUCT_SELECT . ' WHERE p.is_active=1 AND p.slug=?', [(string)($_GET['slug'] ?? '')]);
if (!$p) not_found();
$pd = seo_trim($p['short_description'] ?: $p['description']); $canonPath = pretty_path('/product.php?slug=' . urlencode($p['slug'])); $origin = site_origin();
site_header($p['name'], 'shop', $pd, ['type' => 'product', 'image' => $p['image'], 'canonical' => $canonPath, 'jsonld' => array_filter([
    '@type' => 'Product', 'name' => $p['name'], 'description' => $pd, 'image' => $p['image'] ? [$origin . upload_url($p['image'])] : null,
    'brand' => $p['brand_name'] ? ['@type' => 'Brand', 'name' => $p['brand_name']] : null, 'category' => $p['category_name'] ?: null,
    'offers' => ['@type' => 'Offer', 'url' => $origin . BASE_URL . $canonPath, 'priceCurrency' => 'NPR', 'price' => number_format((float)$p['price'], 2, '.', '')]])]); ?>
<div class="wrap section"><nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url('/shop.php') ?>">Shop</a><?php if ($p['category_name']): ?><?= ico('right') ?><a href="<?= e(url('/shop.php?category=' . urlencode($p['category_slug']))) ?>"><?= e($p['category_name']) ?></a><?php endif; ?><?= ico('right') ?><span aria-current="page"><?= e($p['name']) ?></span></nav>
<div class="detail pdp"><div class="pdp-media"><div class="pimg big"><?= $p['image'] ? '<img src="' . e(upload_url($p['image'])) . '" alt="' . e($p['name']) . '">' : '<div class="noimg">No image</div>' ?></div></div>
<div class="pdp-info"><h1><?= e($p['name']) ?></h1>
<?php if ($p['brand_name']): ?><div class="brandline"><span>Brand</span><b class="brandpill"><?= e($p['brand_name']) ?></b></div><?php endif; ?>
<p class="price xl"><?= e(money($p['price'])) ?></p>
<?php if ($p['short_description']): ?><p><?= e($p['short_description']) ?></p><?php endif; ?>
<p><a class="btn" href="<?= e(url('/contact.php?product=' . urlencode($p['slug']))) ?>">Enquire about this product</a></p>
<?php if (setting('phones')): ?><p class="muted">Or call us: <span class="inlinelinks"><?= phone_links(setting('phones')) ?></span></p><?php endif; ?>
<?php if ($p['description']): ?><div class="pdp-desc"><h2 class="sec">Description</h2><div class="prose"><?= render_content($p['description']) ?></div></div><?php endif; ?></div></div>
<?php
$rel = $p['category_slug'] ? fetch_all(PRODUCT_SELECT . ' WHERE p.is_active=1 AND p.category_id=? AND p.id<>? ORDER BY p.id DESC LIMIT 4', [$p['category_id'], $p['id']]) : [];
if ($rel): ?><section class="related"><h2 class="sec">Related products</h2><div class="grid"><?php foreach ($rel as $r) echo product_card($r); ?></div></section><?php endif; ?></div>
<?php site_footer();
