<?php
// robots.txt (served as /robots.txt via .htaccess). Note: crawlers only read it at the domain root.
require __DIR__ . '/includes/helpers.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nDisallow: " . BASE_URL . "/admin/\nDisallow: " . BASE_URL . "/includes/\nDisallow: " . BASE_URL . "/config/\n\nSitemap: " . site_origin() . BASE_URL . "/sitemap.xml\n";
