<?php
require __DIR__ . '/includes/site.php';
$p = fetch_one(PRODUCT_SELECT . ' WHERE p.is_active=1 AND p.slug=?', [(string)($_GET['slug'] ?? '')]);
if (!$p) not_found();
$imgs = $p['image'] ? [$p['image']] : [];
foreach (fetch_all('SELECT image FROM product_images WHERE product_id=? ORDER BY sort_order, id', [$p['id']]) as $g) $imgs[] = $g['image'];
$pd = seo_trim($p['short_description'] ?: $p['description']); $canonPath = pretty_path('/product.php?slug=' . urlencode($p['slug'])); $origin = site_origin();
site_header($p['name'], 'shop', $pd, ['type' => 'product', 'image' => $p['image'], 'canonical' => $canonPath, 'jsonld' => array_filter([
    '@type' => 'Product', 'name' => $p['name'], 'description' => $pd, 'image' => $imgs ? array_map(fn($i) => $origin . upload_url($i), $imgs) : null,
    'brand' => $p['brand_name'] ? ['@type' => 'Brand', 'name' => $p['brand_name']] : null, 'category' => $p['category_name'] ?: null,
    'offers' => ['@type' => 'Offer', 'url' => $origin . BASE_URL . $canonPath, 'priceCurrency' => 'NPR', 'price' => number_format(final_price($p), 2, '.', '')]])]); ?>
<div class="wrap section"><nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url('/shop.php') ?>">Shop</a><?php if ($p['category_name']): ?><?= ico('right') ?><a href="<?= e(url('/shop.php?category=' . urlencode($p['category_slug']))) ?>"><?= e($p['category_name']) ?></a><?php endif; ?><?= ico('right') ?><span aria-current="page"><?= e($p['name']) ?></span></nav>
<div class="detail pdp"><div class="pdp-media"<?= $imgs ? ' data-gallery' : '' ?>>
<?php if ($imgs): ?>
  <button type="button" class="pimg big gmain" data-gallery-open aria-label="Open image viewer"><?php if (discount_pct($p) > 0): ?><span class="off">-<?= e(pct_label(discount_pct($p))) ?></span><?php endif; ?><img src="<?= e(upload_url($imgs[0])) ?>" alt="<?= e($p['name']) ?>" data-gallery-main><span class="zoomhint" aria-hidden="true"><?= ico('search') ?></span></button>
  <?php if (count($imgs) > 1): ?><div class="gthumbs" role="list"><?php foreach ($imgs as $i => $im): ?><button type="button" role="listitem" class="gthumb<?= $i === 0 ? ' active' : '' ?>" data-gallery-thumb data-full="<?= e(upload_url($im)) ?>" aria-label="Show image <?= $i + 1 ?> of <?= count($imgs) ?>"><img src="<?= e(upload_url($im)) ?>" alt="" loading="lazy"></button><?php endforeach; ?></div><?php endif; ?>
<?php else: ?><div class="pimg big"><div class="noimg">No image</div></div><?php endif; ?></div>
<div class="pdp-info"><h1><?= e($p['name']) ?></h1>
<?php if ($p['brand_name']): ?><div class="brandline"><span>Brand</span><b class="brandpill"><?= e($p['brand_name']) ?></b></div><?php endif; ?>
<div class="pdp-price"><?= price_html($p, true) ?><?php if (discount_pct($p) > 0): ?><span class="save">Save <?= e(money((float)$p['price'] - final_price($p))) ?> &middot; <?= e(pct_label(discount_pct($p))) ?> off</span><?php endif; ?></div>
<?php if ($p['short_description']): ?><p><?= e($p['short_description']) ?></p><?php endif; ?>
<p><a class="btn" href="<?= e(url('/contact.php?product=' . urlencode($p['slug']))) ?>">Enquire about this product</a></p>
<?php if (setting('phones')): ?><p class="muted">Or call us: <span class="inlinelinks"><?= phone_links(setting('phones')) ?></span></p><?php endif; ?>
<?php if ($p['description']): ?><div class="pdp-desc"><h2 class="sec">Description</h2><div class="prose"><?= render_content($p['description']) ?></div></div><?php endif; ?></div></div>
<?php
$rel = $p['category_slug'] ? fetch_all(PRODUCT_SELECT . ' WHERE p.is_active=1 AND p.category_id=? AND p.id<>? ORDER BY p.id DESC LIMIT 8', [$p['category_id'], $p['id']]) : [];
if ($rel): ?><section class="related"><h2 class="sec">Related products</h2><div class="railwrap" data-rail><button type="button" class="railbtn" data-rail-prev aria-label="Previous products"><?= ico('left') ?></button><div class="rail" data-rail-track><?php foreach ($rel as $r) echo product_card($r); ?></div><button type="button" class="railbtn" data-rail-next aria-label="Next products"><?= ico('right') ?></button></div></section><?php endif; ?></div>
<?php site_footer();
