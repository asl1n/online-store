<?php
require __DIR__ . '/includes/site.php';
site_header('Home', 'home');
if (banners_for('home')) render_banners('home');
else echo '<section class="hero plain"><div class="wrap caption"><h2>Premium sound, screens &amp; gaming</h2><p>TVs, speakers, headphones, cameras and more.</p><a class="btn" href="' . url('/shop.php') . '">Shop now</a></div></section>';

$brands = fetch_all('SELECT name,slug,logo FROM brands WHERE is_active=1 AND logo IS NOT NULL ORDER BY sort_order, name');
if ($brands): ?><section class="wrap section"><h2 class="sec">Our Brands</h2><div class="brands">
<?php foreach ($brands as $b): ?><a href="<?= e(url('/shop.php?brand=' . urlencode($b['slug']))) ?>" title="<?= e($b['name']) ?>"><img src="<?= e(upload_url($b['logo'])) ?>" alt="<?= e($b['name']) ?>" loading="lazy"></a><?php endforeach; ?>
</div></section><?php endif;

$feat = fetch_all(PRODUCT_SELECT . ' WHERE p.is_active=1 AND p.is_featured=1 ORDER BY p.id DESC LIMIT 10');
if ($feat): ?><section class="wrap section"><h2 class="sec">Featured</h2><div class="grid"><?php foreach ($feat as $p) echo product_card($p); ?></div></section><?php endif;

foreach (fetch_all('SELECT id,name,slug FROM categories WHERE is_active=1 ORDER BY sort_order, name') as $c) {
    $ps = fetch_all(PRODUCT_SELECT . ' WHERE p.is_active=1 AND p.category_id=? ORDER BY p.id DESC LIMIT 5', [$c['id']]);
    if (!$ps) continue;
    echo '<section class="wrap section"><div class="sechead"><h2 class="sec">Our ' . e($c['name']) . ' Collection</h2><a href="' . e(url('/shop.php?category=' . urlencode($c['slug']))) . '">View all &rarr;</a></div><div class="grid">';
    foreach ($ps as $p) echo product_card($p);
    echo '</div></section>';
}

$posts = fetch_all('SELECT title,slug,excerpt,image,published_at FROM blogs WHERE is_published=1 ORDER BY published_at DESC, id DESC LIMIT 3');
if ($posts): ?><section class="wrap section"><div class="sechead"><h2 class="sec">From the Blog</h2><a href="<?= url('/blogs.php') ?>">All posts &rarr;</a></div><div class="grid g3">
<?php foreach ($posts as $b): ?><a class="pcard" href="<?= e(url('/blog.php?slug=' . urlencode($b['slug']))) ?>"><?php if ($b['image']): ?><div class="pimg"><img src="<?= e(upload_url($b['image'])) ?>" alt="" loading="lazy"></div><?php endif; ?>
<h3><?= e($b['title']) ?></h3><p class="muted"><?= e($b['excerpt']) ?></p></a><?php endforeach; ?></div></section><?php endif;
site_footer();
