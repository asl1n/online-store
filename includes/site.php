<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/contact.php';

const PRODUCT_SELECT = 'SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name, c.slug AS category_slug
  FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id';

function url(string $path): string { return BASE_URL . pretty_path($path); }
function money($v): string { return 'Rs. ' . number_format((float)$v); }
/** Discount % on a product row (0 when none / column missing). */
function discount_pct(array $p): float { $d = (float)($p['discount_percent'] ?? 0); return ($d > 0 && $d < 100) ? $d : 0.0; }
/** Price the customer actually pays, rounded to a whole rupee. */
function final_price(array $p): float { $d = discount_pct($p); return $d > 0 ? round((float)$p['price'] * (1 - $d / 100)) : (float)$p['price']; }
function pct_label(float $d): string { return rtrim(rtrim(number_format($d, 2), '0'), '.') . '%'; }
/** Price block: crossed-out old price + new price when discounted. $big = product page version. */
function price_html(array $p, bool $big = false): string {
    $d = discount_pct($p);
    if ($d <= 0) return '<b class="price' . ($big ? ' xl' : '') . '">' . e(money($p['price'])) . '</b>';
    return '<span class="pricebox' . ($big ? ' big' : '') . '"><s class="was">' . e(money($p['price'])) . '</s><b class="price now' . ($big ? ' xl' : '') . '">' . e(money(final_price($p))) . '</b></span>';
}

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

