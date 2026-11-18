<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/contact.php';

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
        echo '<div class="slide' . ($i === 0 ? ' on' : '') . '" style="background-image:url(\'' . e(upload_url($b['image'])) . '\')"><div class="shade"></div><div class="caption">';
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
    return '<a class="pcard" href="' . e(url('/product.php?slug=' . urlencode($p['slug']))) . '"><div class="pimg">' . $img
        . (!empty($p['brand_name']) ? '<span class="tag">' . e($p['brand_name']) . '</span>' : '') . '</div>'
        . '<div class="pbody"><small>' . e($p['category_name'] ?? '') . '</small><h3>' . e($p['name']) . '</h3>'
        . '<div class="prow"><b class="price">' . e(money($p['price'])) . '</b><span class="go">&rarr;</span></div></div></a>';
}
function blog_card(array $b): string {
    return '<a class="pcard blog" href="' . e(url('/blog.php?slug=' . urlencode($b['slug']))) . '">'
        . ($b['image'] ? '<div class="pimg"><img src="' . e(upload_url($b['image'])) . '" alt="' . e($b['title']) . '" loading="lazy"></div>' : '')
        . '<div class="pbody"><small>' . e(substr((string)$b['published_at'], 0, 10)) . '</small><h3>' . e($b['title']) . '</h3>'
        . '<p class="muted">' . e($b['excerpt']) . '</p></div></a>';
}

function site_pager(int $page, int $pages): string {
    if ($pages <= 1) return '';
    $qs = $_GET; $o = '<div class="pager">';
    for ($i = 1; $i <= $pages; $i++) { $qs['page'] = $i; $o .= '<a class="' . ($i === $page ? 'on' : '') . '" href="?' . e(http_build_query($qs)) . '">' . $i . '</a>'; }
    return $o . '</div>';
}

function logo_html(string $key): string {
    $img = setting($key); $name = site_name();
    if ($img) return '<span class="logo-img"><img src="' . e(upload_url($img)) . '" alt="' . e($name) . '"></span>';
    $p = explode(' ', $name, 2);
    return '<span class="logo-text"><b>' . e($p[0]) . '</b>' . (isset($p[1]) ? '<i>' . e($p[1]) . '</i>' : '') . '</span>';
}

/** $meta keys: image (upload path), type, canonical (path after BASE_URL), noindex, jsonld (array). */
function site_header(string $title, string $active = '', string $desc = '', array $meta = []): void {
    $cats = fetch_all('SELECT name,slug FROM categories WHERE is_active=1 ORDER BY sort_order, name');
    $nav = ['home' => ['Home', '/index.php'], 'shop' => ['Shop', '/shop.php'], 'blog' => ['Blog', '/blogs.php'], 'contact' => ['Contact', '/contact.php']];
    $name = site_name(); $full = $title === 'Home' ? $name : $title . ' | ' . $name;
    $curCat = $active === 'shop' ? (string)($_GET['category'] ?? '') : '';
    $phones = setting('phones'); $addr = setting('address');
    $d = seo_trim($desc ?: setting('meta_description')); $origin = site_origin();
    $canon = isset($meta['canonical']) ? $origin . BASE_URL . $meta['canonical'] : $origin . strtok((string)($_SERVER['REQUEST_URI'] ?? '/'), '?');
    $img = $meta['image'] ?? setting('header_logo'); $ogimg = $img ? $origin . upload_url($img) : '';
    $ogTitle = $title === 'Home' ? $name : $title;
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($full) ?></title><meta name="description" content="<?= e($d) ?>"><?= favicon_tag() ?>
<link rel="canonical" href="<?= e($canon) ?>">
<?php if (!empty($meta['noindex'])): ?><meta name="robots" content="noindex,follow"><?php endif; ?>
<meta property="og:site_name" content="<?= e($name) ?>"><meta property="og:type" content="<?= e($meta['type'] ?? 'website') ?>"><meta property="og:title" content="<?= e($ogTitle) ?>">
<meta property="og:description" content="<?= e($d) ?>"><meta property="og:url" content="<?= e($canon) ?>"><?php if ($ogimg): ?><meta property="og:image" content="<?= e($ogimg) ?>"><?php endif; ?>
<meta name="twitter:card" content="<?= $ogimg ? 'summary_large_image' : 'summary' ?>"><meta name="twitter:title" content="<?= e($ogTitle) ?>"><meta name="twitter:description" content="<?= e($d) ?>">
<?php if (!empty($meta['jsonld'])): ?><script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org'] + $meta['jsonld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script><?php endif; ?>
<link rel="stylesheet" href="<?= url('/assets/site.css') ?>"></head><body>
<?php if ($phones || $addr): ?><div class="strip"><div class="wrap"><span><?= e($addr) ?></span><span><?= $phones ? 'Call ' . e($phones) : '' ?></span></div></div><?php endif; ?>
<header class="top"><div class="wrap bar">
  <a class="logo" href="<?= url('/index.php') ?>"><?= logo_html('header_logo') ?></a>
  <form class="search" action="<?= url('/shop.php') ?>" method="get" role="search"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg><input name="q" placeholder="Search products…" maxlength="60" value="<?= e($active === 'shop' ? (string)($_GET['q'] ?? '') : '') ?>"><button class="btn sm">Search</button></form>
  <button class="burger" data-nav-toggle aria-label="Menu">&#9776;</button>
  <nav id="mainnav"><?php foreach ($nav as $k => [$l, $h]): ?><a class="<?= $k === $active ? 'on' : '' ?>" href="<?= url($h) ?>"><?= $l ?></a><?php endforeach; ?></nav>
</div>
<?php if ($cats): ?><div class="wrap chips"><a class="chip <?= $active === 'shop' && $curCat === '' ? 'on' : '' ?>" href="<?= url('/shop.php') ?>">All products</a>
<?php foreach ($cats as $c): ?><a class="chip <?= $curCat === $c['slug'] ? 'on' : '' ?>" href="<?= e(url('/shop.php?category=' . urlencode($c['slug']))) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div><?php endif; ?>
</header>
<?php foreach (flashes() as [$t, $m]): ?><div class="wrap"><div class="flash <?= e($t) ?>"><?= e($m) ?></div></div><?php endforeach;
}

