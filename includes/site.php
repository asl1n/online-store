<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/contact.php';

const SITE_NAME = 'Allure International';
const SITE_PHONES = '9802055500, 9802055501';
const SITE_ADDRESS = 'Jawalakhel, Lalitpur';
const SITE_EMAIL = 'allureinternationalnp@gmail.com';
const PRODUCT_SELECT = 'SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name, c.slug AS category_slug
  FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id';

function url(string $path): string { return BASE_URL . $path; }
function money($v): string { return 'Rs. ' . number_format((float)$v); }

/** Admin-entered links: allow /path or http(s):// only. Blocks javascript: and // tricks. */
function safe_link(?string $l): string {
    $l = trim((string)$l);
    if (preg_match('~^https?://~i', $l)) return $l;
    if ($l !== '' && $l[0] === '/' && !str_starts_with($l, '//')) return BASE_URL . $l;
    return '';
}
/** Plain text -> safe HTML paragraphs. */
function render_text(?string $s): string {
    $out = '';
    foreach (preg_split('/\n\s*\n/', trim((string)$s)) as $p) if (trim($p) !== '') $out .= '<p>' . nl2br(e(trim($p))) . '</p>';
    return $out;
}
function not_found(): never {
    http_response_code(404);
    site_header('Not found');
    echo '<div class="wrap section"><h1>Page not found</h1><p><a class="btn" href="' . url('/index.php') . '">Back home</a></p></div>';
    site_footer(); exit;
}

function banners_for(string $page): array {
    return fetch_all('SELECT * FROM banners WHERE page=? AND is_active=1 ORDER BY sort_order, id', [$page]);
}
function render_banners(string $page, bool $small = false): void {
    $bs = banners_for($page);
    if (!$bs) return;
    echo '<section class="hero' . ($small ? ' small' : '') . '" data-slider>';
    foreach ($bs as $i => $b) {
        $link = safe_link($b['button_link']);
        echo '<div class="slide' . ($i === 0 ? ' on' : '') . '" style="background-image:url(\'' . e(upload_url($b['image'])) . '\')"><div class="shade"></div><div class="wrap caption">';
        if ($b['title']) echo '<h2>' . e($b['title']) . '</h2>';
        if ($b['subtitle']) echo '<p>' . e($b['subtitle']) . '</p>';
        if ($b['button_text'] && $link) echo '<a class="btn" href="' . e($link) . '">' . e($b['button_text']) . '</a>';
        echo '</div></div>';
    }
    if (count($bs) > 1) {
        echo '<button class="arrow prev" data-prev aria-label="Previous">&#10094;</button><button class="arrow next" data-next aria-label="Next">&#10095;</button><div class="dots">';
        foreach ($bs as $i => $_) echo '<span class="' . ($i === 0 ? 'on' : '') . '"></span>';
        echo '</div>';
    }
    echo '</section>';
}

function product_card(array $p): string {
    $img = $p['image'] ? '<img src="' . e(upload_url($p['image'])) . '" alt="' . e($p['name']) . '" loading="lazy">' : '<div class="noimg">No image</div>';
    return '<a class="pcard" href="' . e(url('/product.php?slug=' . urlencode($p['slug']))) . '"><div class="pimg">' . $img . '</div>'
        . '<div class="pmeta"><span>' . e($p['category_name'] ?? '') . '</span><span>' . e($p['brand_name'] ?? '') . '</span></div>'
        . '<h3>' . e($p['name']) . '</h3><b class="price">' . e(money($p['price'])) . '</b></a>';
}

function site_pager(int $page, int $pages): string {
    if ($pages <= 1) return '';
    $qs = $_GET; $o = '<div class="pager">';
    for ($i = 1; $i <= $pages; $i++) { $qs['page'] = $i; $o .= '<a class="' . ($i === $page ? 'on' : '') . '" href="?' . e(http_build_query($qs)) . '">' . $i . '</a>'; }
    return $o . '</div>';
}

function site_header(string $title, string $active = '', string $desc = ''): void {
    $cats = fetch_all('SELECT name,slug FROM categories WHERE is_active=1 ORDER BY sort_order, name');
    $nav = ['home' => ['Home', '/index.php'], 'shop' => ['Shop', '/shop.php'], 'blog' => ['Blog', '/blogs.php'], 'contact' => ['Contact', '/contact.php']];
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= SITE_NAME ?></title><meta name="description" content="<?= e($desc ?: 'Premium TVs, speakers, headphones, gaming and audio gear in Nepal.') ?>">
<link rel="stylesheet" href="<?= url('/assets/site.css') ?>"></head><body>
<header class="top"><div class="wrap bar">
  <a class="logo" href="<?= url('/index.php') ?>">ALLURE<small>INTERNATIONAL</small></a>
  <div class="dd"><button class="btn sm" type="button">All Categories &#9662;</button><div class="menu">
    <?php foreach ($cats as $c): ?><a href="<?= e(url('/shop.php?category=' . urlencode($c['slug']))) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div></div>
  <button class="burger" data-nav-toggle aria-label="Menu">&#9776;</button>
  <nav id="mainnav"><?php foreach ($nav as $k => [$l, $h]): ?><a class="<?= $k === $active ? 'on' : '' ?>" href="<?= url($h) ?>"><?= $l ?></a><?php endforeach; ?></nav>
  <form class="search" action="<?= url('/shop.php') ?>" method="get"><input name="q" placeholder="Search products…" maxlength="60"><button class="btn sm">Search</button></form>
</div></header>
<?php foreach (flashes() as [$t, $m]): ?><div class="wrap"><div class="flash <?= e($t) ?>"><?= e($m) ?></div></div><?php endforeach;
}

function site_footer(): void {
    $cats = fetch_all('SELECT name,slug FROM categories WHERE is_active=1 ORDER BY sort_order, name'); ?>
<footer><div class="wrap cols">
  <div><div class="logo light">ALLURE<small>INTERNATIONAL</small></div>
    <p>Phone: <?= e(SITE_PHONES) ?></p><p>Address: <?= e(SITE_ADDRESS) ?></p><p>Email: <?= e(SITE_EMAIL) ?></p></div>
  <div><h4>Pages</h4><a href="<?= url('/index.php') ?>">Home</a><a href="<?= url('/shop.php') ?>">Shop</a><a href="<?= url('/blogs.php') ?>">Blog</a><a href="<?= url('/contact.php') ?>">Contact</a></div>
  <div><h4>Categories</h4><?php foreach ($cats as $c): ?><a href="<?= e(url('/shop.php?category=' . urlencode($c['slug']))) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div>
  <div><h4>Staff</h4><a href="<?= url('/admin/login.php') ?>">Admin login</a></div>
</div><div class="copy">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</div></footer>
<script src="<?= url('/assets/site.js') ?>"></script></body></html>
<?php }
