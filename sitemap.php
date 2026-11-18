<?php
// XML sitemap for search engines (served as /sitemap.xml via .htaccess)
require __DIR__ . '/includes/helpers.php';
header('Content-Type: application/xml; charset=utf-8');
$o = site_origin() . BASE_URL;
$urls = [[$o . '/', null], [$o . '/shop.php', null], [$o . '/blogs.php', null], [$o . '/contact.php', null]];
foreach (fetch_all('SELECT slug FROM categories WHERE is_active=1') as $r) $urls[] = [$o . '/shop.php?category=' . urlencode($r['slug']), null];
foreach (fetch_all('SELECT slug,updated_at FROM products WHERE is_active=1 ORDER BY id DESC LIMIT 5000') as $r) $urls[] = [$o . '/product.php?slug=' . urlencode($r['slug']), $r['updated_at']];
foreach (fetch_all('SELECT slug,updated_at FROM blogs WHERE is_published=1 ORDER BY id DESC LIMIT 5000') as $r) $urls[] = [$o . '/blog.php?slug=' . urlencode($r['slug']), $r['updated_at']];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc, $mod]) echo '<url><loc>' . e($loc) . '</loc>' . ($mod ? '<lastmod>' . e(date('c', strtotime($mod))) . '</lastmod>' : '') . "</url>\n";
echo '</urlset>';
