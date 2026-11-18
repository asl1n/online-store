<?php
require __DIR__ . '/includes/site.php';
site_header('Home', 'home');
if (banners_for('home')) render_banners('home');
else echo '<section class="hero plain"><div class="caption"><span class="eyebrow">Welcome to ' . e(site_name()) . '</span><h2>Sound, screens &amp; play — picked with care.</h2><p>TVs, speakers, headphones and gaming gear you will actually love.</p><a class="btn" href="' . url('/shop.php') . '">Start shopping</a></div></section>';

$cats = fetch_all('SELECT c.name,c.slug,COUNT(p.id) AS cnt FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.is_active=1 WHERE c.is_active=1 GROUP BY c.id ORDER BY c.sort_order, c.name');
if ($cats): ?><section class="wrap section"><div class="sechead"><div><span class="eyebrow">Browse</span><h2 class="sec">Shop by category</h2></div></div><div class="tiles">
<?php foreach ($cats as $c): ?><a class="tile" href="<?= e(url('/shop.php?category=' . urlencode($c['slug']))) ?>"><b><?= e($c['name']) ?></b><span><?= (int)$c['cnt'] ?> product<?= (int)$c['cnt'] === 1 ? '' : 's' ?> &rarr;</span></a><?php endforeach; ?></div></section><?php endif;

$feat = fetch_all(PRODUCT_SELECT . ' WHERE p.is_active=1 AND p.is_featured=1 ORDER BY p.id DESC LIMIT 12');
if ($feat): ?><section class="wrap section"><div class="sechead"><div><span class="eyebrow">Hand-picked</span><h2 class="sec">Featured</h2></div><a class="more" href="<?= url('/shop.php') ?>">See everything &rarr;</a></div>
<div class="rail"><?php foreach ($feat as $p) echo product_card($p); ?></div></section><?php endif;

$new = fetch_all(PRODUCT_SELECT . ' WHERE p.is_active=1 ORDER BY p.id DESC LIMIT 8');
if ($new): ?><section class="wrap section"><div class="sechead"><div><span class="eyebrow">Just landed</span><h2 class="sec">New arrivals</h2></div><a class="more" href="<?= url('/shop.php') ?>">Shop all &rarr;</a></div>
<div class="grid"><?php foreach ($new as $p) echo product_card($p); ?></div></section><?php endif;

$brands = fetch_all('SELECT name,slug,logo FROM brands WHERE is_active=1 AND logo IS NOT NULL ORDER BY sort_order, name');
if ($brands): ?><section class="wrap section"><div class="sechead"><div><span class="eyebrow">Trusted names</span><h2 class="sec">Brands we carry</h2></div></div><div class="brands">
<?php foreach ($brands as $b): ?><a href="<?= e(url('/shop.php?brand=' . urlencode($b['slug']))) ?>" title="<?= e($b['name']) ?>"><img src="<?= e(upload_url($b['logo'])) ?>" alt="<?= e($b['name']) ?>" loading="lazy"></a><?php endforeach; ?></div></section><?php endif;

$posts = fetch_all('SELECT title,slug,excerpt,image,published_at FROM blogs WHERE is_published=1 ORDER BY published_at DESC, id DESC LIMIT 3');
if ($posts): ?><section class="wrap section"><div class="sechead"><div><span class="eyebrow">Read</span><h2 class="sec">From the blog</h2></div><a class="more" href="<?= url('/blogs.php') ?>">All posts &rarr;</a></div>
<div class="grid g3"><?php foreach ($posts as $b) echo blog_card($b); ?></div></section><?php endif; ?>

<section class="wrap section"><div class="cta"><div><h2>Not sure what to pick?</h2><p>Tell us what you need and we will help you choose the right one.</p></div><a class="btn" href="<?= url('/contact.php') ?>">Talk to us</a></div></section>
<?php site_footer();