/** Small inline icons used in the top bar, footer and contact page. */
function ico(string $n): string {
    static $p = [
        'pin'   => '<path d="M12 21s7-6.2 7-11.5a7 7 0 0 0-14 0C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'mail'  => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'back'  => '<path d="M19 12H5"/><path d="M11 6l-6 6 6 6"/>',
        'left'  => '<path d="M15 6l-6 6 6 6"/>',
        'right' => '<path d="M9 6l6 6-6 6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
    ];
    return '<svg class="ico" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$n] ?? '') . '</svg>';
}
/** "9801, 9802" -> separate tap-to-call links. */
function phone_links(string $raw): string {
    $o = [];
    foreach (preg_split('/\s*[,\/]\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $n) {
        $d = preg_replace('/[^0-9+]/', '', $n);
        $o[] = $d !== '' ? '<a href="tel:' . e($d) . '">' . e($n) . '</a>' : e($n);
    }
    return '<span class="nums">' . implode('<span class="sep">, </span>', $o) . '</span>';
}
function email_link(string $m): string { return '<a href="mailto:' . e($m) . '">' . e($m) . '</a>'; }

function product_card(array $p): string {
    $img = $p['image'] ? '<img src="' . e(upload_url($p['image'])) . '" alt="' . e($p['name']) . '" loading="lazy">' : '<div class="noimg">No image</div>';
    return '<a class="pcard" href="' . e(url('/product.php?slug=' . urlencode($p['slug']))) . '"><div class="pimg">' . $img
        . (!empty($p['brand_name']) ? '<span class="tag">' . e($p['brand_name']) . '</span>' : '')
        . (discount_pct($p) > 0 ? '<span class="off">-' . e(pct_label(discount_pct($p))) . '</span>' : '') . '</div>'
        . '<div class="pbody"><small>' . e($p['category_name'] ?? '') . '</small><h3>' . e($p['name']) . '</h3>'
        . '<div class="prow">' . price_html($p) . '<span class="go">&rarr;</span></div></div></a>';
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
    $cats = fetch_all('SELECT c.name,c.slug,COUNT(p.id) AS cnt FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.is_active=1 WHERE c.is_active=1 GROUP BY c.id ORDER BY c.sort_order, c.name');
    $nav = ['home' => ['Home', '/index.php'], 'shop' => ['Shop', '/shop.php'], 'blog' => ['Blog', '/blogs.php'], 'contact' => ['Contact', '/contact.php']];
    $name = site_name(); $full = $title === 'Home' ? $name : $title . ' | ' . $name;
    $curCat = $active === 'shop' ? (string)($_GET['category'] ?? '') : '';
    $phones = setting('phones'); $addr = setting('address'); $email = setting('email');
    $d = seo_trim($desc ?: setting('meta_description')); $origin = site_origin();
    $canon = isset($meta['canonical']) ? $origin . BASE_URL . pretty_path($meta['canonical']) : $origin . strtok((string)($_SERVER['REQUEST_URI'] ?? '/'), '?');
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
<link rel="stylesheet" href="<?= asset('/assets/site.css') ?>"></head><body>
<?php if ($phones || $addr || $email): ?><div class="strip"><div class="wrap">
  <span class="addr"><?php if ($addr): ?><?= ico('pin') ?><?= e($addr) ?><?php endif; ?></span>
  <div class="reach"><?php if ($phones): ?><span class="item"><?= ico('phone') ?><?= phone_links($phones) ?></span><?php endif; ?>
  <?php if ($email): ?><span class="item mail"><?= ico('mail') ?><?= email_link($email) ?></span><?php endif; ?></div>
</div></div><?php endif; ?>
<header class="top"><div class="wrap bar">
  <a class="logo" href="<?= url('/index.php') ?>"><?= logo_html('header_logo') ?></a>
  <form class="search" action="<?= url('/shop.php') ?>" method="get" role="search"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg><input name="q" placeholder="Search products…" maxlength="60" value="<?= e($active === 'shop' ? (string)($_GET['q'] ?? '') : '') ?>"><button class="btn sm">Search</button></form>
  <button class="burger" data-nav-toggle aria-label="Menu">&#9776;</button>
  <nav id="mainnav"><?php foreach ($nav as $k => [$l, $h]): ?><a class="<?= ($k === $active && !($k === 'shop' && $curCat !== '')) ? 'on' : '' ?>" href="<?= url($h) ?>"><?= $l ?></a>
<?php if ($k === 'shop' && $cats): ?><div class="navdrop<?= $curCat !== '' ? ' has-on' : '' ?>"><button type="button" data-drop aria-haspopup="true" aria-expanded="false">Categories <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg></button>
  <div class="droppanel" style="--cols:<?= count($cats) > 8 ? 2 : 1 ?>"><?php foreach ($cats as $c): ?><a class="<?= $curCat === $c['slug'] ? 'on' : '' ?>" href="<?= e(url('/shop.php?category=' . urlencode($c['slug']))) ?>"><span><?= e($c['name']) ?></span><small><?= (int)$c['cnt'] ?></small></a><?php endforeach; ?></div></div><?php endif; ?>
<?php endforeach; ?></nav>
</div>
</header>
<?php foreach (flashes() as [$t, $m]): ?><div class="wrap"><div class="flash <?= e($t) ?>"><?= e($m) ?></div></div><?php endforeach;
}

function site_footer(): void {
    $cats = fetch_all('SELECT name,slug FROM categories WHERE is_active=1 ORDER BY sort_order, name');
    $fb = setting('facebook_url'); $ig = setting('instagram_url'); $isurl = fn($u) => (bool)preg_match('~^https?://~i', $u);
    $pby = setting('powered_by'); $pbu = setting('powered_by_url');
    $copy = setting('copyright_text') ?: '© ' . date('Y') . ' ' . site_name() . '. All rights reserved.'; ?>
<footer><div class="wrap cols">
  <div class="fabout"><a class="logo" href="<?= url('/index.php') ?>"><?= logo_html('footer_logo') ?></a>
    <?php if (setting('footer_about')): ?><p><?= e(setting('footer_about')) ?></p><?php endif; ?>
    <?php if ($isurl($fb) || $isurl($ig)): ?><div class="social">
      <?php if ($isurl($fb)): ?><a href="<?= e($fb) ?>" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg><span>Facebook</span></a><?php endif; ?>
      <?php if ($isurl($ig)): ?><a href="<?= e($ig) ?>" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><path d="M17.5 6.5h.01"/></svg><span>Instagram</span></a><?php endif; ?></div><?php endif; ?></div>
  <div><h4>Explore</h4><a href="<?= url('/index.php') ?>">Home</a><a href="<?= url('/shop.php') ?>">Shop</a><a href="<?= url('/blogs.php') ?>">Blog</a><a href="<?= url('/contact.php') ?>">Contact</a></div>
  <?php if ($cats): ?><div class="fcats"><h4>Shop by type</h4><?php foreach ($cats as $c): ?><a href="<?= e(url('/shop.php?category=' . urlencode($c['slug']))) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div><?php endif; ?>
  <?php if (setting('phones') || setting('address') || setting('email')): ?><div class="fvisit"><h4>Visit &amp; call</h4><ul class="ilist">
    <?php if (setting('address')): ?><li><?= ico('pin') ?><span><?= e(setting('address')) ?></span></li><?php endif; ?>
    <?php if (setting('phones')): ?><li><?= ico('phone') ?><span><?= phone_links(setting('phones')) ?></span></li><?php endif; ?>
    <?php if (setting('email')): ?><li><?= ico('mail') ?><span><?= email_link(setting('email')) ?></span></li><?php endif; ?></ul></div><?php endif; ?>
</div><div class="copy"><div class="wrap copyrow<?= $pby === '' ? ' solo' : '' ?>"><span><?= e($copy) ?></span>
  <?php if ($pby !== ''): ?><span class="powered">Powered by <?= $isurl($pbu) ? '<a href="' . e($pbu) . '" target="_blank" rel="noopener noreferrer">' . e($pby) . '</a>' : e($pby) ?></span><?php endif; ?></div></div></footer>
<script src="<?= asset('/assets/site.js') ?>"></script></body></html>
<?php }
