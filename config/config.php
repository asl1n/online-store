<?php
// ---------------------------------------------------------------
// Default settings = LOCAL (XAMPP). Works out of the box.
// On the live server, create config/config.local.php (see
// config.local.example.php). If that file exists it overrides the
// defaults below. NEVER overwrite it when you re-upload the site.
// ---------------------------------------------------------------
$cfg = [
    'DB_HOST'    => '127.0.0.1',
    'DB_NAME'    => 'store_db',
    'DB_USER'    => 'root',
    'DB_PASS'    => '',
    // Folder path of the site in the browser, no trailing slash. '' if at root.
    'BASE_URL'   => '/store',
    // Full domain for canonical links / sitemap, e.g. 'https://yourdomain.com'. '' = auto-detect.
    'SITE_URL'   => '',
    'APP_DEBUG'  => true,   // false in production
];

if (is_file(__DIR__ . '/config.local.php')) {
    $cfg = array_merge($cfg, (array) require __DIR__ . '/config.local.php');
}

define('DB_HOST', $cfg['DB_HOST']);
define('DB_NAME', $cfg['DB_NAME']);
define('DB_USER', $cfg['DB_USER']);
define('DB_PASS', $cfg['DB_PASS']);
define('BASE_URL', rtrim($cfg['BASE_URL'], '/'));
define('APP_DEBUG', (bool) $cfg['APP_DEBUG']);
if ($cfg['SITE_URL'] !== '') define('SITE_URL', $cfg['SITE_URL']);

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('MAX_UPLOAD_MB', 3);
