# Allure International - Phase 1 (Backend foundation)

Requirements: PHP 8.1+ (pdo_mysql, fileinfo) and MySQL/MariaDB. Easiest: XAMPP.

1. Copy the `allure` folder into `C:\xampp\htdocs\` (Linux: /var/www/html/).
2. Start Apache + MySQL in XAMPP.
3. Open http://localhost/phpmyadmin and create a database named `allure_db` (utf8mb4_general_ci).
4. Edit `config/config.php` (DB user/pass; BASE_URL = '/allure').
5. Visit http://localhost/allure/install.php and create your super admin.
6. Delete `install.php`.

## Security notes

- All queries use real prepared statements (PDO, emulation off). Never concatenate user input into SQL.
- All output goes through e() (HTML escaping) -> stored XSS protection for contact messages, blogs, etc.
- Contact form: CSRF token, honeypot, minimum-fill-time, per-IP rate limit, length/format validation, link-spam limit.
- Behind a proxy/Cloudflare? Use the real client IP header instead of REMOTE_ADDR in rate limiting.
- Set APP_DEBUG=false and use HTTPS before going live.

## Phase 3 - public site

Pages: index.php (home), shop.php, product.php, blogs.php, blog.php, contact.php.
Everything is driven by the admin panel. Contact details/footer text: edit the constants at the top of includes/site.php.

## Update: admin UI + settings

- New `settings` table (in schema.sql). Site name, logos, favicon, address, phones, email, footer text and social links are edited in Admin > Settings (super admin).
- To apply: in phpMyAdmin drop `allure_db`, create it again (empty), copy the new files, run install.php again.
- Fresh install seeds default settings automatically.
