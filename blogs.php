<?php
require __DIR__ . '/includes/site.php';
$per = 9; $page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)fetch_val('SELECT COUNT(*) FROM blogs WHERE is_published=1'); $pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages);
$posts = fetch_all('SELECT title,slug,excerpt,image,published_at FROM blogs WHERE is_published=1 ORDER BY published_at DESC, id DESC LIMIT ' . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per));
site_header('Blog', 'blog'); render_banners('blog', true); ?>
<div class="wrap section"><div class="sechead"><div><span class="eyebrow">Stories &amp; guides</span><h1>Blog</h1></div></div>
<?php if ($posts): ?><div class="grid g3"><?php foreach ($posts as $b) echo blog_card($b); ?></div><?= site_pager($page, $pages) ?>
<?php else: ?><div class="emptybox"><h3>No posts yet</h3><p class="muted">Check back soon.</p></div><?php endif; ?></div>
<?php site_footer();
