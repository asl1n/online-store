<?php
require __DIR__ . '/includes/site.php';
$b = fetch_one('SELECT * FROM blogs WHERE is_published=1 AND slug=?', [(string)($_GET['slug'] ?? '')]);
if (!$b) not_found();
site_header($b['title'], 'blog', (string)$b['excerpt']); ?>
<article class="wrap section narrow"><p class="muted"><a href="<?= url('/blogs.php') ?>">&larr; All posts</a></p>
<h1><?= e($b['title']) ?></h1><p class="muted"><?= e(substr((string)$b['published_at'], 0, 10)) ?></p>
<?php if ($b['image']): ?><div class="pimg big"><img src="<?= e(upload_url($b['image'])) ?>" alt=""></div><?php endif; ?>
<div class="prose"><?= render_text($b['content']) ?></div></article><?php site_footer();