function site_footer(): void {
    $cats = fetch_all('SELECT name,slug FROM categories WHERE is_active=1 ORDER BY sort_order, name');
    $fb = setting('facebook_url'); $ig = setting('instagram_url'); $isurl = fn($u) => (bool)preg_match('~^https?://~i', $u);
    $copy = setting('copyright_text') ?: '© ' . date('Y') . ' ' . site_name() . '. All rights reserved.'; ?>
<footer><div class="wrap cols">
  <div class="fabout"><a class="logo" href="<?= url('/index.php') ?>"><?= logo_html('footer_logo') ?></a>
    <?php if (setting('footer_about')): ?><p><?= e(setting('footer_about')) ?></p><?php endif; ?>
    <?php if ($isurl($fb) || $isurl($ig)): ?><div class="social">
      <?php if ($isurl($fb)): ?><a href="<?= e($fb) ?>" target="_blank" rel="noopener noreferrer">Facebook</a><?php endif; ?>
      <?php if ($isurl($ig)): ?><a href="<?= e($ig) ?>" target="_blank" rel="noopener noreferrer">Instagram</a><?php endif; ?></div><?php endif; ?></div>
  <div><h4>Explore</h4><a href="<?= url('/index.php') ?>">Home</a><a href="<?= url('/shop.php') ?>">Shop</a><a href="<?= url('/blogs.php') ?>">Blog</a><a href="<?= url('/contact.php') ?>">Contact</a></div>
  <?php if ($cats): ?><div><h4>Shop by type</h4><?php foreach ($cats as $c): ?><a href="<?= e(url('/shop.php?category=' . urlencode($c['slug']))) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div><?php endif; ?>
  <?php if (setting('phones') || setting('address') || setting('email')): ?><div><h4>Visit &amp; call</h4>
    <?php if (setting('address')): ?><p><?= e(setting('address')) ?></p><?php endif; ?>
    <?php if (setting('phones')): ?><p><?= e(setting('phones')) ?></p><?php endif; ?>
    <?php if (setting('email')): ?><p><?= e(setting('email')) ?></p><?php endif; ?></div><?php endif; ?>
</div><div class="copy"><?= e($copy) ?></div></footer>
<script src="<?= url('/assets/site.js') ?>"></script></body></html>
<?php }
