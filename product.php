<?php
require __DIR__ . '/includes/site.php';
$p = fetch_one(PRODUCT_SELECT . ' WHERE p.is_active=1 AND p.slug=?', [(string)($_GET['slug'] ?? '')]);
if (!$p) not_found();
site_header($p['name'], 'shop', (string)$p['short_description']); ?>
<div class="wrap section"><p class="muted"><a href="<?= url('/shop.php') ?>">Shop</a><?php if ($p['category_name']): ?> / <a href="<?= e(url('/shop.php?category=' . urlencode($p['category_slug']))) ?>"><?= e($p['category_name']) ?></a><?php endif; ?></p>
<div class="detail"><div class="pimg big"><?= $p['image'] ? '<img src="' . e(upload_url($p['image'])) . '" alt="' . e($p['name']) . '">' : '<div class="noimg">No image</div>' ?></div>
<div><h1><?= e($p['name']) ?></h1>
<?php if ($p['brand_name']): ?><p class="muted">Brand: <a href="<?= e(url('/shop.php?brand=' . urlencode($p['brand_slug']))) ?>"><?= e($p['brand_name']) ?></a></p><?php endif; ?>
<p class="price xl"><?= e(money($p['price'])) ?></p>
<?php if ($p['short_description']): ?><p><?= e($p['short_description']) ?></p><?php endif; ?>
<p><a class="btn" href="<?= e(url('/contact.php?product=' . urlencode($p['slug']))) ?>">Enquire about this product</a></p>
<?php if (setting('phones')): ?><p class="muted">Or call us: <?= e(setting('phones')) ?></p><?php endif; ?></div></div>
<?php if ($p['description']): ?><h2 class="sec">Description</h2><div class="prose"><?= render_text($p['description']) ?></div><?php endif;
$rel = $p['category_slug'] ? fetch_all(PRODUCT_SELECT . ' WHERE p.is_active=1 AND p.category_id=? AND p.id<>? ORDER BY p.id DESC LIMIT 4', [$p['category_id'], $p['id']]) : [];
if ($rel): ?><h2 class="sec">Related products</h2><div class="grid g4"><?php foreach ($rel as $r) echo product_card($r); ?></div><?php endif; ?></div>
<?php site_footer();
