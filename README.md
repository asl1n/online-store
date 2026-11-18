# Aslin Store - Built using Php

Requirements: PHP 8.1+ (pdo_mysql, fileinfo) and MySQL/MariaDB. Easiest: XAMPP.

## Set up Guide

1. Copy the `online-store` folder into `C:\xampp\htdocs\` (Linux: /var/www/html/).
2. Start Apache + MySQL in XAMPP.
3. Open http://localhost/phpmyadmin and create a database named `store_db` (utf8mb4_general_ci).
4. Edit `config/config.php` (DB user/pass; BASE_URL = '/store').
5. Visit http://localhost/store/install.php and create your super admin.
6. Delete `install.php`.

## SEO

- Every public page has a unique <title>, meta description, canonical URL, Open Graph + Twitter tags. Blog posts use the blog title and the short description; products add Product structured data, blog posts add Article data.
- /sitemap.xml and /robots.txt are generated automatically (needs Apache mod_rewrite, on by default in XAMPP).
- In production add this line to config/config.php so canonical links always use your real domain: const SITE_URL = 'https://yourdomain.com';
- Admin pages send noindex.

## Security notes

- All queries use real prepared statements (PDO, emulation off). Never concatenate user input into SQL.
- All output goes through e() (HTML escaping) -> stored XSS protection for contact messages, blogs, etc.
- Contact form: CSRF token, honeypot, minimum-fill-time, per-IP rate limit, length/format validation, link-spam limit.
- Behind a proxy/Cloudflare? Use the real client IP header instead of REMOTE_ADDR in rate limiting.
- Set APP_DEBUG=false and use HTTPS before going live.
