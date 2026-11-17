<?php
require __DIR__ . '/includes/site.php';
$per = 9; $page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)fetch_val('SELECT COUNT(*) FROM blogs WHERE is_published=1'); $pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages);
$posts = fetch_all('SELECT title,slug,excerpt,image,published_at FROM blogs WHERE is_published=1 ORDER BY published_at DESC, id DESC LIMIT ' . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per));
site_header('Blog', 'blog'); render_banners('blog', true); ?>
<div class="wrap section"><h1>Blog</h1><?php if ($posts): ?><div class="grid g3">
<?php foreach ($posts as $b): ?><a class="pcard" href="<?= e(url('/blog.php?slug=' . urlencode($b['slug']))) ?>"><?php if ($b['image']): ?><div class="pimg"><img src="<?= e(upload_url($b['image'])) ?>" alt="" loading="lazy"></div><?php endif; ?>
<p class="muted"><?= e(substr((string)$b['published_at'], 0, 10)) ?></p><h3><?= e($b['title']) ?></h3><p class="muted"><?= e($b['excerpt']) ?></p></a><?php endforeach; ?></div><?= site_pager($page, $pages) ?>
<?php else: ?><p class="muted">No posts yet.</p><?php endif; ?></div><?php site_footer();
