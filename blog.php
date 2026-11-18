<?php
require __DIR__ . '/includes/site.php';
$b = fetch_one('SELECT * FROM blogs WHERE is_published=1 AND slug=?', [(string)($_GET['slug'] ?? '')]);
if (!$b) not_found();
$desc = seo_trim($b['excerpt'] ?: $b['content']);
$canonPath = '/blog.php?slug=' . urlencode($b['slug']); $origin = site_origin();
site_header($b['title'], 'blog', $desc, ['type' => 'article', 'image' => $b['image'], 'canonical' => $canonPath, 'jsonld' => array_filter([
    '@type' => 'Article', 'headline' => $b['title'], 'description' => $desc,
    'image' => $b['image'] ? [$origin . upload_url($b['image'])] : null,
    'datePublished' => date('c', strtotime((string)($b['published_at'] ?: $b['created_at']))), 'dateModified' => date('c', strtotime((string)$b['updated_at'])),
    'author' => ['@type' => 'Organization', 'name' => site_name()], 'publisher' => ['@type' => 'Organization', 'name' => site_name()],
    'mainEntityOfPage' => $origin . BASE_URL . $canonPath])]); ?>
<article class="wrap section narrow"><p class="muted"><a href="<?= url('/blogs.php') ?>">&larr; All posts</a></p>
<h1><?= e($b['title']) ?></h1><p class="muted"><?= e(substr((string)$b['published_at'], 0, 10)) ?></p>
<?php if ($b['image']): ?><div class="pimg big"><img src="<?= e(upload_url($b['image'])) ?>" alt="<?= e($b['title']) ?>"></div><?php endif; ?>
<div class="prose"><?= render_text($b['content']) ?></div></article><?php site_footer();
